<?php
/** @var array<string,mixed> $resultado */
/** @var array<string,mixed> $filtros */

$linhas = $resultado['linhas'] ?? [];
$grupos = $resultado['grupos'] ?? [];
$totais = $resultado['totais'] ?? [];
$tipoComparativo = ($filtros['tipo_relatorio'] ?? '') === 'comparativo';
$agrupado = ($filtros['agrupamento'] ?? 'detalhado') !== 'detalhado';

$formatarData = static function (?string $data): string {
    if (!$data) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($data))->format('d/m/Y');
    } catch (Throwable $e) {
        return '—';
    }
};
$formatarValor = static fn(float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
$statusLabel = ['aberta' => 'Aberta', 'paga' => 'Paga', 'recebida' => 'Recebida', 'cancelada' => 'Cancelada'];
$statusClass = ['aberta' => 'warning text-dark', 'paga' => 'success', 'recebida' => 'success', 'cancelada' => 'secondary'];
$renderizarLinha = static function (object $linha) use ($tipoComparativo, $formatarData, $formatarValor, $statusLabel, $statusClass): void {
    $status = (string) ($linha->status ?? '');
    ?>
    <tr>
        <?php if ($tipoComparativo): ?>
            <td><span class="badge bg-<?php echo ($linha->tipo_lancamento ?? '') === 'pagar' ? 'danger' : 'success'; ?>"><?php echo ($linha->tipo_lancamento ?? '') === 'pagar' ? 'Pagar' : 'Receber'; ?></span></td>
        <?php endif; ?>
        <td><?php echo htmlspecialchars($formatarData($linha->data_referencia ?? null)); ?></td>
        <td>
            <div class="fw-semibold"><?php echo htmlspecialchars($linha->descricao ?? ''); ?></div>
            <small class="text-muted">Emissão: <?php echo htmlspecialchars($formatarData($linha->data_emissao ?? null)); ?></small>
        </td>
        <td><?php echo htmlspecialchars($linha->entidade_nome ?? ''); ?></td>
        <td><small><?php echo htmlspecialchars(trim((string) ($linha->plano_codigo ?? '') . ' - ' . (string) ($linha->plano_nome ?? ''))); ?></small></td>
        <td class="text-end fw-semibold"><?php echo htmlspecialchars($formatarValor((float) ($linha->valor ?? 0))); ?></td>
        <td><span class="badge bg-<?php echo htmlspecialchars($statusClass[$status] ?? 'info'); ?>"><?php echo htmlspecialchars($statusLabel[$status] ?? ucfirst($status)); ?></span></td>
    </tr>
    <?php
};
?>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <?php if ($tipoComparativo): ?><th class="ps-4">Tipo</th><?php endif; ?>
                <th class="<?php echo $tipoComparativo ? '' : 'ps-4'; ?>">Data</th>
                <th>Descrição</th>
                <th>Fornecedor / Cliente</th>
                <th>Plano de Contas</th>
                <th class="text-end">Valor</th>
                <th class="pe-4">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($agrupado): ?>
                <?php foreach ($grupos as $grupo): ?>
                    <tr class="table-secondary">
                        <td colspan="<?php echo $tipoComparativo ? 7 : 6; ?>" class="fw-bold">
                            <?php echo htmlspecialchars($grupo['titulo']); ?>
                            <span class="float-end">Subtotal: <?php echo htmlspecialchars($formatarValor((float) $grupo['total'])); ?> · <?php echo (int) $grupo['quantidade']; ?> lançamento(s)</span>
                        </td>
                    </tr>
                    <?php foreach ($grupo['itens'] as $linha): $renderizarLinha($linha); endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($linhas as $linha): $renderizarLinha($linha); endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="<?php echo $tipoComparativo ? 5 : 4; ?>" class="ps-4 fw-bold">Total geral</td>
                <td class="text-end fw-bold"><?php echo htmlspecialchars($formatarValor((float) ($totais['total_geral'] ?? 0))); ?></td>
                <td class="pe-4"></td>
            </tr>
            <?php if ($tipoComparativo): ?>
                <tr>
                    <td colspan="<?php echo $tipoComparativo ? 7 : 6; ?>" class="ps-4 small">
                        Total a pagar: <strong><?php echo htmlspecialchars($formatarValor((float) ($totais['total_pagar'] ?? 0))); ?></strong>
                        <span class="mx-2">|</span>
                        Total a receber: <strong><?php echo htmlspecialchars($formatarValor((float) ($totais['total_receber'] ?? 0))); ?></strong>
                        <span class="mx-2">|</span>
                        Saldo: <strong><?php echo htmlspecialchars($formatarValor((float) ($totais['saldo'] ?? 0))); ?></strong>
                    </td>
                </tr>
            <?php endif; ?>
        </tfoot>
    </table>
</div>

<?php
$total = (int) ($resultado['total_registros'] ?? 0);
$pagina = (int) ($resultado['pagina'] ?? 1);
$porPagina = (int) ($resultado['por_pagina'] ?? 50);
$totalPaginas = max(1, (int) ceil($total / max(1, $porPagina)));
if ($totalPaginas > 1):
    $baseQuery = $filtros;
    $baseQuery['gerar'] = '1';
?>
<div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
    <span class="text-muted small"><?php echo $total; ?> lançamento(s) encontrado(s)</span>
    <nav aria-label="Paginação do relatório">
        <ul class="pagination pagination-sm mb-0">
            <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
                <?php $baseQuery['pagina'] = $p; ?>
                <li class="page-item <?php echo $p === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="/relatorios/financeiro/buscar?<?php echo htmlspecialchars(http_build_query($baseQuery)); ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<?php else: ?>
<div class="px-4 py-3 border-top text-muted small"><?php echo $total; ?> lançamento(s) encontrado(s)</div>
<?php endif; ?>
