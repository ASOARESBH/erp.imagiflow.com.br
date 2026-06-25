<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class NotificacaoConfigAlerta
{
    private PDO $pdo;
    private string $table = 'notificacao_config_alertas';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /** Retorna todas as configurações de alertas do usuário indexadas por tipo */
    public function findByUsuario(int $usuarioId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$this->table} WHERE usuario_id = ?"
            );
            $stmt->execute([$usuarioId]);
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

            $indexed = [];
            foreach ($rows as $row) {
                $indexed[$row->tipo] = $row;
            }
            return $indexed;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Verifica se um tipo de alerta está ativo para o usuário */
    public function isAtivo(int $usuarioId, string $tipo): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT ativo FROM {$this->table} WHERE usuario_id = ? AND tipo = ? LIMIT 1"
            );
            $stmt->execute([$usuarioId, $tipo]);
            $row = $stmt->fetchColumn();

            // Se não existe configuração, considera ativo por padrão
            if ($row === false) {
                return true;
            }
            return (bool) $row;
        } catch (\Throwable $e) {
            return true; // padrão: ativo
        }
    }

    /** Retorna os dias de antecedência configurados para um tipo */
    public function getDiasAntecedencia(int $usuarioId, string $tipo): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT dias_antecedencia FROM {$this->table} WHERE usuario_id = ? AND tipo = ? LIMIT 1"
            );
            $stmt->execute([$usuarioId, $tipo]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? (int) $val : 3; // padrão: 3 dias
        } catch (\Throwable $e) {
            return 3;
        }
    }

    /** Salva (insert ou update) a configuração de um tipo de alerta */
    public function salvar(int $usuarioId, string $tipo, bool $ativo, int $diasAntecedencia = 3): bool
    {
        try {
            // Verificar se já existe
            $check = $this->pdo->prepare(
                "SELECT id FROM {$this->table} WHERE usuario_id = ? AND tipo = ? LIMIT 1"
            );
            $check->execute([$usuarioId, $tipo]);
            $existeId = $check->fetchColumn();

            if ($existeId) {
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->table}
                     SET ativo = ?, dias_antecedencia = ?, updated_at = NOW()
                     WHERE id = ?"
                );
                return $stmt->execute([$ativo ? 1 : 0, $diasAntecedencia, $existeId]);
            } else {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO {$this->table} (usuario_id, tipo, ativo, dias_antecedencia)
                     VALUES (?, ?, ?, ?)"
                );
                return $stmt->execute([$usuarioId, $tipo, $ativo ? 1 : 0, $diasAntecedencia]);
            }
        } catch (\Throwable $e) {
            error_log('[NotificacaoConfigAlerta] salvar: ' . $e->getMessage());
            return false;
        }
    }

    /** Salva em lote todas as configurações de uma vez (enviadas pelo formulário) */
    public function salvarLote(int $usuarioId, array $configs): bool
    {
        try {
            foreach ($configs as $tipo => $cfg) {
                $this->salvar(
                    $usuarioId,
                    $tipo,
                    (bool) ($cfg['ativo'] ?? false),
                    (int)  ($cfg['dias']  ?? 3)
                );
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[NotificacaoConfigAlerta] Erro ao salvar lote: ' . $e->getMessage());
            return false;
        }
    }
}
