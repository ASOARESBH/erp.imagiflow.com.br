<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class HubIaHistorico extends Model
{
    protected string $table = 'hub_ia_historico';

    public function registrar(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (agente_id, usuario_id, modulo_origem, pergunta, resposta, sql_gerado, sql_linhas_retornadas,
                  provider, modelo, tokens_prompt, tokens_resposta, tokens_total, custo_estimado_usd,
                  tempo_ms, ip_address, status)
                 VALUES
                 (:agente_id, :usuario_id, :modulo_origem, :pergunta, :resposta, :sql_gerado, :sql_linhas,
                  :provider, :modelo, :tokens_prompt, :tokens_resposta, :tokens_total, :custo,
                  :tempo_ms, :ip, :status)"
            );
            $stmt->execute([
                ':agente_id'       => $d['agente_id']       ?? null,
                ':usuario_id'      => $d['usuario_id']      ?? null,
                ':modulo_origem'   => $d['modulo_origem']   ?? 'hub_ia',
                ':pergunta'        => $d['pergunta'],
                ':resposta'        => $d['resposta']        ?? null,
                ':sql_gerado'      => $d['sql_gerado']      ?? null,
                ':sql_linhas'      => $d['sql_linhas_retornadas'] ?? null,
                ':provider'        => $d['provider']        ?? null,
                ':modelo'          => $d['modelo']          ?? null,
                ':tokens_prompt'   => $d['tokens_prompt']   ?? null,
                ':tokens_resposta' => $d['tokens_resposta'] ?? null,
                ':tokens_total'    => $d['tokens_total']    ?? null,
                ':custo'           => $d['custo_estimado_usd'] ?? null,
                ':tempo_ms'        => $d['tempo_ms']        ?? null,
                ':ip'              => $d['ip_address']      ?? null,
                ':status'          => $d['status']          ?? 'sucesso',
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaHistorico::registrar] ' . $e->getMessage());
            return false;
        }
    }

    public function listar(array $filtros = [], int $limit = 100): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['agente_id'])) {
            $where[] = 'agente_id = :agente_id';
            $params[':agente_id'] = (int) $filtros['agente_id'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[] = 'usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'created_at >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'created_at <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }

        $sql = "SELECT h.*, a.nome AS agente_nome, u.name AS usuario_nome
                FROM {$this->table} h
                LEFT JOIN hub_ia_agentes a ON a.id = h.agente_id
                LEFT JOIN users u ON u.id = h.usuario_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY h.created_at DESC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function kpisHoje(): object
    {
        $stmt = $this->pdo->query(
            "SELECT
                COUNT(*) AS perguntas_hoje,
                COALESCE(SUM(tokens_total), 0) AS tokens_hoje,
                COALESCE(SUM(custo_estimado_usd), 0) AS custo_hoje
             FROM hub_ia_historico
             WHERE DATE(created_at) = CURDATE()"
        );
        return $stmt->fetch() ?: (object) ['perguntas_hoje' => 0, 'tokens_hoje' => 0, 'custo_hoje' => 0];
    }

    public function consumoPorModulo(int $dias = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT modulo_origem AS modulo, COUNT(*) AS total, COALESCE(SUM(tokens_total),0) AS tokens
             FROM hub_ia_historico
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
             GROUP BY modulo_origem
             ORDER BY total DESC"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function consumoDiario(int $dias = 14): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS dia, COUNT(*) AS total, COALESCE(SUM(tokens_total),0) AS tokens,
                    COALESCE(SUM(custo_estimado_usd),0) AS custo
             FROM hub_ia_historico
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
             GROUP BY DATE(created_at)
             ORDER BY dia ASC"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function custosPorProvider(int $dias = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT provider, COUNT(*) AS total, COALESCE(SUM(tokens_total),0) AS tokens,
                    COALESCE(SUM(custo_estimado_usd),0) AS custo
             FROM hub_ia_historico
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :dias DAY) AND provider IS NOT NULL
             GROUP BY provider
             ORDER BY custo DESC"
        );
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function tempoMedioResposta(): float
    {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(AVG(tempo_ms), 0) AS media FROM hub_ia_historico WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        return (float) ($stmt->fetch()->media ?? 0);
    }
}
