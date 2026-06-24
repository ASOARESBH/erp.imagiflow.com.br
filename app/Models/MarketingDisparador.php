<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MarketingDisparador
{
    private PDO $pdo;
    private string $table = 'marketing_disparadores';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // ---------------------------------------------------------------
    // Consultas
    // ---------------------------------------------------------------

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['d.usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        if (!empty($filtros['campanha_id'])) {
            $where[]               = 'd.campanha_id = :campanha_id';
            $params[':campanha_id'] = (int) $filtros['campanha_id'];
        }
        if (!empty($filtros['status'])) {
            $where[]           = 'd.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['publico'])) {
            $where[]            = 'd.publico = :publico';
            $params[':publico'] = $filtros['publico'];
        }

        $sql = "SELECT d.*,
                       c.nome  AS campanha_nome,
                       c.canal AS campanha_canal
                FROM {$this->table} d
                LEFT JOIN marketing_campanhas c ON c.id = d.campanha_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY d.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*,
                    c.nome            AS campanha_nome,
                    c.canal           AS campanha_canal,
                    c.assunto_email   AS campanha_assunto,
                    c.corpo           AS campanha_corpo,
                    c.tipo_conteudo   AS campanha_tipo_conteudo,
                    c.remetente_nome  AS campanha_remetente_nome,
                    c.remetente_email AS campanha_remetente_email
             FROM {$this->table} d
             LEFT JOIN marketing_campanhas c ON c.id = d.campanha_id
             WHERE d.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findPendentesParaDisparo(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*,
                    c.canal           AS campanha_canal,
                    c.assunto_email   AS campanha_assunto,
                    c.corpo           AS campanha_corpo,
                    c.tipo_conteudo   AS campanha_tipo_conteudo,
                    c.remetente_nome  AS campanha_remetente_nome,
                    c.remetente_email AS campanha_remetente_email
             FROM {$this->table} d
             LEFT JOIN marketing_campanhas c ON c.id = d.campanha_id
             WHERE d.status IN ('agendado','em_andamento')
               AND (d.agendado_para IS NULL OR d.agendado_para <= NOW())
             ORDER BY d.agendado_para ASC, d.id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function dashboardStats(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                            AS total_disparadores,
               SUM(CASE WHEN status='concluido' THEN 1 ELSE 0 END) AS concluidos,
               SUM(CASE WHEN status='em_andamento' THEN 1 ELSE 0 END) AS em_andamento,
               SUM(CASE WHEN status='agendado' THEN 1 ELSE 0 END)  AS agendados,
               SUM(total_enviados)                                  AS total_enviados,
               SUM(total_erros)                                     AS total_erros
             FROM {$this->table}
             WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // Escrita
    // ---------------------------------------------------------------

    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, campanha_id, nome, publico, segmentacao,
              total_destinatarios, status, agendado_para, intervalo_envio, lote_tamanho)
             VALUES
             (:usuario_id, :campanha_id, :nome, :publico, :segmentacao,
              :total_destinatarios, :status, :agendado_para, :intervalo_envio, :lote_tamanho)"
        );
        $stmt->execute([
            ':usuario_id'          => (int) $data['usuario_id'],
            ':campanha_id'         => (int) $data['campanha_id'],
            ':nome'                => $data['nome'],
            ':publico'             => $data['publico'],
            ':segmentacao'         => $data['segmentacao'] ?? null,
            ':total_destinatarios' => (int) ($data['total_destinatarios'] ?? 0),
            ':status'              => $data['status'] ?? 'rascunho',
            ':agendado_para'       => $data['agendado_para'] ?? null,
            ':intervalo_envio'     => (int) ($data['intervalo_envio'] ?? 5),
            ':lote_tamanho'        => (int) ($data['lote_tamanho'] ?? 5),
        ]);
        return $this->pdo->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [':id' => $id];

        $campos = [
            'nome', 'campanha_id', 'publico', 'segmentacao', 'total_destinatarios',
            'status', 'agendado_para', 'intervalo_envio', 'lote_tamanho',
            'iniciado_em', 'concluido_em', 'total_enviados', 'total_erros', 'log_execucao',
        ];
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $data)) {
                $sets[]              = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $data[$campo];
            }
        }

        if (empty($sets)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function incrementarEnviados(int $id, int $qtd = 1): void
    {
        $this->pdo->exec(
            "UPDATE {$this->table} SET total_enviados = total_enviados + {$qtd} WHERE id = {$id}"
        );
    }

    public function incrementarErros(int $id, int $qtd = 1): void
    {
        $this->pdo->exec(
            "UPDATE {$this->table} SET total_erros = total_erros + {$qtd} WHERE id = {$id}"
        );
    }

    public function delete(int $id, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE id = :id AND usuario_id = :uid"
        );
        return $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
    }
}
