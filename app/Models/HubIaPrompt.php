<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class HubIaPrompt extends Model
{
    protected string $table = 'hub_ia_prompts';

    public function listar(bool $apenasAtivos = false): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($apenasAtivos) {
            $sql .= " WHERE ativo = 1";
        }
        $sql .= " ORDER BY nome ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(int $usuarioId, array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (usuario_id, nome, categoria, conteudo, ativo)
                 VALUES (:usuario_id, :nome, :categoria, :conteudo, :ativo)"
            );
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':nome'       => trim($d['nome']),
                ':categoria'  => $d['categoria'] ?? null,
                ':conteudo'   => $d['conteudo'],
                ':ativo'      => (int) ($d['ativo'] ?? 1),
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaPrompt::create] ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET nome = :nome, categoria = :categoria, conteudo = :conteudo, ativo = :ativo
                 WHERE id = :id"
            );
            return $stmt->execute([
                ':nome'      => trim($d['nome']),
                ':categoria' => $d['categoria'] ?? null,
                ':conteudo'  => $d['conteudo'],
                ':ativo'     => (int) ($d['ativo'] ?? 1),
                ':id'        => $id,
            ]);
        } catch (\Throwable $e) {
            error_log('[HubIaPrompt::update] ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Substitui variáveis {{nome}} no conteúdo do prompt pelos valores fornecidos.
     */
    public static function interpolar(string $conteudo, array $variaveis): string
    {
        foreach ($variaveis as $k => $v) {
            $conteudo = str_replace('{{' . $k . '}}', (string) $v, $conteudo);
        }
        return $conteudo;
    }
}
