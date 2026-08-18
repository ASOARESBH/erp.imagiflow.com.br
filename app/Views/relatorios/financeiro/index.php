<?php

use App\Core\UI;

UI::sectionHeader('Relatórios Financeiros', 'Analise contas a pagar, receber e fluxo de caixa', []);

$filtros = $filtros ?? [];
$resultado = $resultado ?? null;
$erroFiltro = $erroFiltro ?? '';
$gerar = $gerar ?? false;
$planos = $planos ?? [];
$fornecedores = $fornecedores ?? [];
$clientes = $clientes ?? [];

$selecionado = static function (array $valores, mixed $valor): bool {
    return in_array((string) $valor, array_map('strval', $valores), true);
};
$exportQuery = $filtros;
$exportQuery['gerar'] = '1';
?>

<?php if ($erroFiltro !== ''): ?>
    <div class="alert alert-danger border-0 shadow-sm" role="alert"><i class="fas fa-exclamation-triangle me-1"></i><?php echo htmlspecialchars($erroFiltro); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/relatorios/financeiro/buscar" class="row g-3 align-items-end" id="filtroRelatorioFinanceiro">
            <input type="hidden" name="gerar" value="1">
            <div class="col-md-3">
                <label for="data_inicio" class="form-label small fw-bold text-muted">Data Inicial</label>
                <input type="date" id="data_inicio" name="data_inicio" class="form-control" required value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="data_fim" class="form-label small fw-bold text-muted">Data Final</label>
                <input type="date" id="data_fim" name="data_fim" class="form-control" required value="<?php echo htmlspecialchars($filtros['data_fim'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="tipo_data" class="form-label small fw-bold text-muted">Tipo de Data</label>
                <select id="tipo_data" name="tipo_data" class="form-select">
                    <option value="vencimento" <?php echo ($filtros['tipo_data'] ?? '') === 'vencimento' ? 'selected' : ''; ?>>Vencimento</option>
                    <option value="efetivo" <?php echo ($filtros['tipo_data'] ?? '') === 'efetivo' ? 'selected' : ''; ?>>Pagamento / Recebimento efetivo</option>
                    <option value="emissao" <?php echo ($filtros['tipo_data'] ?? '') === 'emissao' ? 'selected' : ''; ?>>Emissão</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="tipo_relatorio" class="form-label small fw-bold text-muted">Tipo de Relatório</label>
                <select id="tipo_relatorio" name="tipo_relatorio" class="form-select">
                    <option value="pagar" <?php echo ($filtros['tipo_relatorio'] ?? '') === 'pagar' ? 'selected' : ''; ?>>Contas a Pagar (analítico)</option>
                    <option value="receber" <?php echo ($filtros['tipo_relatorio'] ?? '') === 'receber' ? 'selected' : ''; ?>>Contas a Receber (analítico)</option>
                    <option value="comparativo" <?php echo ($filtros['tipo_relatorio'] ?? '') === 'comparativo' ? 'selected' : ''; ?>>Contas a Pagar vs Receber</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="agrupamento" class="form-label small fw-bold text-muted">Agrupamento / Visualização</label>
                <select id="agrupamento" name="agrupamento" class="form-select">
                    <option value="detalhado" <?php echo ($filtros['agrupamento'] ?? '') === 'detalhado' ? 'selected' : ''; ?>>Detalhado</option>
                    <option value="plano" <?php echo ($filtros['agrupamento'] ?? '') === 'plano' ? 'selected' : ''; ?>>Agrupado por Plano de Contas</option>
                    <option value="entidade" <?php echo ($filtros['agrupamento'] ?? '') === 'entidade' ? 'selected' : ''; ?>>Agrupado por Fornecedor / Cliente</option>
                    <option value="status" <?php echo ($filtros['agrupamento'] ?? '') === 'status' ? 'selected' : ''; ?>>Agrupado por Status</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="plano_ids" class="form-label small fw-bold text-muted">Plano de Contas</label>
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar plano de contas" data-filter-select="plano_ids">
                <select id="plano_ids" name="plano_ids[]" class="form-select" multiple size="3">
                    <?php foreach ($planos as $plano): ?>
                        <option value="<?php echo (int) $plano->id; ?>" <?php echo $selecionado($filtros['plano_ids'] ?? [], $plano->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($plano->codigo . ' - ' . $plano->nome); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Use Ctrl/Cmd para selecionar mais de uma conta.</small>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label small fw-bold text-muted">Status</label>
                <select id="status" name="status[]" class="form-select" multiple size="3">
                    <?php foreach (['aberta' => 'Aberta', 'paga' => 'Paga', 'recebida' => 'Recebida', 'cancelada' => 'Cancelada'] as $valor => $titulo): ?>
                        <option value="<?php echo $valor; ?>" <?php echo $selecionado($filtros['status'] ?? [], $valor) ? 'selected' : ''; ?>><?php echo $titulo; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6" id="grupoFornecedor">
                <label for="fornecedor_ids" class="form-label small fw-bold text-muted">Fornecedores</label>
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar fornecedor por nome ou documento" data-filter-select="fornecedor_ids">
                <select id="fornecedor_ids" name="fornecedor_ids[]" class="form-select" multiple size="4">
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?php echo (int) $fornecedor->id; ?>" <?php echo $selecionado($filtros['fornecedor_ids'] ?? [], $fornecedor->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($fornecedor->nome . (!empty($fornecedor->cpf_cnpj) ? ' — ' . $fornecedor->cpf_cnpj : '')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6" id="grupoCliente">
                <label for="cliente_ids" class="form-label small fw-bold text-muted">Clientes</label>
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar cliente por nome ou documento" data-filter-select="cliente_ids">
                <select id="cliente_ids" name="cliente_ids[]" class="form-select" multiple size="4">
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $nomeCliente = $cliente->nome_fantasia ?: $cliente->razao_social; ?>
                        <option value="<?php echo (int) $cliente->id; ?>" <?php echo $selecionado($filtros['cliente_ids'] ?? [], $cliente->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nomeCliente . (!empty($cliente->cpf_cnpj) ? ' — ' . $cliente->cpf_cnpj : '')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="valor_min" class="form-label small fw-bold text-muted">Valor mínimo</label>
                <input id="valor_min" name="valor_min" type="text" inputmode="decimal" class="form-control" placeholder="0,00" value="<?php echo htmlspecialchars($filtros['valor_min'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="valor_max" class="form-label small fw-bold text-muted">Valor máximo</label>
                <input id="valor_max" name="valor_max" type="text" inputmode="decimal" class="form-control" placeholder="0,00" value="<?php echo htmlspecialchars($filtros['valor_max'] ?? ''); ?>">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Filtrar / Gerar Relatório</button>
                <a href="/relatorios/financeiro" class="btn btn-outline-secondary" title="Limpar filtros"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if ($resultado === null): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Defina os filtros e clique em <strong>Filtrar / Gerar Relatório</strong> para visualizar os dados.</p>
            </div>
        <?php elseif (empty($resultado['linhas'])): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Nenhum lançamento encontrado para os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                <div>
                    <strong>Pré-visualização do relatório</strong>
                    <div class="text-muted small">A visualização mostra até 50 linhas por página. As exportações trazem todos os resultados do filtro.</div>
                </div>
                <div class="btn-group">
                    <a href="/relatorios/financeiro/exportar-csv?<?php echo htmlspecialchars(http_build_query($exportQuery)); ?>" class="btn btn-outline-success"><i class="fas fa-file-csv me-1"></i> Exportar CSV</a>
                    <a href="/relatorios/financeiro/exportar-pdf?<?php echo htmlspecialchars(http_build_query($exportQuery)); ?>" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Exportar PDF</a>
                </div>
            </div>
            <?php include __DIR__ . '/_grid.php'; ?>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/relatorio-financeiro.js"></script>
