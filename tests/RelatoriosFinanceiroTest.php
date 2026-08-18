<?php

declare(strict_types=1);

function assertRelatorioFinanceiro(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/Models/RelatorioFinanceiro.php');
$controller = file_get_contents($root . '/app/Controllers/RelatoriosFinanceiroController.php');
$permission = file_get_contents($root . '/app/Core/Permission.php');
$routes = file_get_contents($root . '/routes/web.php');
$menu = file_get_contents($root . '/app/Views/layout/erp_header.php');
$view = file_get_contents($root . '/app/Views/relatorios/financeiro/index.php');
$grid = file_get_contents($root . '/app/Views/relatorios/financeiro/_grid.php');

assertRelatorioFinanceiro(strpos($permission, "'view_relatorios_financeiro'") !== false, 'A permissão de relatório financeiro deve existir.');
assertRelatorioFinanceiro(substr_count($permission, "'view_relatorios_financeiro'") === 4, 'A permissão deve estar somente nos quatro papéis autorizados.');
assertRelatorioFinanceiro(strpos($model, 'tenant_id = :tenant_id') !== false, 'As consultas devem exigir tenant_id.');
assertRelatorioFinanceiro(strpos($model, 'SELECT id, nome, documento') !== false, 'O relatório deve consultar o campo documento de fornecedores.');
assertRelatorioFinanceiro(strpos($model, 'nome, cpf_cnpj\n             FROM fornecedores') === false, 'O relatório não pode consultar cpf_cnpj em fornecedores.');
assertRelatorioFinanceiro(strpos($model, "'pagar', 'receber', 'comparativo'") !== false, 'O modelo deve atender os três tipos de relatório.');
assertRelatorioFinanceiro(strpos($model, "'vencimento', 'efetivo', 'emissao'") !== false, 'O modelo deve aceitar os três tipos de data.');
assertRelatorioFinanceiro(strpos($model, "'detalhado', 'plano', 'entidade', 'status'") !== false, 'O modelo deve aceitar os agrupamentos previstos.');
assertRelatorioFinanceiro(strpos($model, 'DATE_FORMAT(CURDATE()') === false, 'O relatório não deve depender de filtros fixos de período.');
assertRelatorioFinanceiro(strpos($controller, 'function exportarCsv') !== false, 'A exportação CSV deve existir.');
assertRelatorioFinanceiro(strpos($controller, 'function exportarPdf') !== false, 'A exportação PDF deve existir.');
assertRelatorioFinanceiro(strpos($controller, "fputcsv(\$saida, \$cabecalho, ';')") !== false, 'O CSV deve usar ponto e vírgula.');
assertRelatorioFinanceiro(strpos($controller, 'chr(0xEF) . chr(0xBB) . chr(0xBF)') !== false, 'O CSV deve incluir BOM UTF-8.');
assertRelatorioFinanceiro(strpos($controller, "Auth::can('view_relatorios_financeiro')") !== false, 'O controlador deve validar a permissão no backend.');
assertRelatorioFinanceiro(strpos($routes, 'Permission:view_relatorios_financeiro') !== false, 'As rotas devem exigir permissão específica.');
assertRelatorioFinanceiro(strpos($routes, '/relatorios/financeiro/exportar-csv') !== false, 'A rota de CSV deve estar registrada.');
assertRelatorioFinanceiro(strpos($routes, '/relatorios/financeiro/exportar-pdf') !== false, 'A rota de PDF deve estar registrada.');
assertRelatorioFinanceiro(strpos($menu, "Auth::can('view_relatorios_financeiro')") !== false, 'O menu deve respeitar a permissão.');
assertRelatorioFinanceiro(strpos($view, 'Filtrar / Gerar Relatório') !== false, 'A tela deve incluir o comando de geração.');
assertRelatorioFinanceiro(strpos($view, 'Exportar CSV') !== false && strpos($view, 'Exportar PDF') !== false, 'A tela deve expor as duas exportações após a consulta.');
assertRelatorioFinanceiro(strpos($grid, 'Total geral') !== false, 'A grade deve apresentar total geral.');

echo "OK: relatórios financeiros possuem filtros, RBAC, tenant, exportações e totais.\n";
