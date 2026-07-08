<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\HubIaAgente;
use App\Models\HubIaAgentePermissao;
use App\Models\HubIaConector;
use App\Models\HubIaPrompt;

class HubIaAgentesController extends Controller
{
    private HubIaAgente $model;
    private HubIaAgentePermissao $permissaoModel;

    public function __construct()
    {
        $this->model          = new HubIaAgente();
        $this->permissaoModel = new HubIaAgentePermissao();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $agentes = $this->model->listar();
        $permissoesPorAgente = [];
        foreach ($agentes as $a) {
            $permissoesPorAgente[$a->id] = $this->permissaoModel->listarPorAgente((int) $a->id);
        }

        View::render('hub_ia.agentes', [
            'title'               => 'HUB I.A — Robôs IA',
            'agentes'             => $agentes,
            'permissoesPorAgente' => $permissoesPorAgente,
            'conectores'          => (new HubIaConector())->listar(),
            'prompts'             => (new HubIaPrompt())->listar(true),
            'modulos'             => HubIaAgentePermissao::MODULOS,
            'breadcrumb'          => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Robôs IA' => '/hub-ia/agentes'],
            '_layout'             => 'erp',
        ]);
    }

    public function store(): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }
        if (empty(trim($_POST['nome'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'Informe o nome do agente.']);
            exit();
        }

        $id = $this->model->create((int) Auth::user()->id, $_POST);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar o agente.']);
            exit();
        }

        $this->salvarPermissoesDoPost($id);
        AuditLogger::log('hub_ia_agente_criado', ['id' => $id]);
        echo json_encode(['success' => true, 'id' => $id]);
        exit();
    }

    public function update(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::can('manage_hub_ia')) {
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit();
        }
        if (empty(trim($_POST['nome'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'Informe o nome do agente.']);
            exit();
        }

        $ok = $this->model->update($id, $_POST);
        if ($ok) {
            $this->salvarPermissoesDoPost($id);
            AuditLogger::log('hub_ia_agente_atualizado', ['id' => $id]);
        }
        echo json_encode(['success' => $ok]);
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
            AuditLogger::log('hub_ia_agente_excluido', ['id' => $id]);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }

    private function salvarPermissoesDoPost(int $agenteId): void
    {
        $permissoes = [];
        foreach (HubIaAgentePermissao::MODULOS as $modulo) {
            $permissoes[$modulo] = !empty($_POST['perm_' . $modulo]);
        }
        $this->permissaoModel->salvar($agenteId, $permissoes);
    }
}
