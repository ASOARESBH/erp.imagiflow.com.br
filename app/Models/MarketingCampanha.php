<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MarketingCampanha
{
    private PDO $pdo;
    private string $table = 'marketing_campanhas';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // ---------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['c.usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['canal'])) {
            $where[]          = 'c.canal = :canal';
            $params[':canal'] = $filtros['canal'];
        }
        if (!empty($filtros['status'])) {
            $where[]           = 'c.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[]      = '(c.nome LIKE :q OR c.descricao LIKE :q)';
            $params[':q'] = '%' . $filtros['q'] . '%';
        }

        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM marketing_disparadores d WHERE d.campanha_id = c.id) AS total_disparadores
                FROM {$this->table} c
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM marketing_disparadores d WHERE d.campanha_id = c.id) AS total_disparadores,
                    (SELECT COUNT(*) FROM marketing_envios e
                     INNER JOIN marketing_disparadores d2 ON d2.id = e.disparador_id
                     WHERE d2.campanha_id = c.id) AS total_envios_geral
             FROM {$this->table} c
             WHERE c.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findAtivas(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nome, canal FROM {$this->table}
             WHERE usuario_id = :uid AND status = 'ativa'
             ORDER BY nome ASC"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function countByCanal(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT canal, COUNT(*) AS total
             FROM {$this->table}
             WHERE usuario_id = :uid
             GROUP BY canal"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // Escrita
    // ---------------------------------------------------------------

    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, nome, descricao, canal, status, assunto_email, tipo_conteudo,
              corpo, remetente_nome, remetente_email, numero_origem)
             VALUES
             (:usuario_id, :nome, :descricao, :canal, :status, :assunto_email, :tipo_conteudo,
              :corpo, :remetente_nome, :remetente_email, :numero_origem)"
        );
        $stmt->execute([
            ':usuario_id'      => (int) $data['usuario_id'],
            ':nome'            => $data['nome'],
            ':descricao'       => $data['descricao'] ?? null,
            ':canal'           => $data['canal'],
            ':status'          => $data['status'] ?? 'rascunho',
            ':assunto_email'   => $data['assunto_email'] ?? null,
            ':tipo_conteudo'   => $data['tipo_conteudo'] ?? 'html',
            ':corpo'           => $data['corpo'] ?? null,
            ':remetente_nome'  => $data['remetente_nome'] ?? null,
            ':remetente_email' => $data['remetente_email'] ?? null,
            ':numero_origem'   => $data['numero_origem'] ?? null,
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [':id' => $id];

        $campos = [
            'nome', 'descricao', 'canal', 'status', 'assunto_email',
            'tipo_conteudo', 'corpo', 'remetente_nome', 'remetente_email', 'numero_origem',
        ];
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $data)) {
                $sets[]          = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $data[$campo];
            }
        }

        if (empty($sets)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function incrementarContador(int $id, string $campo): void
    {
        $campos = ['total_enviados', 'total_abertos', 'total_cliques', 'total_erros'];
        if (!in_array($campo, $campos, true)) return;
        $this->pdo->exec("UPDATE {$this->table} SET {$campo} = {$campo} + 1 WHERE id = {$id}");
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = :id AND usuario_id = :uid"
        );
        return $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
    }
}
