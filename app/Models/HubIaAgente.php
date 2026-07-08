<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class HubIaAgente extends Model
{
    protected string $table = 'hub_ia_agentes';

    public function listar(): array
    {
        $stmt = $this->pdo->query(
            "SELECT a.*, c.nome AS conector_nome, c.provider AS conector_provider, p.nome AS prompt_nome
             FROM {$this->table} a
             LEFT JOIN hub_ia_conectores c ON c.id = a.conector_id
             LEFT JOIN hub_ia_prompts p ON p.id = a.prompt_id
             ORDER BY a.nome ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, c.nome AS conector_nome, c.provider AS conector_provider
             FROM {$this->table} a
             LEFT JOIN hub_ia_conectores c ON c.id = a.conector_id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(int $usuarioId, array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, nome, avatar, descricao, conector_id, prompt_id, prompt_base,
                  temperatura, idioma, personalidade, permite_consulta_banco, ativo)
                 VALUES
                 (:usuario_id, :nome, :avatar, :descricao, :conector_id, :prompt_id, :prompt_base,
                  :temperatura, :idioma, :personalidade, :permite_consulta_banco, :ativo)"
            );
            $stmt->execute($this->mapParams($usuarioId, $d));
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaAgente::create] ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        try {
            $params = $this->mapParams(null, $d);
            unset($params[':usuario_id']);
            $params[':id'] = $id;

            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    nome = :nome, avatar = :avatar, descricao = :descricao, conector_id = :conector_id,
                    prompt_id = :prompt_id, prompt_base = :prompt_base, temperatura = :temperatura,
                    idioma = :idioma, personalidade = :personalidade,
                    permite_consulta_banco = :permite_consulta_banco, ativo = :ativo
                 WHERE id = :id"
            );
            return $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log('[HubIaAgente::update] ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $this->pdo->prepare("DELETE FROM hub_ia_agente_permissoes WHERE agente_id = ?")->execute([$id]);
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function mapParams(?int $usuarioId, array $d): array
    {
        return [
            ':usuario_id'             => $usuarioId,
            ':nome'                   => trim($d['nome']),
            ':avatar'                 => $d['avatar']        ?? '🤖',
            ':descricao'              => $d['descricao']     ?? null,
            ':conector_id'            => !empty($d['conector_id']) ? (int) $d['conector_id'] : null,
            ':prompt_id'              => !empty($d['prompt_id'])   ? (int) $d['prompt_id']   : null,
            ':prompt_base'            => $d['prompt_base']   ?? null,
            ':temperatura'            => isset($d['temperatura']) && $d['temperatura'] !== '' ? (float) $d['temperatura'] : null,
            ':idioma'                 => $d['idioma']        ?? 'pt-BR',
            ':personalidade'          => $d['personalidade'] ?? null,
            ':permite_consulta_banco' => (int) ($d['permite_consulta_banco'] ?? 0),
            ':ativo'                  => (int) ($d['ativo'] ?? 1),
        ];
    }
}
