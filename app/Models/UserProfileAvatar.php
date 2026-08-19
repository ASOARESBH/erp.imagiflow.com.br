<?php

namespace App\Models;

use App\Core\Model;
use App\Core\TenantContext;
use PDO;

class UserProfileAvatar extends Model
{
    public function findForUser(int $userId): object|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, path, created_at, updated_at
             FROM user_profile_avatars
             WHERE tenant_id = :tenant_id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            ':tenant_id' => TenantContext::id(),
            ':user_id' => $userId,
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function upsert(int $userId, string $path): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_profile_avatars (tenant_id, user_id, path)
             VALUES (:tenant_id, :user_id, :path)
             ON DUPLICATE KEY UPDATE path = VALUES(path), updated_at = NOW()'
        );
        return $stmt->execute([
            ':tenant_id' => TenantContext::id(),
            ':user_id' => $userId,
            ':path' => $path,
        ]);
    }
}
