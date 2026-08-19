<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Auth;
use App\Core\Mail;
use App\Core\Permission;
use App\Core\TenantContext;
use App\Core\Audit\AuditLogger;
use App\Models\ApiToken;
use App\Models\MobileAuthChallenge;
use App\Models\MobileLoginAttempt;
use App\Models\PasswordResetToken;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TwoFactorService;

class MobileAuthController extends MobileController
{
    public function ping(): void
    {
        $tenant = TenantContext::tenant();
        $this->success([
            'tenant' => [
                'id' => (int) $tenant->id,
                'name' => $tenant->name ?? $tenant->razao_social ?? $tenant->slug ?? 'Empresa',
                'slug' => $tenant->slug ?? null,
            ],
        ]);
    }

    public function login(): void
    {
        $input = $this->input();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $deviceName = $this->cleanString($input['device_name'] ?? '', 120);
        $devicePlatform = strtolower($this->cleanString($input['device_platform'] ?? '', 10));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $this->error('Informe um e-mail válido e a senha.', [
                'email' => ['Informe um e-mail válido.'],
                'password' => ['Informe a senha.'],
            ], 422);
        }
        if ($devicePlatform !== '' && !in_array($devicePlatform, ['ios', 'android'], true)) {
            $this->error('Plataforma de dispositivo inválida.', ['device_platform' => ['Use ios ou android.']], 422);
        }

        $tenantId = TenantContext::id();
        $attempts = new MobileLoginAttempt();
        if ($attempts->isBlocked($tenantId, $email, $ip)) {
            $this->audit('mobile_login_rate_limited', ['email_hash' => hash('sha256', $email)]);
            $this->error('Muitas tentativas. Aguarde alguns minutos e tente novamente.', [], 429);
        }

        $user = (new User())->findByEmail($email);
        $isValid = $user && ($user->status ?? 'ativo') === 'ativo' && Auth::verifyPassword($password, (string) $user->password);
        if (!$isValid) {
            $attempts->register($tenantId, $email, $ip, false);
            $this->audit('mobile_login_failed', ['email_hash' => hash('sha256', $email)]);
            $this->error('E-mail ou senha inválidos.', ['credentials' => ['E-mail ou senha inválidos.']], 401);
        }

        $attempts->register($tenantId, $email, $ip, true);

        if (!empty($user->two_factor_enabled)) {
            $service = new TwoFactorService();
            $sent = $service->generateAndSendCode($user, $ip, (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'mobile'));
            $challenge = (new MobileAuthChallenge())->create($tenantId, (int) $user->id, $deviceName, $devicePlatform ?: null);
            if (!$challenge) {
                $this->error('Não foi possível iniciar a verificação em dois fatores. Tente novamente.', [], 500);
            }

            $this->audit('mobile_2fa_challenge_created', [
                'user_id' => (int) $user->id,
                'email_enviado' => $sent,
            ]);
            $this->success([
                'requires_2fa' => true,
                'challenge_token' => $challenge['token'],
                'expires_at' => $challenge['expires_at'],
                'code_length' => 4,
                'email_sent' => $sent,
            ], 'Informe o código de verificação enviado ao seu e-mail.');
        }

        $this->issueTokenResponse($user, $deviceName, $devicePlatform ?: null);
    }

    public function forgotPassword(): void
    {
        $input = $this->input();
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = (new User())->findByEmail($email);
            if ($user) {
                try {
                    $reset = (new PasswordResetToken())->createForUser((int) $user->id);
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
                    $baseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
                    if ($baseUrl === '') {
                        $baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
                    }
                    Mail::sendPasswordResetLink($user->email, $baseUrl . '/reset-password/' . $reset['raw'], (int) $user->id);
                    $this->audit('mobile_forgot_password_requested', ['user_id' => (int) $user->id]);
                } catch (\Throwable $exception) {
                    // A resposta deve permanecer neutra para não enumerar contas; o erro fica no log do servidor.
                    (new \App\Core\Logger())->warning('Falha no fluxo móvel de recuperação de senha', [
                        'tenant_id' => TenantContext::id(),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
        $this->success(null, 'Se este e-mail estiver cadastrado, você receberá as instruções de recuperação.');
    }

    public function verifyTwoFactor(): void
    {
        $input = $this->input();
        $challengeToken = trim((string) ($input['challenge_token'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));

        if ($challengeToken === '' || !preg_match('/^\d{4}$/', $code)) {
            $this->error('Informe o desafio e os quatro dígitos do código.', [
                'code' => ['O código deve ter quatro dígitos.'],
            ], 422);
        }

        $challengeModel = new MobileAuthChallenge();
        $challenge = $challengeModel->findPendingByPlainToken($challengeToken);
        if (!$challenge || (int) $challenge->tenant_id !== TenantContext::id()) {
            $this->error('A verificação expirou. Faça login novamente.', [], 401);
        }

        $user = (new User())->findById((int) $challenge->user_id);
        if (!$user) {
            $this->error('Usuário não encontrado neste ambiente.', [], 401);
        }

        $service = new TwoFactorService();
        $result = $service->verifyCode(
            $user,
            $code,
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'mobile')
        );
        if (!$result['success']) {
            $messages = [
                'locked' => 'Muitas tentativas incorretas. Aguarde alguns minutos.',
                'expired' => 'O código expirou. Solicite um novo código.',
                'no_pending_code' => 'Não há código pendente. Solicite um novo código.',
                'invalid_code' => 'Código inválido.',
            ];
            $this->audit('mobile_2fa_verify_failed', [
                'user_id' => (int) $user->id,
                'reason' => $result['reason'] ?? 'unknown',
            ]);
            $this->error($messages[$result['reason'] ?? ''] ?? 'Código inválido.', [], 401);
        }

        if (!$challengeModel->consume((int) $challenge->id)) {
            $this->error('Esta verificação já foi utilizada. Faça login novamente.', [], 409);
        }

        $this->audit('mobile_2fa_verify_success', ['user_id' => (int) $user->id]);
        $this->issueTokenResponse($user, (string) ($challenge->device_name ?? ''), $challenge->device_platform ?? null);
    }

    public function resendTwoFactor(): void
    {
        $input = $this->input();
        $challengeToken = trim((string) ($input['challenge_token'] ?? ''));
        $challenge = (new MobileAuthChallenge())->findPendingByPlainToken($challengeToken);
        if (!$challenge || (int) $challenge->tenant_id !== TenantContext::id()) {
            $this->error('A verificação expirou. Faça login novamente.', [], 401);
        }

        $user = (new User())->findById((int) $challenge->user_id);
        if (!$user) {
            $this->error('Usuário não encontrado neste ambiente.', [], 401);
        }

        $service = new TwoFactorService();
        if ($service->isLocked($user)) {
            $this->error('Conta temporariamente bloqueada. Aguarde alguns minutos.', [], 429);
        }
        if (!$service->canResend($user)) {
            $this->error('Aguarde antes de solicitar outro código.', [
                'seconds_left' => [(string) $service->secondsUntilResend($user)],
            ], 429);
        }

        $sent = $service->generateAndSendCode(
            $user,
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'mobile')
        );
        $this->audit('mobile_2fa_code_resent', ['user_id' => (int) $user->id, 'email_enviado' => $sent]);
        $this->success(['email_sent' => $sent], $sent ? 'Código reenviado.' : 'Não foi possível enviar o código.');
    }

    public function logout(): void
    {
        $tokenId = (int) ($_SESSION['api_token_id'] ?? 0);
        if ($tokenId <= 0) {
            $this->error('Sessão móvel inválida.', [], 401);
        }

        (new ApiToken())->revokeCurrent($tokenId, $this->currentTenantId(), $this->currentUserId());
        $this->audit('mobile_logout', ['token_id' => $tokenId]);
        $this->success(null, 'Sessão encerrada.');
    }

    public function devices(): void
    {
        $tokens = (new ApiToken())->listActiveForUser($this->currentTenantId(), $this->currentUserId());
        $currentTokenId = (int) ($_SESSION['api_token_id'] ?? 0);
        foreach ($tokens as $token) {
            $token->is_current = (int) $token->id === $currentTokenId;
            unset($token->push_token);
        }
        $this->success(['items' => $tokens]);
    }

    public function revokeDevice(int $id): void
    {
        $revoked = (new ApiToken())->revoke($id, $this->currentTenantId(), $this->currentUserId());
        if (!$revoked) {
            $this->error('Dispositivo não encontrado ou já revogado.', [], 404);
        }
        $this->audit('mobile_device_revoked', ['token_id' => $id]);
        $this->success(null, 'Dispositivo revogado.');
    }

    public function updatePushToken(): void
    {
        $input = $this->input();
        $pushToken = $this->cleanString($input['push_token'] ?? '', 255);
        $tokenId = (int) ($_SESSION['api_token_id'] ?? 0);
        if ($tokenId <= 0) {
            $this->error('Sessão móvel inválida.', [], 401);
        }

        $updated = (new ApiToken())->updatePushToken($tokenId, $this->currentTenantId(), $this->currentUserId(), $pushToken);
        if (!$updated) {
            $this->error('Não foi possível atualizar este dispositivo.', [], 500);
        }
        $this->audit('mobile_push_token_updated', ['token_id' => $tokenId]);
        $this->success(null, 'Dispositivo atualizado.');
    }

    private function issueTokenResponse(object $user, string $deviceName, ?string $devicePlatform): never
    {
        $issued = (new ApiToken())->issue(
            TenantContext::id(),
            (int) $user->id,
            $deviceName,
            $devicePlatform
        );
        if (!$issued) {
            $this->error('Não foi possível iniciar a sessão móvel. Tente novamente.', [], 500);
        }

        $this->audit('mobile_login_success', [
            'user_id' => (int) $user->id,
            'token_id' => $issued['id'],
        ]);
        $this->success([
            'requires_2fa' => false,
            'access_token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expires_at'],
            'profile' => $this->profilePayload($user),
        ], 'Login realizado com sucesso.');
    }

    private function profilePayload(object $user): array
    {
        $tenant = TenantContext::tenant();
        $role = (string) ($user->tenant_role ?? $user->role ?? 'user');
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'locale' => $user->locale ?? 'pt_BR',
            'permissions' => (new Permission())->getPermissionsForRole($role),
            'tenant' => [
                'id' => (int) $tenant->id,
                'name' => $tenant->name ?? $tenant->razao_social ?? $tenant->slug ?? 'Empresa',
                'slug' => $tenant->slug ?? null,
            ],
        ];
    }
}
