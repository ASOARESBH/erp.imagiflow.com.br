<?php

namespace App\Models;

use App\Core\Model;

/**
 * Configuração de acesso da IA ao banco de dados (NL→SQL).
 * Sempre opera sobre a conexão já configurada em .env (Database::getInstance());
 * esta tela só controla QUAIS tabelas/views ficam liberadas para consulta,
 * nunca credenciais de conexão alternativas.
 */
class HubIaBancoConfig extends Model
{
    protected string $table = 'hub_ia_banco_config';

    public function get(): object
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return (object) ['id' => 0, 'tabelas_liberadas' => '[]', 'ativo' => 0];
    }

    public function salvar(array $tabelas, bool $ativo): bool
    {
        try {
            $tabelasJson = json_encode(array_values(array_unique(array_filter(array_map('trim', $tabelas)))));
            $atual = $this->get();

            if ($atual->id) {
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->table} SET tabelas_liberadas = :t, ativo = :a WHERE id = :id"
                );
                return $stmt->execute([':t' => $tabelasJson, ':a' => $ativo ? 1 : 0, ':id' => $atual->id]);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (tabelas_liberadas, ativo) VALUES (:t, :a)"
            );
            return $stmt->execute([':t' => $tabelasJson, ':a' => $ativo ? 1 : 0]);
        } catch (\Throwable $e) {
            error_log('[HubIaBancoConfig::salvar] ' . $e->getMessage());
            return false;
        }
    }

    public function getTabelasLiberadas(): array
    {
        $cfg = $this->get();
        $tabelas = json_decode((string) $cfg->tabelas_liberadas, true);
        return is_array($tabelas) ? $tabelas : [];
    }

    public function isAtivo(): bool
    {
        return (bool) $this->get()->ativo;
    }
}
