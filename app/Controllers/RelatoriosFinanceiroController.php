<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Core\Audit\AuditLogger;
use App\Models\RelatorioFinanceiro;
use App\Models\Tenant;

class RelatoriosFinanceiroController extends Controller
{
    private RelatorioFinanceiro $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new RelatorioFinanceiro();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        $this->garantirPermissao();
        $this->renderizarTela(false);
    }

    public function buscar(): void
    {
        $this->garantirPermissao();
        $this->renderizarTela(true);
    }

    public function buscarOpcoes(): void
    {
        $this->garantirPermissao();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $tipo = (string) ($_GET['tipo'] ?? '');
            $busca = trim((string) ($_GET['q'] ?? ''));
            if (!in_array($tipo, ['plano', 'fornecedor', 'cliente'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Tipo de filtro inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            if (mb_strlen($busca) < 2) {
                echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $user = Auth::user();
            $tenantId = (int) ($user->tenant_id ?? 0);
            if ($tenantId <= 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tenant não identificado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode([
                'success' => true,
                'data' => $this->model->buscarOpcoesFiltro($tenantId, $tipo, $busca),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $this->logger->error('Erro na busca de opções do relatório financeiro.', [
                'error' => $e->getMessage(),
                'tipo' => (string) ($_GET['tipo'] ?? ''),
                'usuario_id' => (int) (Auth::user()->id ?? 0),
            ]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível buscar as opções agora.'], JSON_UNESCAPED_UNICODE);
        }
    }

    private function renderizarTela(bool $forcarBusca): void
    {
        try {
            $user = Auth::user();
            $tenantId = (int) ($user->tenant_id ?? 0);
            $filtros = $this->obterFiltros();
            $gerar = $forcarBusca || (string) ($_GET['gerar'] ?? '') === '1';
            $resultado = null;
            $erroFiltro = '';

            if ($gerar) {
                $erroFiltro = $this->validarFiltros($filtros);
                if ($erroFiltro === '') {
                    $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
                    $resultado = $this->model->buscar($tenantId, $filtros, $pagina, 50);
                    $this->logger->info('Relatório financeiro consultado.', [
                        'usuario_id' => (int) ($user->id ?? 0),
                        'tenant_id' => $tenantId,
                        'tipo' => $filtros['tipo_relatorio'],
                        'agrupamento' => $filtros['agrupamento'],
                        'total_registros' => $resultado['total_registros'],
                    ]);
                    AuditLogger::log('gerar_relatorio_financeiro', [
                        'tenant_id' => $tenantId,
                        'tipo' => $filtros['tipo_relatorio'],
                        'total_registros' => $resultado['total_registros'],
                    ]);
                }
            }

            View::render('relatorios/financeiro/index', [
                '_layout' => 'erp',
                'title' => 'Relatórios Financeiros',
                'breadcrumb' => ['Relatórios' => '/relatorios/financeiro', 'Financeiro'],
                'filtros' => $filtros,
                'resultado' => $resultado,
                'erroFiltro' => $erroFiltro,
                'gerar' => $gerar,
                'planos' => $this->model->buscarOpcoesSelecionadas($tenantId, 'plano', $filtros['plano_ids']),
                'fornecedores' => $this->model->buscarOpcoesSelecionadas($tenantId, 'fornecedor', $filtros['fornecedor_ids']),
                'clientes' => $this->model->buscarOpcoesSelecionadas($tenantId, 'cliente', $filtros['cliente_ids']),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao consultar relatório financeiro.', [
                'error' => $e->getMessage(),
                'usuario_id' => (int) (Auth::user()->id ?? 0),
            ]);
            http_response_code(500);
            View::render('relatorios/financeiro/index', [
                '_layout' => 'erp',
                'title' => 'Relatórios Financeiros',
                'breadcrumb' => ['Relatórios' => '/relatorios/financeiro', 'Financeiro'],
                'filtros' => $this->obterFiltros(),
                'resultado' => null,
                'erroFiltro' => 'Não foi possível gerar o relatório agora. Tente novamente.',
                'gerar' => false,
                'planos' => [],
                'fornecedores' => [],
                'clientes' => [],
            ]);
        }
    }

    /** @return array<string,mixed> */
    private function obterFiltros(): array
    {
        $tipoRelatorio = (string) ($_GET['tipo_relatorio'] ?? 'pagar');
        $statusLegado = $this->obterLista('status');
        return [
            'data_inicio' => (string) ($_GET['data_inicio'] ?? date('Y-m-01')),
            'data_fim' => (string) ($_GET['data_fim'] ?? date('Y-m-d')),
            'tipo_data' => (string) ($_GET['tipo_data'] ?? 'vencimento'),
            'tipo_relatorio' => $tipoRelatorio,
            'agrupamento' => (string) ($_GET['agrupamento'] ?? 'detalhado'),
            'plano_ids' => $this->obterLista('plano_ids'),
            'fornecedor_ids' => $this->obterLista('fornecedor_ids'),
            'cliente_ids' => $this->obterLista('cliente_ids'),
            'status_pagar' => $this->obterLista('status_pagar') ?: ($tipoRelatorio === 'pagar' ? $statusLegado : []),
            'status_receber' => $this->obterLista('status_receber') ?: ($tipoRelatorio === 'receber' ? $statusLegado : []),
            'valor_min' => (string) ($_GET['valor_min'] ?? ''),
            'valor_max' => (string) ($_GET['valor_max'] ?? ''),
        ];
    }

    /** @return array<int,string> */
    private function obterLista(string $chave): array
    {
        $valor = $_GET[$chave] ?? [];
        if (!is_array($valor)) {
            $valor = $valor === '' ? [] : [$valor];
        }
        return array_values(array_filter(array_map('strval', $valor), static fn(string $item): bool => $item !== ''));
    }

    private function validarFiltros(array $filtros): string
    {
        $inicio = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $filtros['data_inicio']);
        $fim = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $filtros['data_fim']);
        if (!$inicio || !$fim || $inicio->format('Y-m-d') !== $filtros['data_inicio'] || $fim->format('Y-m-d') !== $filtros['data_fim']) {
            return 'Informe uma data inicial e uma data final válidas.';
        }
        if ($inicio > $fim) {
            return 'A data inicial não pode ser posterior à data final.';
        }
        if ($inicio->diff($fim)->days > 366) {
            return 'O período máximo para consulta e exportação é de 12 meses.';
        }
        if (!in_array($filtros['tipo_relatorio'], ['pagar', 'receber', 'comparativo'], true)) {
            return 'Selecione um tipo de relatório válido.';
        }
        if (!in_array($filtros['tipo_data'], ['vencimento', 'efetivo', 'emissao'], true)) {
            return 'Selecione um tipo de data válido.';
        }
        if (!in_array($filtros['agrupamento'], ['detalhado', 'plano', 'entidade', 'status'], true)) {
            return 'Selecione um agrupamento válido.';
        }
        if (array_diff($filtros['status_pagar'], ['aberta', 'paga', 'cancelada']) !== []) {
            return 'Selecione status válidos para Contas a Pagar.';
        }
        if (array_diff($filtros['status_receber'], ['aberta', 'recebida', 'cancelada']) !== []) {
            return 'Selecione status válidos para Contas a Receber.';
        }
        return '';
    }

    public function exportarCsv(): void
    {
        [$filtros, $resultado, $tenantId, $usuarioId] = $this->obterDadosParaExportacao();
        $linhas = $resultado['linhas'];
        $comparativo = $filtros['tipo_relatorio'] === 'comparativo';
        $nomeArquivo = 'relatorio_financeiro_' . $filtros['tipo_relatorio'] . '_' . date('Ymd_His') . '.csv';

        $this->logger->info('Relatório financeiro exportado em CSV.', [
            'usuario_id' => $usuarioId,
            'tenant_id' => $tenantId,
            'tipo' => $filtros['tipo_relatorio'],
            'total' => count($linhas),
        ]);
        AuditLogger::log('exportar_relatorio_financeiro_csv', [
            'tenant_id' => $tenantId,
            'tipo' => $filtros['tipo_relatorio'],
            'total' => count($linhas),
        ]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $saida = fopen('php://output', 'w');
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $cabecalho = $comparativo
            ? ['Tipo', 'Data', 'Descrição', 'Fornecedor / Cliente', 'Plano de Contas', 'Valor', 'Status']
            : ['Data', 'Descrição', 'Fornecedor / Cliente', 'Plano de Contas', 'Valor', 'Status'];
        fputcsv($saida, $cabecalho, ';');
        foreach ($linhas as $linha) {
            $registro = [
                $this->formatarData($linha->data_referencia ?? null),
                $linha->descricao ?? '',
                $linha->entidade_nome ?? '',
                trim((string) ($linha->plano_codigo ?? '') . ' - ' . (string) ($linha->plano_nome ?? '')),
                number_format((float) ($linha->valor ?? 0), 2, ',', '.'),
                $this->rotuloStatus((string) ($linha->status ?? '')),
            ];
            if ($comparativo) {
                array_unshift($registro, ($linha->tipo_lancamento ?? '') === 'pagar' ? 'Contas a Pagar' : 'Contas a Receber');
            }
            fputcsv($saida, $registro, ';');
        }
        fclose($saida);
        exit();
    }

    public function exportarPdf(): void
    {
        [$filtros, $resultado, $tenantId, $usuarioId] = $this->obterDadosParaExportacao();
        require_once BASE_PATH . '/app/Lib/fpdf/fpdf.php';

        $tenant = (new Tenant())->findActiveById($tenantId);
        $nomeEmpresa = trim((string) ($tenant->nome_fantasia ?? $tenant->razao_social ?? $tenant->name ?? 'ERP IMAGINIFLOW'));
        $linhas = $resultado['linhas'];
        $totais = $resultado['totais'];
        $enc = static fn(string $texto): string => iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);

        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();
        $pdf->SetFillColor(0, 89, 162);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(277, 10, $enc($nomeEmpresa), 0, 1, 'L', true);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(277, 8, $enc('RELATÓRIO FINANCEIRO'), 0, 1, 'L', true);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(277, 6, $enc('Período: ' . $this->formatarData($filtros['data_inicio']) . ' a ' . $this->formatarData($filtros['data_fim']) . ' | Gerado em: ' . date('d/m/Y H:i')), 0, 1, 'L');
        $pdf->Ln(2);

        $larguras = [25, 68, 55, 62, 28, 25];
        $titulos = ['Data', 'Descrição', 'Fornecedor / Cliente', 'Plano de Contas', 'Valor', 'Status'];
        $pdf->SetFillColor(240, 242, 245);
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($titulos as $indice => $titulo) {
            $pdf->Cell($larguras[$indice], 7, $enc($titulo), 1, 0, $indice === 4 ? 'R' : 'L', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7);
        foreach ($linhas as $linha) {
            if ($pdf->GetY() > 185) {
                $pdf->AddPage();
            }
            $dados = [
                $this->formatarData($linha->data_referencia ?? null),
                $this->truncar((string) ($linha->descricao ?? ''), 35),
                $this->truncar((string) ($linha->entidade_nome ?? ''), 28),
                $this->truncar(trim((string) ($linha->plano_codigo ?? '') . ' - ' . (string) ($linha->plano_nome ?? '')), 32),
                'R$ ' . number_format((float) ($linha->valor ?? 0), 2, ',', '.'),
                $this->rotuloStatus((string) ($linha->status ?? '')),
            ];
            foreach ($dados as $indice => $dado) {
                $pdf->Cell($larguras[$indice], 6, $enc($dado), 1, 0, $indice === 4 ? 'R' : 'L');
            }
            $pdf->Ln();
        }
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(210, 8, $enc('Total geral'), 1, 0, 'R', true);
        $pdf->Cell(28, 8, $enc('R$ ' . number_format((float) ($totais['total_geral'] ?? 0), 2, ',', '.')), 1, 0, 'R', true);
        $pdf->Cell(25, 8, '', 1, 0, 'L', true);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(277, 6, $enc('Usuário gerador: ' . (Auth::user()->name ?? 'Usuário') . ' | Total de lançamentos: ' . count($linhas)), 0, 1, 'L');

        $this->logger->info('Relatório financeiro exportado em PDF.', [
            'usuario_id' => $usuarioId,
            'tenant_id' => $tenantId,
            'tipo' => $filtros['tipo_relatorio'],
            'total' => count($linhas),
        ]);
        AuditLogger::log('exportar_relatorio_financeiro_pdf', [
            'tenant_id' => $tenantId,
            'tipo' => $filtros['tipo_relatorio'],
            'total' => count($linhas),
        ]);
        $pdf->Output('D', 'relatorio_financeiro_' . date('Ymd_His') . '.pdf');
        exit();
    }

    /** @return array{0:array<string,mixed>,1:array<string,mixed>,2:int,3:int} */
    private function obterDadosParaExportacao(): array
    {
        $this->garantirPermissao();
        $user = Auth::user();
        $tenantId = (int) ($user->tenant_id ?? 0);
        $filtros = $this->obterFiltros();
        $erro = $this->validarFiltros($filtros);
        if ($erro !== '') {
            http_response_code(422);
            exit($erro);
        }
        return [$filtros, $this->model->buscar($tenantId, $filtros, 1, 100, true), $tenantId, (int) ($user->id ?? 0)];
    }

    private function formatarData(?string $data): string
    {
        if (!$data) {
            return '—';
        }
        try {
            return (new \DateTimeImmutable($data))->format('d/m/Y');
        } catch (\Throwable $e) {
            return '—';
        }
    }

    private function rotuloStatus(string $status): string
    {
        return ['aberta' => 'Aberta', 'paga' => 'Paga', 'recebida' => 'Recebida', 'cancelada' => 'Cancelada'][$status] ?? ucfirst($status);
    }

    private function truncar(string $texto, int $limite): string
    {
        return mb_strlen($texto) > $limite ? mb_substr($texto, 0, $limite - 3) . '...' : $texto;
    }

    private function garantirPermissao(): void
    {
        if (!Auth::can('view_relatorios_financeiro')) {
            $this->logger->warning('Acesso negado a relatórios financeiros.', [
                'usuario_id' => (int) (Auth::user()->id ?? 0),
            ]);
            http_response_code(403);
            exit('403 - Acesso negado');
        }
    }
}
