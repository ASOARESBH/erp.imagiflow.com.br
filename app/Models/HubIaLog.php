<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Log técnico por chamada de API (nível mais baixo que hub_ia_historico —
 * inclui status HTTP e erro bruto, útil para diagnóstico/monitoramento).
 */
class HubIaLog extends Model
{
    protected string $table = 'hub_ia_logs';

    public function registrar(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (conector_id, agente_id, historico_id, provider, status_http, erro, tempo_ms, tokens_total)
                 VALUES (:conector_id, :agente_id, :historico_id, :provider, :status_http, :erro, :tempo_ms, :tokens_total)"
            );
            $stmt->execute([
                ':conector_id'  => $d['conector_id']  ?? null,
                ':agente_id'    => $d['agente_id']    ?? null,
                ':historico_id' => $d['historico_id'] ?? null,
                ':provider'     => $d['provider']     ?? null,
                ':status_http'  => $d['status_http']  ?? null,
                ':erro'         => $d['erro']         ?? null,
                ':tempo_ms'     => $d['tempo_ms']     ?? null,
                ':tokens_total' => $d['tokens_total'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[HubIaLog::registrar] ' . $e->getMessage());
            return false;
        }
    }

    public function listar(int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.*, c.nome AS conector_nome
             FROM {$this->table} l
             LEFT JOIN hub_ia_conectores c ON c.id = l.conector_id
             ORDER BY l.created_at DESC LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function taxaFalhas(int $dias = 7): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN erro IS NOT NULL AND erro != '' THEN 1 ELSE 0 END) AS falhas
             FROM {$this->table}
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)"
        );
        $stmt->execute([':dias' => $dias]);
        $row = $stmt->fetch();
        $total = (int) ($row->total ?? 0);
        if ($total === 0) {
            return 0.0;
        }
        return round(((int) $row->falhas / $total) * 100, 1);
    }
}
