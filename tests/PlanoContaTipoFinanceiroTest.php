<?php

declare(strict_types=1);

function assertAccountTypeFlow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/Models/PlanoConta.php');
$controller = file_get_contents($root . '/app/Controllers/PlanoContasController.php');
$pagar = file_get_contents($root . '/app/Controllers/ContasPagarController.php');
$receber = file_get_contents($root . '/app/Controllers/ContasReceberController.php');
$viewPagar = file_get_contents($root . '/app/Views/contas_pagar/tabs/geral-enterprise.php');
$viewReceber = file_get_contents($root . '/app/Views/contas_receber/tabs/geral-enterprise.php');
$script = file_get_contents($root . '/public/assets/js/plano-conta-rapido.js');
$routes = file_get_contents($root . '/routes/web.php');

assertAccountTypeFlow(strpos($model, 'function findByIdForTenantAndType') !== false, 'O modelo deve validar plano por tenant e tipo.');
assertAccountTypeFlow(strpos($model, 'function searchByTenantAndType') !== false, 'O modelo deve buscar planos por tenant e tipo.');
assertAccountTypeFlow(strpos($pagar, "'tipo' => 'Despesa'") !== false, 'Contas a pagar deve listar apenas despesas.');
assertAccountTypeFlow(strpos($pagar, "findByIdForTenantAndType(\$planoContaId, \$tenantId, 'Despesa')") !== false, 'Contas a pagar deve rejeitar receitas no servidor.');
assertAccountTypeFlow(strpos($receber, "'tipo' => 'Receita'") !== false, 'Contas a receber deve listar apenas receitas.');
assertAccountTypeFlow(strpos($receber, "findByIdForTenantAndType(\$planoContaId, \$tenantId, 'Receita')") !== false, 'Contas a receber deve rejeitar despesas no servidor.');
assertAccountTypeFlow(strpos($viewPagar, 'data-plano-tipo="Despesa"') !== false, 'O campo de pagar deve declarar contexto de despesa.');
assertAccountTypeFlow(strpos($viewReceber, 'data-plano-tipo="Receita"') !== false, 'O campo de receber deve declarar contexto de receita.');
assertAccountTypeFlow(strpos($viewPagar, 'modalNovoPlanoConta') !== false && strpos($viewReceber, 'modalNovoPlanoConta') !== false, 'Os dois campos devem permitir cadastro rápido.');
assertAccountTypeFlow(strpos($script, '/financeiro/plano-contas/busca-rapida') !== false, 'O script deve pesquisar planos por palavra.');
assertAccountTypeFlow(strpos($script, 'payload.tipo !== type') !== false, 'O script deve impedir troca de tipo entre o campo e o modal.');
assertAccountTypeFlow(strpos($controller, 'assertCsrfHeader') !== false, 'O cadastro rápido deve exigir CSRF.');
assertAccountTypeFlow(strpos($routes, '/financeiro/plano-contas/busca-rapida') !== false, 'A rota de busca rápida deve estar registrada.');
assertAccountTypeFlow(strpos($routes, '/financeiro/plano-contas/criar-rapido') !== false, 'A rota de cadastro rápido deve estar registrada.');

echo "OK: filtros de receita e despesa e cadastro rápido de plano estão consistentes.\n";
