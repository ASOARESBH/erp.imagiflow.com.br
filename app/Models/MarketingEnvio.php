<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MarketingEnvio
{
    private PDO $pdo;
    private string $table = 'marketing_envios';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // ---------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------

    public function findByDisparadorId(int $disparadorId, array $filtros = []): array
    {
        $where  = ['e.disparador_id = :did'];
        $params = [':did' => $disparadorId];

        if (!empty($filtros['status'])) {
            $where[]           = 'e.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[]      = '(e.destinatario_nome LIKE :q OR e.destinatario_email LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $sql = "SELECT e.* FROM {$this->table} e
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findPendentesByDisparadorId(int $disparadorId, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE disparador_id = :did AND status = 'pendente'
             ORDER BY id ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':did', $disparadorId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findByToken(string $token): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE tracking_token = :token LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function countByDisparadorId(int $disparadorId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status, COUNT(*) AS total
             FROM {$this->table}
             WHERE disparador_id = :did
             GROUP BY status"
        );
        $stmt->execute([':did' => $disparadorId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function statsGerais(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                                AS total,
               SUM(CASE WHEN status='enviado'  THEN 1 ELSE 0 END)    AS enviados,
               SUM(CASE WHEN status='aberto'   THEN 1 ELSE 0 END)    AS abertos,
               SUM(CASE WHEN status='clicado'  THEN 1 ELSE 0 END)    AS clicados,
               SUM(CASE WHEN status='erro'     THEN 1 ELSE 0 END)    AS erros
             FROM {$this->table}
             WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByDestinatario(string $tipo, int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*,
                    d.nome          AS disparador_nome,
                    c.nome          AS campanha_nome,
                    c.canal         AS campanha_canal
             FROM {$this->table} e
             LEFT JOIN marketing_disparadores d ON d.id = e.disparador_id
             LEFT JOIN marketing_campanhas    c ON c.id = d.campanha_id
             WHERE e.destinatario_tipo = :tipo AND e.destinatario_id = :id
             ORDER BY e.created_at DESC"
        );
        $stmt->execute([':tipo' => $tipo, ':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // Escrita
    // ---------------------------------------------------------------

    public function createBatch(array $registros): int
    {
        if (empty($registros)) return 0;

        $placeholders = [];
        $params       = [];
        foreach ($registros as $i => $r) {
            $placeholders[] = "(:uid{$i}, :did{$i}, :dtype{$i}, :did2{$i}, :dname{$i}, :demail{$i}, :dtel{$i}, :token{$i})";
            $params[":uid{$i}"]    = (int) $r['usuario_id'];
            $params[":did{$i}"]    = (int) $r['disparador_id'];
            $params[":dtype{$i}"]  = $r['destinatario_tipo'];
            $params[":did2{$i}"]   = (int) $r['destinatario_id'];
            $params[":dname{$i}"]  = $r['destinatario_nome'] ?? null;
            $params[":demail{$i}"] = $r['destinatario_email'] ?? null;
            $params[":dtel{$i}"]   = $r['destinatario_tel'] ?? null;
            $params[":token{$i}"]  = bin2hex(random_bytes(16));
        }

        $sql = "INSERT INTO {$this->table}
                (usuario_id, disparador_id, destinatario_tipo, destinatario_id,
                 destinatario_nome, destinatario_email, destinatario_tel, tracking_token)
                VALUES " . implode(', ', $placeholders);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->rowCount();
    }

    public function updateStatus(int $id, string $status, ?string $erroMsg = null): bool
    {
        $campos = ['status = :status', 'updated_at = NOW()'];
        $params = [':status' => $status, ':id' => $id];

        if ($status === 'enviado') {
            $campos[] = 'enviado_em = NOW()';
        } elseif ($status === 'aberto') {
            $campos[] = 'aberto_em = NOW()';
        } elseif ($status === 'clicado') {
            $campos[] = 'clicado_em = NOW()';
        }

        if ($erroMsg !== null) {
            $campos[]       = 'erro_msg = :erro_msg';
            $params[':erro_msg'] = $erroMsg;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function deleteByDisparadorId(int $disparadorId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE disparador_id = :did");
        $stmt->execute([':did' => $disparadorId]);
    }
}
