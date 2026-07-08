<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\HubIaWhatsappConfig;

/**
 * Tela de configuração do WhatsApp — apenas persistência dos dados, sem
 * envio/recebimento real nesta fase (ver App\Services\AI\WhatsAppAI).
 */
class HubIaWhatsappController extends Controller
{
    private HubIaWhatsappConfig $model;

    public function __construct()
    {
        $this->model = new HubIaWhatsappConfig();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        View::render('hub_ia.whatsapp', [
            'title'      => 'HUB I.A — WhatsApp',
            'config'     => $this->model->get(),
            'breadcrumb' => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'WhatsApp' => '/hub-ia/whatsapp'],
            '_layout'    => 'erp',
        ]);
    }

    public function salvar(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $ok = $this->model->salvar($_POST);
        if ($ok) {
            AuditLogger::log('hub_ia_whatsapp_config_salva', []);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }
}
