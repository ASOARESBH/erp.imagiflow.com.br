<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\HubIaPrompt;

class HubIaPromptsController extends Controller
{
    private HubIaPrompt $model;

    public function __construct()
    {
        $this->model = new HubIaPrompt();
    }

    public function index(): void
    {
        if (!Auth::can('view_hub_ia')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        View::render('hub_ia.prompts', [
            'title'      => 'HUB I.A — Prompts',
            'prompts'    => $this->model->listar(),
            'breadcrumb' => ['Configurações' => '/configuracoes', 'HUB I.A' => '/hub-ia', 'Prompts' => '/hub-ia/prompts'],
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
        if (empty(trim($_POST['nome'] ?? '')) || empty(trim($_POST['conteudo'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'Informe nome e conteúdo do prompt.']);
            exit();
        }

        $id = $this->model->create((int) Auth::user()->id, $_POST);
        if ($id) {
            AuditLogger::log('hub_ia_prompt_criado', ['id' => $id]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao salvar o prompt.']);
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
        if (empty(trim($_POST['nome'] ?? '')) || empty(trim($_POST['conteudo'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'Informe nome e conteúdo do prompt.']);
            exit();
        }

        $ok = $this->model->update($id, $_POST);
        if ($ok) {
            AuditLogger::log('hub_ia_prompt_atualizado', ['id' => $id]);
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
            AuditLogger::log('hub_ia_prompt_excluido', ['id' => $id]);
        }
        echo json_encode(['success' => $ok]);
        exit();
    }
}
