<?php

declare(strict_types=1);

use App\Core\Model;
use App\Models\EmpresaConfig;
use App\Models\Tenant;
use App\Services\TenantCompanyProfileService;

require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Logger.php';
require_once dirname(__DIR__) . '/app/Models/Tenant.php';
require_once dirname(__DIR__) . '/app/Models/EmpresaConfig.php';
require_once dirname(__DIR__) . '/app/Services/TenantCompanyProfileService.php';

final class TenantCompanyProfileTestPdo extends PDO
{
    private bool $transactionOpen = false;

    public function __construct()
    {
    }

    public function beginTransaction(): bool
    {
        $this->transactionOpen = true;
        return true;
    }

    public function commit(): bool
    {
        $this->transactionOpen = false;
        return true;
    }

    public function rollBack(): bool
    {
        $this->transactionOpen = false;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->transactionOpen;
    }
}

final class TenantCompanyProfileTestTenant extends Tenant
{
    public object|false $tenant = false;
    public array $updates = [];
    public function __construct(TenantCompanyProfileTestPdo $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findActiveById(int $id): object|false
    {
        return $this->tenant && (int) $this->tenant->id === $id ? $this->tenant : false;
    }

    public function findActiveForUser(int $tenantId, int $userId): object|false
    {
        return $this->findActiveById($tenantId);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function updateCompanyProfile(int $tenantId, array $data): bool
    {
        $this->updates[] = ['tenant_id' => $tenantId, 'data' => $data];
        return true;
    }
}

final class TenantCompanyProfileTestConfig extends EmpresaConfig
{
    public object|false $config = false;
    public array $upserts = [];

    public function __construct()
    {
    }

    public function findByTenantId(int $tenantId): object|false
    {
        return $this->config;
    }

    public function upsertForTenant(int $tenantId, int $usuarioId, array $data): bool
    {
        $this->upserts[] = ['tenant_id' => $tenantId, 'usuario_id' => $usuarioId, 'data' => $data];
        return true;
    }
}

function assertTenantProfile(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function injectServiceDependency(object $service, string $propertyName, object $dependency): void
{
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(true);
    $property->setValue($service, $dependency);
}

$pdo = new TenantCompanyProfileTestPdo();
$tenantModel = new TenantCompanyProfileTestTenant($pdo);
$tenantModel->tenant = (object) [
    'id' => 27,
    'master_user_id' => 9,
    'name' => 'Imagem Saúde',
    'status' => 'active',
    'email' => 'contato@imagemsaude.com.br',
    'phone' => '11999999999',
    'cnpj' => '12345678000199',
    'razao_social' => 'Imagem Saúde Diagnósticos Ltda.',
    'nome_fantasia' => 'Imagem Saúde',
    'endereco' => 'Rua Central',
    'numero' => '100',
    'complemento' => 'Sala 2',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'cep' => '01001000',
    'billing_email' => 'financeiro@imagemsaude.com.br',
    'logo' => 'storage/uploads/empresa/tenant-27/logo.png',
];
$configModel = new TenantCompanyProfileTestConfig();
$configModel->config = (object) [
    'tenant_id' => 27,
    'site' => 'https://imagemsaude.com.br',
    'inscricao_estadual' => '123.456.789',
    'assinatura_nome' => 'Dra. Ana',
    'autenticacao_ativa' => 1,
];

$serviceReflection = new ReflectionClass(TenantCompanyProfileService::class);
/** @var TenantCompanyProfileService $service */
$service = $serviceReflection->newInstanceWithoutConstructor();
injectServiceDependency($service, 'tenantModel', $tenantModel);
injectServiceDependency($service, 'empresaConfigModel', $configModel);

$profile = $service->findForTenant(27);
assertTenantProfile($profile !== false, 'O perfil do tenant ativo deve ser resolvido.');
assertTenantProfile($profile->razao_social === 'Imagem Saúde Diagnósticos Ltda.', 'A razão social deve vir do cadastro SaaS do tenant.');
assertTenantProfile($profile->cpf_cnpj === '12345678000199', 'O CNPJ deve vir do cadastro SaaS do tenant.');
assertTenantProfile($profile->site === 'https://imagemsaude.com.br', 'O site complementar deve continuar disponível.');
assertTenantProfile($profile->assinatura_nome === 'Dra. Ana', 'A assinatura complementar deve ser preservada.');

$saveData = [
    'razao_social' => 'Imagem Saúde Diagnósticos Ltda.',
    'nome_fantasia' => 'Imagem Saúde',
    'cpf_cnpj' => '12345678000199',
    'email_responsavel' => 'contato@imagemsaude.com.br',
    'email_financeiro' => 'financeiro@imagemsaude.com.br',
    'telefone' => '11999999999',
    'logradouro' => 'Rua Central',
    'numero' => '100',
    'complemento' => 'Sala 2',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'cep' => '01001000',
    'site' => 'https://imagemsaude.com.br',
    'assinatura_nome' => 'Dra. Ana',
];
$saveResult = $service->saveForTenant(27, 9, $saveData);
assertTenantProfile($saveResult['success'], 'A atualização conjunta do tenant e dos campos complementares deve ser concluída.');
assertTenantProfile($saveResult['code'] === 'empresa_atualizada', 'Uma configuração existente deve retornar atualização confirmada.');
assertTenantProfile(count($tenantModel->updates) === 1, 'Os dados centrais devem atualizar o tenant uma única vez.');
assertTenantProfile($tenantModel->updates[0]['data']['cnpj'] === '12345678000199', 'A atualização central deve receber o CNPJ do formulário.');
assertTenantProfile(count($configModel->upserts) === 1, 'Os campos complementares devem persistir na configuração do tenant.');
assertTenantProfile($configModel->upserts[0]['tenant_id'] === 27, 'Os campos complementares devem ser gravados no tenant correto.');

echo "OK: perfil empresarial sincronizado com o tenant ativo.\n";

$tenantModel->tenant = false;
$deniedResult = $service->saveForTenant(27, 9, $saveData);
assertTenantProfile(!$deniedResult['success'], 'A gravação deve falhar sem vínculo ativo ao tenant.');
assertTenantProfile($deniedResult['code'] === 'tenant_unavailable', 'A falha de vínculo deve retornar um código compreensível.');

echo "OK: falha de vínculo do tenant tratada com retorno explícito.\n";
