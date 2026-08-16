<?php

namespace App\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mail;
use App\Core\View;
use App\Models\Plano;
use App\Models\Tenant;
use App\Services\CepService;
use App\Services\CnpjService;
use App\Services\SaasAdminService;
use App\Services\SaasImpersonationService;
use RuntimeException;

class SaasEmpresasController extends Controller
{
    private Tenant $tenantModel;
    private Plano $planoModel;
    private SaasAdminService $saasService;
    private SaasImpersonationService $impersonationService;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->planoModel = new Plano();
        $this->saasService = new SaasAdminService();
        $this->impersonationService = new SaasImpersonationService();
    }

    public function index(): void
    {
        View::render('saas_admin/empresas/index', [
            'title' => 'Empresas SaaS',
            'breadcrumb' => ['Painel SaaS' => '/painel', 0 => 'Empresas'],
            'empresas' => $this->tenantModel->listAllWithPlan(),
            '_layout' => 'erp',
        ]);
    }

    public function create(): void
    {
        $this->renderForm(null, false);
    }

    public function edit(int $id): void
    {
        $empresa = $this->tenantModel->findById($id);
        if (!$empresa) {
            header('Location: /painel/empresas?error=not_found');
            exit();
        }
        $this->renderForm($empresa, true);
    }

    public function store(): void
    {
        $this->assertCsrf();
        try {
            $company = $this->sanitizeCompanyRequest();
            $master = [
                'name' => trim(strip_tags((string) ($_POST['master_name'] ?? ''))),
                'email' => strtolower(trim((string) ($_POST['master_email'] ?? ''))),
            ];
            if ($company['razao_social'] === '' || $company['nome_fantasia'] === '' || $master['name'] === '' || $master['email'] === '') {
                throw new RuntimeException('Preencha os dados obrigatórios da empresa e do usuário master.');
            }

            $result = $this->saasService->provisionCompany($company, $master, (int) Auth::user()->id);
            $tenant = $this->tenantModel->findById((int) $result['tenant_id']);
            $inviteDelivery = 'sent';
            try {
                if (!$tenant) {
                    throw new RuntimeException('Tenant recém-criado não encontrado para gerar o convite.');
                }
                $inviteUrl = 'https://' . $this->tenantHost($tenant) . '/reset-password/' . rawurlencode($result['invite_token']);
                if (!Mail::sendPasswordResetLink($master['email'], $inviteUrl, (int) $result['master_user_id'])) {
                    $inviteDelivery = 'failed';
                    AuditLogger::log('saas_master_invite_delivery_failed', [
                        'tenant_id' => $result['tenant_id'],
                        'master_user_id' => $result['master_user_id'],
                    ]);
                }
            } catch (\Throwable $mailException) {
                $inviteDelivery = 'failed';
                AuditLogger::log('saas_master_invite_delivery_exception', [
                    'tenant_id' => $result['tenant_id'],
                    'master_user_id' => $result['master_user_id'],
                    'error' => $mailException->getMessage(),
                ]);
            }

            header('Location: /painel/empresas/edit/' . (int) $result['tenant_id'] . '?success=created&invite=' . $inviteDelivery);
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_company_store_exception', [
                'saas_admin_user_id' => Auth::user()->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            header('Location: /painel/empresas/create?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    public function update(int $id): void
    {
        $this->assertCsrf();
        try {
            $company = $this->sanitizeCompanyRequest();
            $current = $this->tenantModel->findById($id);
            if (!$current) {
                throw new RuntimeException('Empresa não encontrada.');
            }
            $company['status'] = (string) $current->status;
            $this->saasService->updateCompany($id, $company);
            header('Location: /painel/empresas/edit/' . $id . '?success=updated');
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_company_update_exception', [
                'tenant_id' => $id,
                'saas_admin_user_id' => Auth::user()->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            header('Location: /painel/empresas/edit/' . $id . '?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    public function toggleStatus(int $id): void
    {
        $this->assertCsrf();
        try {
            $status = (string) ($_POST['status'] ?? '');
            $this->saasService->changeCompanyStatus($id, $status, (int) Auth::user()->id);
            header('Location: /painel/empresas?success=status_updated');
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_company_status_exception', [
                'tenant_id' => $id,
                'error' => $exception->getMessage(),
            ]);
            header('Location: /painel/empresas?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    public function buscarCnpj(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cnpj = preg_replace('/\D/', '', (string) ($_GET['cnpj'] ?? ''));
        if (strlen($cnpj) !== 14) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CNPJ inválido.']);
            exit();
        }

        try {
            $result = (new CnpjService())->consultar($cnpj);
            if (isset($result['erro'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => $result['erro']]);
                exit();
            }
            AuditLogger::log('saas_company_cnpj_lookup', ['cnpj' => $cnpj]);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_company_cnpj_lookup_failed', ['cnpj' => $cnpj, 'error' => $exception->getMessage()]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível consultar o CNPJ.']);
        }
        exit();
    }

    public function buscarCep(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cep = preg_replace('/\D/', '', (string) ($_GET['cep'] ?? ''));
        if (strlen($cep) !== 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CEP inválido.']);
            exit();
        }

        try {
            $result = (new CepService())->consultar($cep);
            if (isset($result['erro'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => $result['erro']]);
                exit();
            }
            AuditLogger::log('saas_company_cep_lookup', ['cep' => $cep]);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_company_cep_lookup_failed', ['cep' => $cep, 'error' => $exception->getMessage()]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível consultar o CEP.']);
        }
        exit();
    }

    public function impersonar(int $id): void
    {
        $this->assertCsrf();
        try {
            $handoff = $this->impersonationService->createHandoff(
                $id,
                (int) Auth::user()->id,
                trim(strip_tags((string) ($_POST['reason'] ?? 'Suporte SaaS')))
            );
            $_SESSION['saas_impersonation_pending'] = [
                'log_id' => (int) $handoff['log_id'],
                'target_tenant_id' => $id,
                'return_token' => (string) $handoff['return_token'],
                'started_at' => time(),
            ];
            $host = $this->tenantHost($handoff['target_tenant']);
            $url = 'https://' . $host . '/painel/impersonacao/entrar/' . rawurlencode($handoff['entry_token'])
                . '?return=' . rawurlencode($handoff['return_token']);
            header('Location: ' . $url);
        } catch (\Throwable $exception) {
            AuditLogger::log('saas_impersonation_start_exception', [
                'target_tenant_id' => $id,
                'error' => $exception->getMessage(),
            ]);
            header('Location: /painel/empresas?error=' . rawurlencode($exception->getMessage()));
        }
        exit();
    }

    private function renderForm(?object $empresa, bool $isEdit): void
    {
        View::render('saas_admin/empresas/form-enterprise', [
            'title' => $isEdit ? 'Editar Empresa SaaS' : 'Nova Empresa SaaS',
            'empresa' => $empresa,
            'isEdit' => $isEdit,
            'planos' => $this->planoModel->listActive(),
            '_layout' => 'erp',
        ]);
    }

    private function sanitizeCompanyRequest(): array
    {
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        // O domínio externo é único (erp.imagiflow.com.br). Cada tenant recebe
        // apenas um identificador técnico único, que nunca exige DNS público.
        $internalDomain = $slug !== '' ? 'tenant-' . $slug . '.internal' : null;

        return [
            'name' => trim(strip_tags((string) ($_POST['nome_fantasia'] ?? ''))),
            'slug' => $slug,
            'domain' => $internalDomain,
            'subdomain' => null,
            'status' => 'active',
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))) ?: null,
            'phone' => trim(strip_tags((string) ($_POST['telefone'] ?? ''))) ?: null,
            'cnpj' => preg_replace('/\D/', '', (string) ($_POST['cnpj'] ?? '')),
            'razao_social' => trim(strip_tags((string) ($_POST['razao_social'] ?? ''))),
            'nome_fantasia' => trim(strip_tags((string) ($_POST['nome_fantasia'] ?? ''))),
            'endereco' => trim(strip_tags((string) ($_POST['endereco'] ?? ''))) ?: null,
            'numero' => trim(strip_tags((string) ($_POST['numero'] ?? ''))) ?: null,
            'complemento' => trim(strip_tags((string) ($_POST['complemento'] ?? ''))) ?: null,
            'bairro' => trim(strip_tags((string) ($_POST['bairro'] ?? ''))) ?: null,
            'cidade' => trim(strip_tags((string) ($_POST['cidade'] ?? ''))) ?: null,
            'estado' => strtoupper(substr(trim((string) ($_POST['estado'] ?? '')), 0, 2)) ?: null,
            'cep' => preg_replace('/\D/', '', (string) ($_POST['cep'] ?? '')) ?: null,
            'plano_id' => (int) ($_POST['plano_id'] ?? 0) ?: null,
            'plano_iniciado_em' => null,
            'trial_ends_at' => trim((string) ($_POST['trial_ends_at'] ?? '')) ?: null,
            'billing_email' => strtolower(trim((string) ($_POST['billing_email'] ?? ''))) ?: null,
            'created_by_saas_admin_id' => (int) (Auth::user()->id ?? 0),
            'notes' => trim(strip_tags((string) ($_POST['notes'] ?? ''))) ?: null,
        ];
    }

    private function assertCsrf(): void
    {
        $submitted = (string) ($_POST['csrf_token'] ?? '');
        if ($submitted === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $submitted)) {
            http_response_code(419);
            exit('419 - Token CSRF inválido');
        }
    }

    /**
     * Todos os convites e handoffs SaaS usam o mesmo domínio ERP. O tenant é
     * selecionado por vínculo autenticado e token, nunca por host do cliente.
     */
    private function tenantHost(object $tenant): string
    {
        $host = strtolower(trim((string) ($_ENV['SAAS_SHARED_HOST'] ?? '')));
        if ($host === '') {
            $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
            $host = preg_replace('/:\\d+$/', '', $host) ?? '';
        }
        if ($host === '') {
            throw new RuntimeException('Domínio compartilhado do ERP não configurado.');
        }
        return $host;
    }
}
