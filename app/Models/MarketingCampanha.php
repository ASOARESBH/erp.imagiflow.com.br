<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class MarketingCampanha extends Model
{
    protected string $table = 'marketing_campanhas';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $where  = ['usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        $status = trim($filtros['status'] ?? '');
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $tipo = trim($filtros['tipo'] ?? '');
        if ($tipo !== '') {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        $q = trim($filtros['q'] ?? '');
        if ($q !== '') {
            $where[] = 'nome LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function countByStatus(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT status, COUNT(*) as total
             FROM {$this->table}
             WHERE usuario_id = ?
             GROUP BY status"
        );
        $stmt->execute([$usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $out  = [];
        foreach ($rows as $r) {
            $out[$r->status] = (int)$r->total;
        }
        return $out;
    }

    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, nome, tipo, status, assunto, conteudo, data_agendamento)
             VALUES (:uid, :nome, :tipo, :status, :assunto, :conteudo, :data_agendamento)"
        );
        $stmt->bindValue(':uid',              (int)$data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome',             trim((string)($data['nome'] ?? '')));
        $stmt->bindValue(':tipo',             $data['tipo']   ?? 'email');
        $stmt->bindValue(':status',           $data['status'] ?? 'rascunho');
        $stmt->bindValue(':assunto',          $data['assunto'] ?? null);
        $stmt->bindValue(':conteudo',         $data['conteudo'] ?? null);
        $stmt->bindValue(':data_agendamento', $data['data_agendamento'] ?? null);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['nome','tipo','status','assunto','conteudo','data_agendamento',
                    'total_destinatarios','total_enviados','total_erros'];
        $sets   = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $data)) continue;
            $sets[]      = "{$f} = :{$f}";
            $params[":{$f}"] = $data[$f];
        }
        if (empty($sets)) return false;
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
