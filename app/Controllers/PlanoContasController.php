<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Models\PlanoConta;
use App\Services\PlanoContasPadraoService;

class PlanoContasController extends Controller
{
    private PlanoConta $model;
    private PlanoContasPadraoService $defaultPlanService;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new PlanoConta();
        $this->defaultPlanService = new PlanoContasPadraoService();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        try {
            $user = $this->currentUser();
            $filtros = [
                'status' => $_GET['status'] ?? 'ativo',
                'tipo' => $_GET['tipo'] ?? '',
                'pesquisa' => $_GET['q'] ?? '',
            ];
            $contas = $this->model->findByTenantId((int) $user->tenant_id, $filtros);

            View::render('plano_contas/index', [
                '_layout' => 'erp',
                'title' => 'Plano de Contas',
                'breadcrumb' => ['Financeiro' => '/financeiro/pagar', 0 => 'Plano de Contas'],
                'contas' => $contas,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao listar plano de contas: ' . $exception->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        $user = $this->currentUser();
        $contasPai = $this->model->listAtivasParaPaiByTenant((int) $user->tenant_id);

        View::render('plano_contas/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Nova Conta do Tenant',
            'conta' => null,
            'contasPai' => $contasPai,
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $this->assertCsrf();
            $user = $this->currentUser();
            $data = $this->validatedInput();
            if ($data === null) {
                header('Location: /financeiro/plano-contas/create?error=missing_fields');
                exit();
            }
            if ($this->model->findByTenantAndCode((int) $user->tenant_id, $data['codigo'])) {
                header('Location: /financeiro/plano-contas/create?error=duplicate_code');
                exit();
            }

            $data['tenant_id'] = (int) $user->tenant_id;
            $data['usuario_id'] = (int) $user->id;
            $id = $this->model->createForTenant($data);
            if (!$id) {
                header('Location: /financeiro/plano-contas/create?error=db_failure');
                exit();
            }

            AuditLogger::log('create_plano_conta', [
                'id' => $id,
                'tenant_id' => (int) $user->tenant_id,
                'codigo' => $data['codigo'],
                'nome' => $data['nome'],
            ]);
            header("Location: /financeiro/plano-contas/edit/{$id}?success=created");
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao criar plano de conta: ' . $exception->getMessage());
            header('Location: /financeiro/plano-contas/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $user = $this->currentUser();
        $conta = $this->model->findByIdForTenant((int) $id, (int) $user->tenant_id);
        if (!$conta) {
            header('Location: /financeiro/plano-contas?error=not_found');
            exit();
        }

        $contasPai = $this->model->listAtivasParaPaiByTenant((int) $user->tenant_id, (int) $conta->id);
        View::render('plano_contas/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Editar Plano de Contas',
            'conta' => $conta,
            'contasPai' => $contasPai,
            'tab' => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $this->assertCsrf();
            $user = $this->currentUser();
            $conta = $this->model->findByIdForTenant((int) $id, (int) $user->tenant_id);
            if (!$conta) {
                header('Location: /financeiro/plano-contas?error=unauthorized');
                exit();
            }
            $data = $this->validatedInput();
            if ($data === null) {
                header("Location: /financeiro/plano-contas/edit/{$id}?error=missing_fields");
                exit();
            }
            $duplicate = $this->model->findByTenantAndCode((int) $user->tenant_id, $data['codigo']);
            if ($duplicate && (int) $duplicate->id !== (int) $id) {
                header("Location: /financeiro/plano-contas/edit/{$id}?error=duplicate_code");
                exit();
            }

            if (!$this->model->updateForTenant((int) $id, (int) $user->tenant_id, $data)) {
                header("Location: /financeiro/plano-contas/edit/{$id}?error=db_failure");
                exit();
            }

            AuditLogger::log('update_plano_conta', [
                'id' => (int) $id,
                'tenant_id' => (int) $user->tenant_id,
                'codigo' => $data['codigo'],
                'nome' => $data['nome'],
            ]);
            header("Location: /financeiro/plano-contas/edit/{$id}?success=updated");
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao atualizar plano de conta: ' . $exception->getMessage());
            header("Location: /financeiro/plano-contas/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $this->assertCsrf();
            $user = $this->currentUser();
            $conta = $this->model->findByIdForTenant((int) $id, (int) $user->tenant_id);
            if (!$conta) {
                header('Location: /financeiro/plano-contas?error=unauthorized');
                exit();
            }
            if (!$this->model->deleteForTenant((int) $id, (int) $user->tenant_id)) {
                header('Location: /financeiro/plano-contas?error=db_failure');
                exit();
            }

            AuditLogger::log('delete_plano_conta', [
                'id' => (int) $id,
                'tenant_id' => (int) $user->tenant_id,
                'codigo' => $conta->codigo,
            ]);
            header('Location: /financeiro/plano-contas?success=deleted');
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao inativar plano de conta: ' . $exception->getMessage());
            header('Location: /financeiro/plano-contas?error=fatal');
        }
        exit();
    }

    /**
     * Acrescenta exclusivamente contas do modelo ainda ausentes no tenant.
     */
    public function importDefault(): void
    {
        try {
            $this->assertCsrf();
            $user = $this->currentUser();
            $result = $this->defaultPlanService->seedForTenant((int) $user->tenant_id, (int) $user->id);
            AuditLogger::log('import_plano_contas_padrao', [
                'tenant_id' => (int) $user->tenant_id,
                'user_id' => (int) $user->id,
                'inserted' => $result['inserted'],
                'skipped' => $result['skipped'],
            ]);
            header('Location: /financeiro/plano-contas?success=default_imported&inserted=' . (int) $result['inserted']);
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao importar plano de contas padrão: ' . $exception->getMessage());
            header('Location: /financeiro/plano-contas?error=default_import_failed');
        }
        exit();
    }

    private function currentUser(): object
    {
        $user = Auth::user();
        if (!$user || (int) ($user->tenant_id ?? 0) <= 0) {
            throw new \RuntimeException('Tenant da sessão não identificado.');
        }
        return $user;
    }

    private function validatedInput(): ?array
    {
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = (string) ($_POST['tipo'] ?? '');
        if ($codigo === '' || $nome === '' || !in_array($tipo, ['Receita', 'Despesa'], true)) {
            return null;
        }

        return [
            'codigo' => $codigo,
            'nome' => $nome,
            'tipo' => $tipo,
            'conta_pai_id' => ($_POST['conta_pai_id'] ?? '') !== '' ? (int) $_POST['conta_pai_id'] : null,
            'status' => ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo',
        ];
    }

    private function assertCsrf(): void
    {
        $submitted = (string) ($_POST['csrf_token'] ?? '');
        if ($submitted === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $submitted)) {
            throw new \RuntimeException('Token CSRF inválido.');
        }
    }
}
