<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class MarketingGatilho extends Model
{
    protected string $table = 'marketing_gatilhos';

    public const TIPOS = [
        'aniversario'         => 'Aniversário do Cliente',
        'novo_cliente'        => 'Novo Cliente Cadastrado',
        'pagamento_recebido'  => 'Pagamento Recebido',
        'nf_emitida'          => 'NF Emitida',
        'contrato_vencendo'   => 'Contrato Vencendo',
        'inatividade'         => 'Cliente Inativo',
    ];

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

        $canal = trim($filtros['canal'] ?? '');
        if ($canal !== '') {
            $where[] = 'canal = :canal';
            $params[':canal'] = $canal;
        }

        $ativo = $filtros['ativo'] ?? '';
        if ($ativo !== '') {
            $where[] = 'ativo = :ativo';
            $params[':ativo'] = (int)$ativo;
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

    public function countAtivos(int $usuarioId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ? AND ativo = 1"
        );
        $stmt->execute([$usuarioId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): string|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (usuario_id, nome, tipo, canal, ativo, assunto_email, conteudo_mensagem, delay_dias, condicao_json)
             VALUES (:uid, :nome, :tipo, :canal, :ativo, :assunto_email, :conteudo_mensagem, :delay_dias, :condicao_json)"
        );
        $stmt->bindValue(':uid',               (int)$data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nome',              trim((string)($data['nome'] ?? '')));
        $stmt->bindValue(':tipo',              $data['tipo']  ?? 'novo_cliente');
        $stmt->bindValue(':canal',             $data['canal'] ?? 'email');
        $stmt->bindValue(':ativo',             isset($data['ativo']) ? (int)$data['ativo'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':assunto_email',     $data['assunto_email'] ?? null);
        $stmt->bindValue(':conteudo_mensagem', $data['conteudo_mensagem'] ?? null);
        $stmt->bindValue(':delay_dias',        (int)($data['delay_dias'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':condicao_json',     $data['condicao_json'] ?? null);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['nome','tipo','canal','ativo','assunto_email','conteudo_mensagem',
                    'delay_dias','condicao_json','total_disparos','ultimo_disparo_em'];
        $sets   = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (!array_key_exists($f, $data)) continue;
            $sets[]        = "{$f} = :{$f}";
            $params[":{$f}"] = $data[$f];
        }
        if (empty($sets)) return false;
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function toggleAtivo(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
