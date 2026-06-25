<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MarketingInteracaoCrm
{
    private PDO $pdo;
    private string $table = 'marketing_interacoes_crm';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByRelated(string $tipo, int $id): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT i.*,
                        c.nome  AS campanha_nome,
                        c.canal AS campanha_canal
                 FROM {$this->table} i
                 LEFT JOIN marketing_campanhas c ON c.id = i.campanha_id
                 WHERE i.related_type = :tipo AND i.related_id = :id
                 ORDER BY i.ocorrido_em DESC"
            );
            $stmt->execute([':tipo' => $tipo, ':id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function create(array $data): string|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, envio_id, campanha_id, related_type, related_id, evento, observacao, ocorrido_em)
                 VALUES
                 (:usuario_id, :envio_id, :campanha_id, :related_type, :related_id, :evento, :observacao, NOW())"
            );
            $stmt->execute([
                ':usuario_id'  => (int) $data['usuario_id'],
                ':envio_id'    => (int) $data['envio_id'],
                ':campanha_id' => (int) $data['campanha_id'],
                ':related_type'=> $data['related_type'],
                ':related_id'  => (int) $data['related_id'],
                ':evento'      => $data['evento'],
                ':observacao'  => $data['observacao'] ?? null,
            ]);
            return $this->pdo->lastInsertId() ?: false;
        } catch (\Throwable $e) {
            error_log('[MarketingInteracaoCrm] create: ' . $e->getMessage());
            return false;
        }
    }

    public function countEventosByRelated(string $tipo, int $id): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT evento, COUNT(*) AS total
                 FROM {$this->table}
                 WHERE related_type = :tipo AND related_id = :id
                 GROUP BY evento"
            );
            $stmt->execute([':tipo' => $tipo, ':id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
