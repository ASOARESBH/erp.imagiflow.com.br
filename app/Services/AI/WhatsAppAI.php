<?php

namespace App\Services\AI;

use App\Models\HubIaWhatsappConfig;

/**
 * Placeholder de arquitetura para a futura integração EVA ↔ WhatsApp, pedida
 * explicitamente como "preparar arquitetura para futura integração" — sem
 * envio/recebimento real nesta fase. Fluxo alvo (ver prompt original):
 * Cliente → WhatsApp → Webhook → HUB IA → Modelo IA → Banco → Resposta → WhatsApp.
 */
class WhatsAppAI
{
    private HubIaWhatsappConfig $configModel;

    public function __construct()
    {
        $this->configModel = new HubIaWhatsappConfig();
    }

    public function isConfigurado(): bool
    {
        $cfg = $this->configModel->get();
        return $cfg->status === 'conectado';
    }

    /**
     * Ponto de entrada futuro para o webhook do provedor de WhatsApp
     * (ex.: Meta Cloud API). Não implementado nesta fase.
     */
    public function receberWebhook(array $payload): array
    {
        return ['sucesso' => false, 'erro' => 'Integração com WhatsApp ainda não implementada — arquitetura preparada para fase futura.'];
    }

    /**
     * Ponto de entrada futuro para enviar uma resposta da EVA de volta ao
     * WhatsApp do cliente. Não implementado nesta fase.
     */
    public function enviarMensagem(string $numeroDestino, string $texto): array
    {
        return ['sucesso' => false, 'erro' => 'Integração com WhatsApp ainda não implementada — arquitetura preparada para fase futura.'];
    }
}
