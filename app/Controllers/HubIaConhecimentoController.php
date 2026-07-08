<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Models\HubIaConector;
use App\Models\HubIaDocumento;
use App\Services\AI\KnowledgeBaseService;

class HubIaConhecimentoController extends Controller
{
    private HubIaDocumento $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model  = new HubIaDocumento();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        View::render('hub_ia.conhecimento', [
            'title'      => 'HUB I.A — Base de Conhecimento',
            'documentos' => $this->model->listar(),
            'breadcrumb' => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Base de Conhecimento' => '/hub-ia/conhecimento'],
            '_layout'    => 'erp',
        ]);
    }

    public function upload(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $kb = new KnowledgeBaseService();
        $resultado = $kb->upload($_FILES['arquivo'] ?? [], (int) Auth::user()->id, $_POST['categoria'] ?? null);

        if (!$resultado['sucesso']) {
            echo json_encode(['success' => false, 'error' => $resultado['erro']]);
            exit();
        }

        // Tenta gerar embeddings automaticamente se houver um conector OpenAI ativo com chave configurada
        $conectorModel = new HubIaConector();
        $conectorOpenAI = $conectorModel->findAtivoPorProvider('openai');
        $apiKey = $conectorOpenAI ? $conectorModel->getApiKeyPlain($conectorOpenAI) : null;

        try {
            $kb->processar($resultado['documento_id'], $conectorOpenAI ?: null, $apiKey);
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaConhecimentoController::upload] processar: ' . $e->getMessage());
        }

        AuditLogger::log('hub_ia_documento_upload', ['id' => $resultado['documento_id']]);
        echo json_encode(['success' => true, 'documento_id' => $resultado['documento_id']]);
        exit();
    }

    public function delete(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $ok = (new KnowledgeBaseService())->excluir($id);
        if ($ok) {
            AuditLogger::log('hub_ia_documento_excluido', ['id' => $id]);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }
}
