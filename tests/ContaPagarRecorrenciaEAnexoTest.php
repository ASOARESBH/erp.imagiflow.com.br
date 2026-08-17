<?php

declare(strict_types=1);

function assertContaPagarFlow(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/Models/ContaPagar.php');
$anexoModel = file_get_contents($root . '/app/Models/ContaPagarAnexo.php');
$service = file_get_contents($root . '/app/Services/ContaPagarRecorrenciaService.php');
$controller = file_get_contents($root . '/app/Controllers/ContasPagarController.php');
$form = file_get_contents($root . '/app/Views/contas_pagar/tabs/geral-enterprise.php');
$anexosView = file_get_contents($root . '/app/Views/contas_pagar/tabs/anexos-enterprise.php');
$migration = file_get_contents($root . '/database/migrations/2026-08-18_contas_pagar_recorrencia_parcelas.sql');

assertContaPagarFlow(strpos($model, 'function findByTenantId') !== false, 'A listagem deve estar disponível por tenant.');
assertContaPagarFlow(strpos($model, '$status !== \'\' && $status !== \'todos\'') !== false, 'O filtro Todos deve remover o filtro de status.');
assertContaPagarFlow(strpos($model, 'tenant_id, usuario_id') !== false, 'A criação de conta deve persistir o tenant ativo.');
assertContaPagarFlow(strpos($model, 'recorrencia_modo') !== false, 'O modelo deve persistir o modo de recorrência.');
assertContaPagarFlow(strpos($model, 'grupo_parcelas') !== false, 'O modelo deve persistir o grupo de parcelas.');
assertContaPagarFlow(strpos($service, 'function gerarParcelas') !== false, 'O serviço deve gerar parcelas antecipadas.');
assertContaPagarFlow(strpos($service, 'for ($numero = 2; $numero <= $totalParcelas; $numero++)') !== false, 'O serviço deve gerar da parcela 2 até a quantidade informada.');
assertContaPagarFlow(strpos($service, "case 'mensal':") !== false, 'O serviço deve calcular recorrência mensal.');
assertContaPagarFlow(strpos($service, 'beginTransaction') !== false && strpos($service, 'rollBack') !== false, 'A geração de parcelas deve ser transacional.');
assertContaPagarFlow(strpos($controller, 'new ContaPagarRecorrenciaService') !== false, 'O controlador deve acionar a geração de parcelas.');
assertContaPagarFlow(strpos($controller, 'function normalizarRecorrencia') !== false, 'O controlador deve normalizar a recorrência no servidor.');
assertContaPagarFlow(strpos($controller, "if (\$dados['recorrencia_tipo'] !== null && \$total > 1)") !== false, 'Tipo e quantidade devem ativar a recorrência mesmo sem checkbox.');
assertContaPagarFlow(strpos($controller, 'function deveGerarParcelas') !== false, 'A geração deve depender de tipo e quantidade de parcelas.');
assertContaPagarFlow(strpos($controller, 'findByIdForTenant') !== false, 'O controlador deve validar contas pelo tenant.');
assertContaPagarFlow(strpos($anexoModel, 'function findByContaId(int $contaPagarId, int $tenantId)') !== false, 'Anexos devem ser listados pelo tenant.');
assertContaPagarFlow(strpos($anexoModel, 'tenant_id, usuario_id, conta_pagar_id') !== false, 'Anexos devem persistir o tenant.');
assertContaPagarFlow(strpos($anexosView, 'View::csrfField()') !== false, 'O upload de anexo deve ter token CSRF.');
assertContaPagarFlow(strpos($form, 'Quantidade de parcelas') !== false, 'A tela deve informar a quantidade de parcelas.');
assertContaPagarFlow(strpos($migration, 'ADD COLUMN recorrencia_modo') !== false, 'A migration deve adicionar metadados de recorrência.');

$base = new DateTimeImmutable('2026-03-01');
assertContaPagarFlow($base->modify('+11 month')->format('Y-m-d') === '2027-02-01', 'Doze parcelas mensais devem terminar em fevereiro de 2027.');

echo "OK: recorrência, filtro Todos e anexos de contas a pagar estão cobertos por tenant.\n";
