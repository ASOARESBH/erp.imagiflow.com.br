<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use PDO;

class ApuracaoVoxelImport extends Model
{
    protected string $table = 'apuracao_voxel_imports';

    public function existsForUser(int $usuarioId, string $sourceReference): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM {$this->table}
             WHERE usuario_id = :usuario_id
               AND source_reference = :source_reference
               AND status IN ('pendente', 'importado')
             LIMIT 1"
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':source_reference' => $sourceReference,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function reserve(int $usuarioId, int $apuracaoId, string $sourceReference, string $requestId, string $itemHash): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, apuracao_id, source_reference, request_id, item_hash, status)
                 VALUES (:usuario_id, :apuracao_id, :source_reference, :request_id, :item_hash, 'pendente')"
            );
            return $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':apuracao_id' => $apuracaoId,
                ':source_reference' => $sourceReference,
                ':request_id' => $requestId !== '' ? $requestId : null,
                ':item_hash' => $itemHash,
            ]);
        } catch (\PDOException $exception) {
            (new Logger())->warning('Estudo VOXEL duplicado ou falha na reserva de importação', [
                'usuario_id' => $usuarioId,
                'apuracao_id' => $apuracaoId,
                'source_reference_hash' => hash('sha256', $sourceReference),
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    public function releasePendingForApuracao(int $usuarioId, int $apuracaoId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table}
             WHERE usuario_id = :usuario_id
               AND apuracao_id = :apuracao_id
               AND status = 'pendente'"
        );
        return $stmt->execute([':usuario_id' => $usuarioId, ':apuracao_id' => $apuracaoId]);
    }

    public function markImportedByApuracao(int $usuarioId, int $apuracaoId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET status = 'importado', updated_at = NOW()
             WHERE usuario_id = :usuario_id
               AND apuracao_id = :apuracao_id
               AND status = 'pendente'"
        );
        return $stmt->execute([':usuario_id' => $usuarioId, ':apuracao_id' => $apuracaoId]);
    }
}
