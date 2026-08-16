<?php

declare(strict_types=1);

use App\Services\PlanoContasPadraoService;

require_once dirname(__DIR__) . '/app/Services/PlanoContasPadraoService.php';

function assertChartTemplate(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$template = PlanoContasPadraoService::template();
assertChartTemplate(count($template) >= 50, 'O modelo deve oferecer cobertura suficiente para a operação de saúde e tecnologia.');

$modelos = [];
$codigos = [];
$receitas = 0;
$despesas = 0;
foreach ($template as $account) {
    assertChartTemplate(isset($account['modelo'], $account['codigo'], $account['nome'], $account['tipo'], $account['nivel']), 'Cada conta deve possuir todos os campos obrigatórios.');
    assertChartTemplate(!isset($modelos[$account['modelo']]), 'O código técnico do modelo deve ser único: ' . $account['modelo']);
    assertChartTemplate(!isset($codigos[$account['codigo']]), 'O código financeiro deve ser único: ' . $account['codigo']);
    assertChartTemplate(in_array($account['tipo'], ['Receita', 'Despesa'], true), 'O tipo da conta deve ser Receita ou Despesa.');

    if ($account['pai'] !== null) {
        assertChartTemplate(isset($modelos[$account['pai']]), 'A conta-pai deve ser declarada antes da filha: ' . $account['modelo']);
        assertChartTemplate($account['nivel'] === $modelos[$account['pai']]['nivel'] + 1, 'O nível deve respeitar a hierarquia: ' . $account['modelo']);
    } else {
        assertChartTemplate($account['nivel'] === 1, 'Contas raiz devem permanecer no nível 1.');
    }

    $modelos[$account['modelo']] = $account;
    $codigos[$account['codigo']] = true;
    $receitas += $account['tipo'] === 'Receita' ? 1 : 0;
    $despesas += $account['tipo'] === 'Despesa' ? 1 : 0;
}

assertChartTemplate($receitas > 0 && $despesas > 0, 'O modelo deve conter receitas e despesas.');
assertChartTemplate(isset($codigos['1.01.001']), 'O modelo deve contemplar serviços de diagnóstico por imagem.');
assertChartTemplate(isset($codigos['1.02.001']), 'O modelo deve contemplar licenças e assinaturas de software.');
assertChartTemplate(isset($codigos['1.03.001']), 'O modelo deve contemplar venda de equipamentos médicos.');
assertChartTemplate(isset($codigos['2.02.002']), 'O modelo deve contemplar manutenção de equipamentos médicos.');

echo "OK: modelo de plano de contas possui " . count($template) . " contas válidas e hierarquizadas.\n";
