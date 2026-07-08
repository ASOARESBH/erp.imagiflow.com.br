<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class HubIaAgentePermissao extends Model
{
    protected string $table = 'hub_ia_agente_permissoes';

    public const MODULOS = ['crm', 'financeiro', 'rdv', 'marketing', 'cnes', 'estoque', 'rh', 'configuracoes'];

    public function listarPorAgente(int $agenteId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE agente_id = ?");
        $stmt->execute([$agenteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $mapa = [];
        foreach (self::MODULOS as $m) {
            $mapa[$m] = false;
        }
        foreach ($rows as $r) {
            $mapa[$r->modulo] = (bool) $r->permitido;
        }
        return $mapa;
    }

    /**
     * Substitui todas as permissões do agente pelas informadas (upsert em lote).
     * @param array<string,bool> $permissoes ex.: ['crm' => true, 'rh' => false]
     */
    public function salvar(int $agenteId, array $permissoes): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (agente_id, modulo, permitido) VALUES (:agente_id, :modulo, :permitido)
                 ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)"
            );
            foreach (self::MODULOS as $modulo) {
                $stmt->execute([
                    ':agente_id' => $agenteId,
                    ':modulo'    => $modulo,
                    ':permitido' => !empty($permissoes[$modulo]) ? 1 : 0,
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[HubIaAgentePermissao::salvar] ' . $e->getMessage());
            return false;
        }
    }

    public function podeAcessarModulo(int $agenteId, string $modulo): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT permitido FROM {$this->table} WHERE agente_id = :a AND modulo = :m"
        );
        $stmt->execute([':a' => $agenteId, ':m' => $modulo]);
        $row = $stmt->fetch();
        return $row ? (bool) $row->permitido : false;
    }
}
