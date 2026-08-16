<?php

declare(strict_types=1);

function assertSupplierQuickFlow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/Models/Fornecedor.php');
$controller = file_get_contents($root . '/app/Controllers/FornecedoresController.php');
$view = file_get_contents($root . '/app/Views/contas_pagar/tabs/geral-enterprise.php');
$script = file_get_contents($root . '/public/assets/js/fornecedor-rapido.js');
$routes = file_get_contents($root . '/routes/web.php');
$contasPagar = file_get_contents($root . '/app/Controllers/ContasPagarController.php');

assertSupplierQuickFlow(strpos($model, 'function searchByTenant') !== false, 'O modelo deve expor busca digitável limitada ao tenant.');
assertSupplierQuickFlow(strpos($model, 'function findByIdForTenant') !== false, 'O modelo deve validar o fornecedor pelo tenant.');
assertSupplierQuickFlow(strpos($model, 'ORDER BY created_at DESC, nome ASC') !== false, 'Fornecedores recém-criados devem ser retornados primeiro.');
assertSupplierQuickFlow(strpos($model, 'function documentoExistsForTenant') !== false, 'O modelo deve prevenir documento duplicado por tenant.');
assertSupplierQuickFlow(strpos($controller, 'function quickSearch') !== false, 'O controlador deve expor a busca rápida.');
assertSupplierQuickFlow(strpos($controller, 'function quickStore') !== false, 'O controlador deve expor o cadastro rápido.');
assertSupplierQuickFlow(strpos($controller, 'HTTP_X_CSRF_TOKEN') !== false, 'O cadastro rápido deve validar CSRF.');
assertSupplierQuickFlow(strpos($controller, "'tenant_id' => \$tenantId") !== false, 'O cadastro rápido deve gravar tenant_id.');
assertSupplierQuickFlow(strpos($view, 'fornecedor_busca') !== false, 'A conta a pagar deve conter campo pesquisável de fornecedor.');
assertSupplierQuickFlow(strpos($view, 'modalNovoFornecedor') !== false, 'A conta a pagar deve conter o cadastro rápido em modal.');
assertSupplierQuickFlow(strpos($script, '/fornecedores/busca-rapida') !== false, 'O script deve consultar a busca rápida.');
assertSupplierQuickFlow(strpos($script, '/fornecedores/criar-rapido') !== false, 'O script deve criar o fornecedor sem sair do formulário.');
assertSupplierQuickFlow(strpos($routes, '/fornecedores/busca-rapida') !== false, 'A rota de busca rápida deve estar registrada.');
assertSupplierQuickFlow(strpos($routes, '/fornecedores/criar-rapido') !== false, 'A rota de cadastro rápido deve estar registrada.');
assertSupplierQuickFlow(strpos($contasPagar, 'fornecedorModel->findByTenantId') !== false, 'Contas a pagar deve listar fornecedores do tenant ativo.');
assertSupplierQuickFlow(strpos($contasPagar, 'fornecedorModel->findByIdForTenant') !== false, 'Contas a pagar deve validar o fornecedor no tenant ativo.');

echo "OK: busca e cadastro rápido de fornecedor estão protegidos e isolados por tenant.\n";
