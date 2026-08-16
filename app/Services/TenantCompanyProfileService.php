<?php

namespace App\Services;

use App\Core\Logger;
use App\Models\EmpresaConfig;
use App\Models\Tenant;

/**
 * Fonte única dos dados empresariais do tenant.
 *
 * Os dados cadastrais centrais são lidos da tabela tenants, criada pelo
 * painel SaaS ou atualizada pela aba Empresa. A empresa_config preserva
 * somente os campos complementares do ERP, como inscrições, site, logo e
 * assinatura de documentos.
 */
class TenantCompanyProfileService
{
    private Tenant $tenantModel;
    private EmpresaConfig $empresaConfigModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->empresaConfigModel = new EmpresaConfig();
    }

    public function findForTenant(int $tenantId): object|false
    {
        $tenant = $this->tenantModel->findActiveById($tenantId);
        if (!$tenant) {
            return false;
        }

        $config = $this->empresaConfigModel->findByTenantId($tenantId) ?: (object) [];

        return (object) [
            'tenant_id'                    => (int) $tenant->id,
            'tipo_pessoa'                  => strlen((string) ($tenant->cnpj ?? '')) === 11 ? 'pf' : 'pj',
            'razao_social'                 => $this->prefer($tenant->razao_social ?? null, $tenant->name ?? null, $config->razao_social ?? null),
            'nome_fantasia'                => $this->prefer($tenant->nome_fantasia ?? null, $config->nome_fantasia ?? null),
            'cpf_cnpj'                     => $this->prefer($tenant->cnpj ?? null, $config->cpf_cnpj ?? null),
            'inscricao_estadual'           => (string) ($config->inscricao_estadual ?? ''),
            'inscricao_municipal'          => (string) ($config->inscricao_municipal ?? ''),
            'email_responsavel'            => $this->prefer($tenant->email ?? null, $config->email_responsavel ?? null),
            'email_financeiro'             => $this->prefer($tenant->billing_email ?? null, $config->email_financeiro ?? null, $tenant->email ?? null),
            'financeiro_mesmo_responsavel' => $this->sameEmail($tenant->billing_email ?? null, $tenant->email ?? null, $config->financeiro_mesmo_responsavel ?? false),
            'telefone'                     => $this->prefer($tenant->phone ?? null, $config->telefone ?? null),
            'site'                         => (string) ($config->site ?? ''),
            'cep'                          => $this->prefer($tenant->cep ?? null, $config->cep ?? null),
            'logradouro'                   => $this->prefer($tenant->endereco ?? null, $config->logradouro ?? null),
            'numero'                       => $this->prefer($tenant->numero ?? null, $config->numero ?? null),
            'complemento'                  => $this->prefer($tenant->complemento ?? null, $config->complemento ?? null),
            'bairro'                       => $this->prefer($tenant->bairro ?? null, $config->bairro ?? null),
            'cidade'                       => $this->prefer($tenant->cidade ?? null, $config->cidade ?? null),
            'estado'                       => $this->prefer($tenant->estado ?? null, $config->estado ?? null),
            'logo_path'                    => $this->prefer($tenant->logo ?? null, $config->logo_path ?? null),
            'assinatura_nome'              => (string) ($config->assinatura_nome ?? ''),
            'assinatura_cargo'             => (string) ($config->assinatura_cargo ?? ''),
            'assinatura_rubrica'           => (string) ($config->assinatura_rubrica ?? ''),
            'assinatura_imagem_path'       => (string) ($config->assinatura_imagem_path ?? ''),
            'usar_assinatura_imagem'       => (int) ($config->usar_assinatura_imagem ?? 0),
            'autenticacao_texto'           => (string) ($config->autenticacao_texto ?? ''),
            'autenticacao_ativa'           => array_key_exists('autenticacao_ativa', (array) $config) ? (int) $config->autenticacao_ativa : 1,
        ];
    }

    /**
     * Persiste dados empresariais e informa o estágio do resultado para o
     * controller exibir um retorno claro, sem revelar detalhes internos.
     *
     * @return array{success: bool, code: string, created: bool}
     */
    public function saveForTenant(int $tenantId, int $userId, array $data): array
    {
        $tenant = $this->tenantModel->findActiveForUser($tenantId, $userId);
        if (!$tenant) {
            $this->log('warning', 'Empresa não salva: vínculo de tenant indisponível.', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
            return $this->failure('tenant_unavailable');
        }

        $existingConfig = $this->empresaConfigModel->findByTenantId($tenantId);
        $tenantData = [
            'name'           => $this->prefer($data['nome_fantasia'] ?? null, $data['razao_social'] ?? null),
            'email'          => $data['email_responsavel'] ?? '',
            'phone'          => $data['telefone'] ?? '',
            'cnpj'           => $data['cpf_cnpj'] ?? '',
            'razao_social'   => $data['razao_social'] ?? '',
            'nome_fantasia'  => $data['nome_fantasia'] ?? '',
            'endereco'       => $data['logradouro'] ?? '',
            'numero'         => $data['numero'] ?? '',
            'complemento'    => $data['complemento'] ?? '',
            'bairro'         => $data['bairro'] ?? '',
            'cidade'         => $data['cidade'] ?? '',
            'estado'         => $data['estado'] ?? '',
            'cep'            => $data['cep'] ?? '',
            'billing_email'  => $data['email_financeiro'] ?? '',
        ];
        if (array_key_exists('logo_path', $data)) {
            $tenantData['logo'] = $data['logo_path'];
        }

        $pdo = $this->tenantModel->getPdo();
        try {
            if (!$pdo->beginTransaction()) {
                $this->log('error', 'Empresa não salva: não foi possível iniciar a transação.', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                ]);
                return $this->failure('transaction_start_failed');
            }

            if (!$this->tenantModel->updateCompanyProfile($tenantId, $tenantData)) {
                $pdo->rollBack();
                $this->log('error', 'Empresa não salva: falha ao atualizar dados centrais do tenant.', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                ]);
                return $this->failure('tenant_update_failed');
            }

            $configOwnerId = (int) ($tenant->master_user_id ?? 0) ?: $userId;
            if (!$this->empresaConfigModel->upsertForTenant($tenantId, $configOwnerId, $data)) {
                $pdo->rollBack();
                $this->log('error', 'Empresa não salva: falha ao persistir campos complementares.', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'config_owner_id' => $configOwnerId,
                ]);
                return $this->failure('company_config_save_failed');
            }

            if (!$pdo->commit()) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->log('error', 'Empresa não salva: falha ao confirmar a transação.', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                ]);
                return $this->failure('transaction_commit_failed');
            }

            $created = !$existingConfig;
            $this->log('info', 'Dados da empresa salvos com sucesso.', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action' => $created ? 'created' : 'updated',
            ]);

            return [
                'success' => true,
                'code' => $created ? 'empresa_criada' : 'empresa_atualizada',
                'created' => $created,
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->log('error', 'Empresa não salva: exceção durante a persistência.', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'exception' => $exception->getMessage(),
            ]);

            return $this->failure('exception');
        }
    }

    private function failure(string $code): array
    {
        return ['success' => false, 'code' => $code, 'created' => false];
    }

    private function log(string $level, string $message, array $context): void
    {
        $logger = new Logger();
        if ($level === 'error') {
            $logger->error($message, $context);
            return;
        }
        if ($level === 'warning') {
            $logger->warning($message, $context);
            return;
        }
        $logger->info($message, $context);
    }

    private function prefer(?string ...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function sameEmail(?string $billingEmail, ?string $responsibleEmail, bool $fallback): int
    {
        $billingEmail = strtolower(trim((string) $billingEmail));
        $responsibleEmail = strtolower(trim((string) $responsibleEmail));
        if ($billingEmail === '' || $responsibleEmail === '') {
            return $fallback ? 1 : 0;
        }

        return $billingEmail === $responsibleEmail ? 1 : 0;
    }
}
