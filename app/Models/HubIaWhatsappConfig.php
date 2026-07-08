<?php

namespace App\Models;

use App\Core\Model;
use App\Services\CryptoService;

/**
 * Placeholder de configuração do WhatsApp para o HUB I.A.
 * Sem envio/recebimento real nesta fase — apenas persiste os dados de
 * configuração para uma integração futura (ver App\Services\AI\WhatsAppAI).
 */
class HubIaWhatsappConfig extends Model
{
    protected string $table = 'hub_ia_whatsapp_config';

    public function get(): object
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return (object) ['id' => 0, 'numero' => null, 'webhook_url' => null, 'status' => 'desconectado'];
    }

    public function salvar(array $d): bool
    {
        try {
            $tokenEnc = null;
            if (!empty($d['token']) && $d['token'] !== '********') {
                $tokenEnc = (new CryptoService())->encryptString($d['token']);
            }

            $atual = $this->get();
            if ($atual->id) {
                $setToken = $tokenEnc !== null ? ', token_enc = :token_enc' : '';
                $params = [':numero' => $d['numero'] ?? null, ':webhook' => $d['webhook_url'] ?? null, ':id' => $atual->id];
                if ($tokenEnc !== null) {
                    $params[':token_enc'] = $tokenEnc;
                }
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->table} SET numero = :numero, webhook_url = :webhook{$setToken} WHERE id = :id"
                );
                return $stmt->execute($params);
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (numero, token_enc, webhook_url, status) VALUES (:numero, :token_enc, :webhook, 'desconectado')"
            );
            return $stmt->execute([
                ':numero'    => $d['numero'] ?? null,
                ':token_enc' => $tokenEnc,
                ':webhook'   => $d['webhook_url'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('[HubIaWhatsappConfig::salvar] ' . $e->getMessage());
            return false;
        }
    }
}
