<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\TenantContext;
use App\Models\User;
use App\Models\UserProfileAvatar;

class MobileProfileController extends MobileController
{
    public function me(): void
    {
        $this->requirePermission('view_profile');
        $user = (new User())->findById($this->currentUserId());
        if (!$user) {
            $this->error('Usuário não encontrado.', [], 404);
        }
        $this->success($this->payload($user));
    }

    public function update(): void
    {
        $this->requirePermission('edit_profile');
        $input = $this->input();
        $data = [];

        if (array_key_exists('name', $input)) {
            $name = $this->cleanString($input['name'], 255);
            if ($name === '') {
                $this->error('O nome não pode ficar vazio.', ['name' => ['Informe seu nome.']], 422);
            }
            $data['name'] = $name;
        }
        if (array_key_exists('locale', $input)) {
            $locale = $this->cleanString($input['locale'], 10);
            if (!in_array($locale, ['pt_BR', 'en', 'es'], true)) {
                $this->error('Idioma inválido.', ['locale' => ['Use pt_BR, en ou es.']], 422);
            }
            $data['locale'] = $locale;
        }
        if (empty($data)) {
            $this->error('Nenhum campo de perfil foi informado.', [], 422);
        }

        try {
            $updated = (new User())->updateMobileProfile($this->currentUserId(), $data);
            if (!$updated) {
                $this->error('Não foi possível atualizar o perfil.', [], 500);
            }
            $this->audit('mobile_profile_updated', ['user_id' => $this->currentUserId()]);
            $user = (new User())->findById($this->currentUserId());
            $this->success($this->payload($user), 'Perfil atualizado.');
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao atualizar perfil pelo app móvel', [
                'user_id' => $this->currentUserId(),
                'error' => $exception->getMessage(),
            ]);
            $this->error('Não foi possível atualizar o perfil.', [], 500);
        }
    }

    public function changePassword(): void
    {
        $this->requirePermission('edit_profile');
        $input = $this->input();
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmation = (string) ($input['new_password_confirmation'] ?? '');

        if ($currentPassword === '' || strlen($newPassword) < 8 || $newPassword !== $confirmation) {
            $this->error('Revise os dados de alteração de senha.', [
                'new_password' => ['A senha deve ter pelo menos oito caracteres e coincidir com a confirmação.'],
            ], 422);
        }

        $userModel = new User();
        $user = $userModel->findById($this->currentUserId());
        if (!$user || !Auth::verifyPassword($currentPassword, (string) $user->password)) {
            $this->audit('mobile_password_change_failed', ['user_id' => $this->currentUserId()]);
            $this->error('A senha atual está incorreta.', ['current_password' => ['Senha atual inválida.']], 401);
        }

        try {
            $updated = $userModel->updatePassword($this->currentUserId(), Auth::hashPassword($newPassword));
            if (!$updated) {
                $this->error('Não foi possível alterar a senha.', [], 500);
            }
            $this->audit('mobile_password_changed', ['user_id' => $this->currentUserId()]);
            $this->success(null, 'Senha alterada com sucesso.');
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao alterar senha pelo app móvel', [
                'user_id' => $this->currentUserId(),
                'error' => $exception->getMessage(),
            ]);
            $this->error('Não foi possível alterar a senha.', [], 500);
        }
    }

    public function uploadPhoto(): void
    {
        $this->requirePermission('edit_profile');
        $file = $_FILES['photo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->error('Envie uma foto de perfil válida.', ['photo' => ['Arquivo obrigatório.']], 422);
        }
        if (($file['size'] ?? 0) <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
            $this->error('A foto deve ter no máximo 5 MB.', ['photo' => ['Tamanho inválido.']], 422);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime])) {
            $this->error('Formato de imagem não permitido.', ['photo' => ['Use JPG, PNG ou WEBP.']], 422);
        }

        $tenantId = TenantContext::id();
        $userId = $this->currentUserId();
        $directory = BASE_PATH . '/storage/uploads/perfil/' . $tenantId . '/' . $userId;
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            (new Logger())->error('Falha ao criar diretório de foto de perfil móvel', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
            $this->error('Não foi possível preparar o envio da foto.', [], 500);
        }

        $relativePath = '/storage/uploads/perfil/' . $tenantId . '/' . $userId . '/avatar-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $absolutePath = BASE_PATH . $relativePath;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            (new Logger())->error('Falha ao mover foto de perfil móvel', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
            $this->error('Não foi possível salvar a foto.', [], 500);
        }

        try {
            $avatarModel = new UserProfileAvatar();
            $previous = $avatarModel->findForUser($userId);
            if (!$avatarModel->upsert($userId, $relativePath)) {
                @unlink($absolutePath);
                $this->error('Não foi possível registrar a foto.', [], 500);
            }
            if ($previous && !empty($previous->path) && str_starts_with((string) $previous->path, '/storage/uploads/perfil/')) {
                $oldPath = BASE_PATH . $previous->path;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $this->audit('mobile_profile_photo_updated', ['user_id' => $userId]);
            $this->success(['url' => $relativePath], 'Foto de perfil atualizada.');
        } catch (\Throwable $exception) {
            @unlink($absolutePath);
            (new Logger())->error('Falha ao registrar foto de perfil móvel', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            $this->error('Não foi possível salvar a foto.', [], 500);
        }
    }

    private function payload(object $user): array
    {
        $role = (string) ($user->tenant_role ?? $user->role ?? 'user');
        $tenant = TenantContext::tenant();
        $avatar = (new UserProfileAvatar())->findForUser((int) $user->id);
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'locale' => $user->locale ?? 'pt_BR',
            'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
            'avatar_url' => $avatar->path ?? null,
            'permissions' => (new Permission())->getPermissionsForRole($role),
            'tenant' => [
                'id' => (int) $tenant->id,
                'name' => $tenant->name ?? $tenant->razao_social ?? $tenant->slug ?? 'Empresa',
                'slug' => $tenant->slug ?? null,
            ],
        ];
    }
}
