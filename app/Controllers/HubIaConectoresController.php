<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Models\HubIaConector;
use App\Services\AI\AIService;

class HubIaConectoresController extends Controller
{
    private HubIaConector $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model  = new HubIaConector();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        View::render('hub_ia.conectores', [
            'title'      => 'HUB I.A — Conectores',
            'conectores' => $this->model->listar(),
            'providers'  => HubIaConector::PROVIDERS,
            'mask'       => HubIaConector::MASK,
            'breadcrumb' => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Conectores' => '/hub-ia/conectores'],
            '_layout'    => 'erp',
        ]);
    }

    public function store(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $erro = $this->validar($_POST);
        if ($erro) {
            echo json_encode(['success' => false, 'error' => $erro]);
            exit();
        }

        $id = $this->model->create((int) Auth::user()->id, $_POST);
        if ($id) {
            AuditLogger::log('hub_ia_conector_criado', ['id' => $id, 'provider' => $_POST['provider']]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar o conector.']);
        }
        exit();
    }

    public function update(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $erro = $this->validar($_POST);
        if ($erro) {
            echo json_encode(['success' => false, 'error' => $erro]);
            exit();
        }

        $ok = $this->model->update($id, $_POST);
        if ($ok) {
            AuditLogger::log('hub_ia_conector_atualizado', ['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao atualizar o conector.']);
        }
        exit();
    }

    public function delete(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $ok = $this->model->delete($id);
        if ($ok) {
            AuditLogger::log('hub_ia_conector_excluido', ['id' => $id]);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }

    public function testar(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }

        $conector = $this->model->findById($id);
        if (!$conector) {
            echo json_encode(['success' => false, 'error' => 'Conector não encontrado.']);
            exit();
        }

        try {
            $resultado = (new AIService())->testarConector($conector);
        } catch (\Throwable $e) {
            $this->logger->error('[HubIaConectoresController::testar] ' . $e->getMessage());
            $resultado = ['sucesso' => false, 'mensagem' => 'Erro inesperado ao testar conexão.'];
        }

        $this->model->updateTesteResultado($id, $resultado['sucesso'], $resultado['mensagem']);
        AuditLogger::log('hub_ia_conector_testado', ['id' => $id, 'sucesso' => $resultado['sucesso']]);

        echo json_encode(['success' => $resultado['sucesso'], 'message' => $resultado['mensagem']]);
        exit();
    }

    private function validar(array $d): ?string
    {
        if (empty(trim($d['nome'] ?? ''))) {
            return 'Informe um nome para o conector.';
        }
        if (empty($d['provider']) || !in_array($d['provider'], HubIaConector::PROVIDERS, true)) {
            return 'Selecione um provedor válido.';
        }
        return null;
    }
}
