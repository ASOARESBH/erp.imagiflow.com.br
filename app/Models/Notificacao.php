<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Notificacao
{
    private PDO $pdo;
    private string $table = 'notificacoes';

    // Tipos de notificação disponíveis no sistema
    public const TIPOS = [
        'crm_retorno_vencendo'     => ['label' => 'CRM — Retorno vencendo (Oportunidades)',    'icone' => 'fas fa-chart-line',       'cor' => 'warning'],
        'crm_lead_retorno_vencendo'=> ['label' => 'CRM — Retorno vencendo (Leads)',             'icone' => 'fas fa-user-plus',        'cor' => 'warning'],
        'conta_pagar_vencendo'     => ['label' => 'Contas a Pagar — Vencendo em breve',         'icone' => 'fas fa-file-invoice-dollar','cor' => 'danger'],
        'conta_pagar_vencida'      => ['label' => 'Contas a Pagar — Vencida (em atraso)',       'icone' => 'fas fa-exclamation-circle','cor' => 'danger'],
        'conta_receber_vencendo'   => ['label' => 'Contas a Receber — Vencendo em breve',       'icone' => 'fas fa-hand-holding-usd', 'cor' => 'info'],
        'conta_receber_vencida'    => ['label' => 'Contas a Receber — Vencida (em atraso)',     'icone' => 'fas fa-exclamation-triangle','cor' => 'danger'],
        'oportunidade_fechamento'  => ['label' => 'CRM — Data de fechamento prevista próxima',  'icone' => 'fas fa-handshake',        'cor' => 'primary'],
        'contrato_vencendo'        => ['label' => 'Contratos — Vencendo em breve',              'icone' => 'fas fa-file-contract',    'cor' => 'warning'],
        'marketing_disparo'        => ['label' => 'Marketing — Disparo concluído',              'icone' => 'fas fa-bullhorn',         'cor' => 'success'],
        'sistema_geral'            => ['label' => 'Sistema — Avisos gerais',                    'icone' => 'fas fa-info-circle',      'cor' => 'primary'],
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /** Conta notificações não lidas do usuário — retorna 0 em caso de erro (tabela inexistente, etc.) */
    public function countNaoLidas(int $usuarioId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ? AND lida = 0"
            );
            $stmt->execute([$usuarioId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Busca as últimas N notificações do usuário (lidas e não lidas) */
    public function findRecentes(int $usuarioId, int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table}
                 WHERE usuario_id = ?
                 ORDER BY lida ASC, created_at DESC
                 LIMIT {$limit}"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Busca todas as notificações do usuário com paginação */
    public function findByUsuario(int $usuarioId, int $page = 1, int $perPage = 30): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $stmt   = $this->pdo->prepare(
                "SELECT * FROM {$this->table}
                 WHERE usuario_id = ?
                 ORDER BY lida ASC, created_at DESC
                 LIMIT {$perPage} OFFSET {$offset}"
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Cria uma notificação — previne duplicatas por (usuario_id, tipo, referencia_tipo, referencia_id) no mesmo dia */
    public function criar(array $data): int|false
    {
        try {
            // Prevenir duplicata no mesmo dia
            if (!empty($data['referencia_tipo']) && !empty($data['referencia_id'])) {
                $check = $this->pdo->prepare(
                    "SELECT id FROM {$this->table}
                     WHERE usuario_id = ? AND tipo = ? AND referencia_tipo = ? AND referencia_id = ?
                       AND DATE(created_at) = CURDATE()
                     LIMIT 1"
                );
                $check->execute([
                    $data['usuario_id'],
                    $data['tipo'],
                    $data['referencia_tipo'],
                    $data['referencia_id'],
                ]);
                if ($check->fetchColumn()) {
                    return false; // já existe hoje
                }
            }

            $tipo  = self::TIPOS[$data['tipo']] ?? [];
            $icone = $data['icone'] ?? ($tipo['icone'] ?? 'fas fa-bell');
            $cor   = $data['cor']   ?? ($tipo['cor']   ?? 'primary');

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, tipo, titulo, mensagem, link, icone, cor, referencia_tipo, referencia_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ok = $stmt->execute([
                $data['usuario_id'],
                $data['tipo'],
                $data['titulo'],
                $data['mensagem'] ?? null,
                $data['link']     ?? null,
                $icone,
                $cor,
                $data['referencia_tipo'] ?? null,
                $data['referencia_id']   ?? null,
            ]);

            return $ok ? (int) $this->pdo->lastInsertId() : false;
        } catch (\Throwable $e) {
            error_log('[Notificacao] criar: ' . $e->getMessage());
            return false;
        }
    }

    /** Marca uma notificação como lida */
    public function marcarLida(int $id, int $usuarioId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET lida = 1, lida_em = NOW()
                 WHERE id = ? AND usuario_id = ?"
            );
            return $stmt->execute([$id, $usuarioId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Marca todas as notificações do usuário como lidas */
    public function marcarTodasLidas(int $usuarioId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET lida = 1, lida_em = NOW()
                 WHERE usuario_id = ? AND lida = 0"
            );
            return $stmt->execute([$usuarioId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Exclui notificações antigas (mais de 60 dias) */
    public function limparAntigas(int $diasRetencao = 60): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table}
                 WHERE lida = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            $stmt->execute([$diasRetencao]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
