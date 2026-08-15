<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Plano;
use App\Models\PlanoModulo;
use RuntimeException;

class SaasPlanosController extends Controller
{
    /** @var array<string, string> */
    private const AVAILABLE_MODULES = [
        'dashboard' => 'Dashboard',
        'clientes' => 'Clientes',
        'colaboradores' => 'Colaboradores',
        'medicos' => 'Médicos e Corpo Clínico',
        'contratos_apuracao' => 'Contratos e Apuração',
        'financeiro_receber' => 'Contas a Receber',
        'financeiro_pagar' => 'Contas a Pagar e Plano de Contas',
        'financeiro_bancario' => 'Contas Bancárias e Open Finance',
        'fornecedores' => 'Fornecedores',
        'faturamento_nf' => 'Notas Fiscais',
        'estoque' => 'Estoque',
        'crm' => 'CRM',
        'rdv' => 'RDV',
        'manutencao' => 'Manutenção',
        'marketing' => 'Marketing',
        'portal_cliente' => 'Portal do Cliente',
        'cnes' => 'CNES/DataSUS',
        'integracoes_pagamento' => 'Integrações de Pagamento',
        'hub_ia' => 'Hub IA',
    ];

    private Plano $planoModel;
    private PlanoModulo $planoModuloModel;

    public function __construct()
    {
        $this->planoModel = new Plano();
        $this->planoModuloModel = new PlanoModulo();
    }

    public function index(): void
    {
        View::render('saas_admin/planos/index', [
            'title' => 'Planos SaaS',
            'breadcrumb' => ['Painel SaaS' => '/saas-admin', 0 => 'Planos'],
            'planos' => $this->planoModel->listAll(),
            '_layout' => 'erp',
        ]);
    }

    public function create(): void
    {
        $this->renderForm(null);
    }

    public function edit(int $id): void
    {
        $plano = $this->planoModel->findById($id);
        if (!$plano) {
            header('Location: /saas-admin/planos?error=not_found');
            exit();
        }
        $this->renderForm($plano);
    }

    public function store(): void
    {
        $this->assertCsrf();
        try {
            $data = $this->sanitizeRequest();
            if ($this->planoModel->findBySlug($data['slug'])) {
                throw new RuntimeException('Já existe um plano com este slug.');
            }
            $pdo = $this->planoModel->getPdo();
            $pdo->beginTransaction();
            $id = $this->planoModel->create($data);
            $this->planoModuloModel->replaceForPlan($id, $data['modules']);
            $pdo->commit();
            AuditLogger::log('saas_plan_created', ['plan_id' => $id, 'created_by' => Auth::user()->id]);
            header('Location: /saas-admin/planos/edit/' . $id . '?success=created');
        } catch (\Throwable $exception) {
            if ($this->planoModel->getPdo()->inTransaction()) {
                $this->planoModel->getPdo()->rollBack();
            }
            AuditLogger::log('saas_plan_create_exception', ['error' => $exception->getMessage()]);
            header('Location: /saas-admin/planos/create?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    public function update(int $id): void
    {
        $this->assertCsrf();
        try {
            $existing = $this->planoModel->findById($id);
            if (!$existing) {
                throw new RuntimeException('Plano não encontrado.');
            }
            $data = $this->sanitizeRequest();
            $bySlug = $this->planoModel->findBySlug($data['slug']);
            if ($bySlug && (int) $bySlug->id !== $id) {
                throw new RuntimeException('Já existe outro plano com este slug.');
            }
            $pdo = $this->planoModel->getPdo();
            $pdo->beginTransaction();
            $this->planoModel->update($id, $data);
            $this->planoModuloModel->replaceForPlan($id, $data['modules']);
            $pdo->commit();
            AuditLogger::log('saas_plan_updated', ['plan_id' => $id, 'updated_by' => Auth::user()->id]);
            header('Location: /saas-admin/planos/edit/' . $id . '?success=updated');
        } catch (\Throwable $exception) {
            if ($this->planoModel->getPdo()->inTransaction()) {
                $this->planoModel->getPdo()->rollBack();
            }
            AuditLogger::log('saas_plan_update_exception', ['plan_id' => $id, 'error' => $exception->getMessage()]);
            header('Location: /saas-admin/planos/edit/' . $id . '?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    private function renderForm(?object $plano): void
    {
        $enabled = $plano ? $this->planoModuloModel->activeSlugsForPlan((int) $plano->id) : [];
        View::render('saas_admin/planos/form', [
            'title' => $plano ? 'Editar Plano SaaS' : 'Novo Plano SaaS',
            'plano' => $plano,
            'enabledModules' => $enabled,
            'availableModules' => self::AVAILABLE_MODULES,
            '_layout' => 'erp',
        ]);
    }

    private function sanitizeRequest(): array
    {
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        if (!preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
            throw new RuntimeException('Slug de plano inválido.');
        }
        $name = trim(strip_tags((string) ($_POST['nome'] ?? '')));
        if ($name === '') {
            throw new RuntimeException('Informe o nome do plano.');
        }
        $modules = array_values(array_intersect(
            array_keys(self::AVAILABLE_MODULES),
            array_map('strval', (array) ($_POST['modules'] ?? []))
        ));

        return [
            'slug' => $slug,
            'nome' => $name,
            'descricao' => trim(strip_tags((string) ($_POST['descricao'] ?? ''))),
            'preco_mensal' => max(0, (float) str_replace(',', '.', (string) ($_POST['preco_mensal'] ?? '0'))),
            'limite_usuarios' => ($_POST['limite_usuarios'] ?? '') === '' ? null : max(1, (int) $_POST['limite_usuarios']),
            'ordem' => max(0, (int) ($_POST['ordem'] ?? 0)),
            'status' => in_array($_POST['status'] ?? '', ['ativo', 'inativo'], true) ? $_POST['status'] : 'ativo',
            'modules' => $modules,
        ];
    }

    private function assertCsrf(): void
    {
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(419);
            exit('419 - Token CSRF inválido');
        }
    }
}
