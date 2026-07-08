<?php

namespace App\Services;

use App\Core\Mail;
use App\Models\User;

/**
 * Orquestra a geração, envio, validação e expiração do código de 2FA.
 * Nunca loga o código em texto puro (apenas o hash é persistido).
 *
 * Arquitetura: AuthController → TwoFactorService → MailService/SecurityLogService → User (repositório).
 */
class TwoFactorService
{
    private const CODE_MAX               = 9999; // 4 dígitos: 0000-9999
    private const EXPIRY_MINUTES         = 5;
    private const MAX_ATTEMPTS           = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const LOCK_MINUTES           = 15;

    private User $userModel;
    private SecurityLogService $securityLog;

    public function __construct()
    {
        $this->userModel   = new User();
        $this->securityLog = new SecurityLogService();
    }

    /**
     * Gera um código numérico de 4 dígitos criptograficamente seguro (random_int,
     * nunca rand()), salva o hash e envia por e-mail. Retorna se o e-mail foi enviado.
     */
    public function generateAndSendCode(object $user, string $ip, string $userAgent): bool
    {
        $code      = str_pad((string) random_int(0, self::CODE_MAX), 4, '0', STR_PAD_LEFT);
        $codeHash  = hash('sha256', $code);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_MINUTES . ' minutes'));

        $this->userModel->saveTwoFactorCode((int) $user->id, $codeHash, $expiresAt);

        $sent = Mail::sendTwoFactorCode($user->email, $user->name, $code, $ip, (int) $user->id);

        $this->securityLog->log('code_sent', (int) $user->id, $user->email, [
            'ip' => $ip, 'user_agent' => $userAgent,
        ]);

        return $sent;
    }

    public function canResend(object $user): bool
    {
        return $this->secondsUntilResend($user) <= 0;
    }

    public function secondsUntilResend(object $user): int
    {
        if (empty($user->two_factor_last_sent)) {
            return 0;
        }
        $elapsed   = time() - strtotime($user->two_factor_last_sent);
        $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;
        return max(0, $remaining);
    }

    public function isLocked(object $user): bool
    {
        return !empty($user->two_factor_locked_until) && strtotime($user->two_factor_locked_until) > time();
    }

    public function secondsUntilUnlock(object $user): int
    {
        if (empty($user->two_factor_locked_until)) {
            return 0;
        }
        return max(0, strtotime($user->two_factor_locked_until) - time());
    }

    /**
     * Valida o código informado contra o hash salvo, tratando bloqueio,
     * expiração e limite de tentativas. Nunca aceita código expirado, de
     * outra sessão (o hash pertence exclusivamente a este usuário) ou reutilizado
     * (markTwoFactorValidated limpa o código após sucesso).
     *
     * @return array{success:bool, reason:string, attempts_left?:int}
     */
    public function verifyCode(object $user, string $inputCode, string $ip, string $userAgent): array
    {
        $userId = (int) $user->id;

        if ($this->isLocked($user)) {
            $this->securityLog->log('verify_failed', $userId, $user->email, [
                'ip' => $ip, 'user_agent' => $userAgent,
            ]);
            return ['success' => false, 'reason' => 'locked'];
        }

        if (empty($user->two_factor_code) || empty($user->two_factor_expiration)) {
            return ['success' => false, 'reason' => 'no_pending_code'];
        }

        if (strtotime($user->two_factor_expiration) < time()) {
            $this->securityLog->log('verify_failed', $userId, $user->email, [
                'ip' => $ip, 'user_agent' => $userAgent,
            ]);
            return ['success' => false, 'reason' => 'expired'];
        }

        $inputHash = hash('sha256', $inputCode);
        if (!hash_equals((string) $user->two_factor_code, $inputHash)) {
            $attempts = $this->userModel->incrementTwoFactorAttempts($userId);

            $this->securityLog->log('verify_failed', $userId, $user->email, [
                'ip' => $ip, 'user_agent' => $userAgent, 'tentativas' => $attempts,
            ]);

            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . self::LOCK_MINUTES . ' minutes'));
                $this->userModel->lockTwoFactor($userId, $lockedUntil);
                $this->securityLog->log('locked', $userId, $user->email, [
                    'ip' => $ip, 'user_agent' => $userAgent,
                ]);
                return ['success' => false, 'reason' => 'locked'];
            }

            return ['success' => false, 'reason' => 'invalid_code', 'attempts_left' => self::MAX_ATTEMPTS - $attempts];
        }

        $this->userModel->markTwoFactorValidated($userId);
        $this->securityLog->log('verify_success', $userId, $user->email, [
            'ip' => $ip, 'user_agent' => $userAgent,
        ]);

        return ['success' => true, 'reason' => 'ok'];
    }
}
