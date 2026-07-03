<?php
use App\Core\UI;
use App\Core\Auth;
use App\Models\DdaBoleto;

// Contagens para os badges
$cPendente   = (int)($contagens['pendente']    ?? 0);
$cImportado  = (int)($contagens['importado']   ?? 0);
$cPago       = (int)(($contagens['pago_asaas'] ?? 0) + ($contagens['pago_inlaudo'] ?? 0));
$cIgnorado   = (int)($contagens['ignorado']    ?? 0);
$cTotal      = $cPendente + $cImportado + $cPago + $cIgnorado;

$actions = [];
if (Auth::can('edit_contas_pagar')) {
    $actions[] = [
        'text'  => 'Sincronizar com Asaas',
        'href'  => '#',
        'icon'  => 'fas fa-sync-alt',
        'class' => 'btn-primary',
        'id'    => 'btn-sincronizar-dda',
    ];
}
UI::sectionHeader('Pagamento DDA', 'Débito Direto Autorizado — boletos recebidos via Asaas', $actions);
?>

<!-- Navegação de abas do módulo financeiro -->
<ul class="nav nav-tabs mb-4 border-bottom">
    <li class="nav-item">
        <a class="nav-link text-muted" href="/financeiro/contas-a-pagar">
            <i class="fas fa-file-invoice-dollar me-1"></i> Contas a Pagar
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-bold" href="/financeiro/contas-a-pagar/dda">
            <i class="fas fa-barcode me-1"></i> Pagamento DDA
        </a>
    </li>
</ul>

<!-- Cartões de resumo -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning"><?php echo $cPendente; ?></div>
            <div class="small text-muted">Pendentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info"><?php echo $cImportado; ?></div>
            <div class="small text-muted">Importados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?php echo $cPago; ?></div>
            <div class="small text-muted">Pagos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-secondary"><?php echo $cTotal; ?></div>
            <div class="small text-muted">Total</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/financeiro/contas-a-pagar/dda" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Pesquisar beneficiário</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                        placeholder="Nome ou CPF/CNPJ..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="pendente"    <?php echo ($filtros['status_interno'] ?? '') === 'pendente'    ? 'selected' : ''; ?>>Pendente</option>
                    <option value="importado"   <?php echo ($filtros['status_interno'] ?? '') === 'importado'   ? 'selected' : ''; ?>>Importado</option>
                    <option value="pago_asaas"  <?php echo ($filtros['status_interno'] ?? '') === 'pago_asaas'  ? 'selected' : ''; ?>>Pago (Asaas)</option>
                    <option value="pago_inlaudo"<?php echo ($filtros['status_interno'] ?? '') === 'pago_inlaudo'? 'selected' : ''; ?>>Pago (InLaudo)</option>
                    <option value="ignorado"    <?php echo ($filtros['status_interno'] ?? '') === 'ignorado'    ? 'selected' : ''; ?>>Ignorado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Venc. de</label>
                <input type="date" name="venc_de" class="form-control form-control-sm"
                    value="<?php echo htmlspecialchars($filtros['venc_de'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Venc. até</label>
                <input type="date" name="venc_ate" class="form-control form-control-sm"
                    value="<?php echo htmlspecialchars($filtros['venc_ate'] ?? ''); ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                <a href="/financeiro/contas-a-pagar/dda" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de boletos DDA -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($boletos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-barcode fa-3x mb-3 d-block opacity-25"></i>
                <p class="mb-1 fw-bold">Nenhum boleto DDA encontrado.</p>
                <p class="small">Clique em <strong>Sincronizar com Asaas</strong> para importar os boletos recebidos.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Beneficiário</th>
                            <th>CPF/CNPJ</th>
                            <th class="text-end">Valor</th>
                            <th>Vencimento</th>
                            <th>Situação</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletos as $b): ?>
                            <?php
                            $statusInterno = $b->status_interno ?? 'pendente';
                            $isPago    = in_array($statusInterno, ['pago_asaas', 'pago_inlaudo']);
                            $isIgnorado = $statusInterno === 'ignorado';
                            $isImportado = $statusInterno === 'importado';
                            ?>
                            <tr class="<?php echo $isIgnorado ? 'opacity-50' : ''; ?>">
                                <td class="ps-3">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($b->beneficiario_nome ?? '—'); ?></span>
                                    <?php if (!empty($b->beneficiario_banco)): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($b->beneficiario_banco); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars($b->beneficiario_cpf_cnpj ?? '—'); ?></td>
                                <td class="text-end fw-bold">
                                    R$ <?php echo number_format((float)($b->valor_final ?? $b->valor ?? 0), 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <?php
                                    $dataVenc = $b->data_vencimento ?? '';
                                    if ($dataVenc) {
                                        echo date('d/m/Y', strtotime($dataVenc));
                                        if (!$isPago && !$isIgnorado) {
                                            echo ' ' . DdaBoleto::vencimentoBadge($dataVenc);
                                        }
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo DdaBoleto::statusBadge($statusInterno); ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Ver detalhes -->
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary btn-dda-detalhar"
                                            data-id="<?php echo (int)$b->id; ?>"
                                            title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if (Auth::can('edit_contas_pagar') && !$isPago && !$isIgnorado): ?>

                                            <?php if ($statusInterno === 'pendente'): ?>
                                                <!-- Importar para Contas a Pagar -->
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-info btn-dda-importar"
                                                    data-id="<?php echo (int)$b->id; ?>"
                                                    data-beneficiario="<?php echo htmlspecialchars($b->beneficiario_nome ?? ''); ?>"
                                                    data-valor="<?php echo number_format((float)($b->valor_final ?? $b->valor ?? 0), 2, ',', '.'); ?>"
                                                    title="Importar para Contas a Pagar">
                                                    <i class="fas fa-file-import"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Confirmar pagamento -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-success btn-dda-pagar"
                                                data-id="<?php echo (int)$b->id; ?>"
                                                data-beneficiario="<?php echo htmlspecialchars($b->beneficiario_nome ?? ''); ?>"
                                                data-valor="<?php echo number_format((float)($b->valor_final ?? $b->valor ?? 0), 2, ',', '.'); ?>"
                                                title="Confirmar pagamento">
                                                <i class="fas fa-check-circle"></i>
                                            </button>

                                            <!-- Ignorar -->
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-dda-ignorar"
                                                data-id="<?php echo (int)$b->id; ?>"
                                                title="Ignorar boleto">
                                                <i class="fas fa-ban"></i>
                                            </button>

                                        <?php endif; ?>

                                        <?php if ($isImportado && $b->conta_pagar_id): ?>
                                            <a href="/financeiro/contas-a-pagar/edit/<?php echo (int)$b->conta_pagar_id; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Ver conta a pagar">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 text-muted small border-top">
                <?php echo count($boletos); ?> boleto(s) encontrado(s)
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =====================================================================
     MODAL: Detalhes do Boleto DDA
     ===================================================================== -->
<div class="modal fade" id="modalDdaDetalhar" tabindex="-1" aria-labelledby="modalDdaDetalharLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalDdaDetalharLabel">
                    <i class="fas fa-barcode me-2 text-primary"></i> Detalhes do Boleto DDA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDdaDetalharBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL: Importar para Contas a Pagar
     ===================================================================== -->
<div class="modal fade" id="modalDdaImportar" tabindex="-1" aria-labelledby="modalDdaImportarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info bg-opacity-10">
                <h5 class="modal-title" id="modalDdaImportarLabel">
                    <i class="fas fa-file-import me-2 text-info"></i> Importar para Contas a Pagar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Importar o boleto de <strong id="importar-beneficiario"></strong>
                    no valor de <strong>R$ <span id="importar-valor"></span></strong> para o módulo de Contas a Pagar.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descrição <span class="text-danger">*</span></label>
                    <input type="text" id="importar-descricao" class="form-control" placeholder="Ex: Mensalidade SISBRA Empresarial">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Plano de Contas</label>
                    <select id="importar-plano" class="form-select">
                        <option value="">— Selecionar plano —</option>
                        <?php foreach ($planos as $p): ?>
                            <option value="<?php echo (int)$p->id; ?>">
                                <?php echo htmlspecialchars(($p->codigo ?? '') . ' — ' . ($p->nome ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Fornecedor</label>
                    <select id="importar-fornecedor" class="form-select">
                        <option value="">— Selecionar fornecedor —</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?php echo (int)$f->id; ?>">
                                <?php echo htmlspecialchars($f->nome ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Observação</label>
                    <textarea id="importar-observacao" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info text-white fw-bold" id="btn-confirmar-importar">
                    <i class="fas fa-file-import me-1"></i> Importar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL: Confirmar Pagamento
     ===================================================================== -->
<div class="modal fade" id="modalDdaPagar" tabindex="-1" aria-labelledby="modalDdaPagarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10">
                <h5 class="modal-title" id="modalDdaPagarLabel">
                    <i class="fas fa-check-circle me-2 text-success"></i> Confirmar Pagamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Confirmar pagamento do boleto de <strong id="pagar-beneficiario"></strong>
                    no valor de <strong>R$ <span id="pagar-valor"></span></strong>.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Data do Pagamento <span class="text-danger">*</span></label>
                    <input type="date" id="pagar-data" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Pago por</label>
                    <div class="d-flex gap-3 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pago_por_radio" id="pago-inlaudo" value="inlaudo" checked>
                            <label class="form-check-label" for="pago-inlaudo">
                                <i class="fas fa-building me-1 text-primary"></i> InLaudo
                                <small class="text-muted d-block">Registrar apenas no sistema</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pago_por_radio" id="pago-asaas" value="asaas">
                            <label class="form-check-label" for="pago-asaas">
                                <i class="fas fa-bolt me-1 text-warning"></i> Asaas
                                <small class="text-muted d-block">Pagar via API do Asaas</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning small py-2 mb-0" id="aviso-pagar-asaas" style="display:none;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Atenção:</strong> Esta ação irá efetuar o pagamento real via API do Asaas. Certifique-se de que há saldo suficiente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold" id="btn-confirmar-pagar">
                    <i class="fas fa-check me-1"></i> Confirmar Pagamento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL: Sincronizar DDA
     ===================================================================== -->
<div class="modal fade" id="modalDdaSincronizar" tabindex="-1" aria-labelledby="modalDdaSincronizarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-10">
                <h5 class="modal-title" id="modalDdaSincronizarLabel">
                    <i class="fas fa-sync-alt me-2 text-primary"></i> Sincronizar Boletos DDA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Busca os boletos DDA disponíveis na sua conta Asaas e os importa para o sistema.
                    Boletos já existentes serão atualizados.
                </p>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small">Vencimento de</label>
                        <input type="date" id="sinc-venc-de" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small">Vencimento até</label>
                        <input type="date" id="sinc-venc-ate" class="form-control form-control-sm">
                    </div>
                </div>
                <div id="sinc-resultado" class="mt-3" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary fw-bold" id="btn-executar-sinc">
                    <i class="fas fa-sync-alt me-1"></i> Sincronizar Agora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     JAVASCRIPT
     ===================================================================== -->
<script>
(function () {
    'use strict';

    let ddaIdAtual = null;

    // -----------------------------------------------------------------------
    // Utilitários
    // -----------------------------------------------------------------------
    function showToast(msg, type) {
        type = type || 'success';
        const bg = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-warning text-dark');
        const el = document.createElement('div');
        el.className = 'toast align-items-center text-white ' + bg + ' border-0 show position-fixed bottom-0 end-0 m-3';
        el.style.zIndex = 9999;
        el.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function postJson(url, data, cb) {
        const form = new FormData();
        Object.keys(data).forEach(k => form.append(k, data[k]));
        fetch(url, { method: 'POST', body: form })
            .then(r => r.json())
            .then(cb)
            .catch(err => cb({ success: false, message: err.message }));
    }

    // -----------------------------------------------------------------------
    // Botão Sincronizar
    // -----------------------------------------------------------------------
    const btnSinc = document.getElementById('btn-sincronizar-dda');
    if (btnSinc) {
        btnSinc.addEventListener('click', function () {
            new bootstrap.Modal(document.getElementById('modalDdaSincronizar')).show();
        });
    }

    document.getElementById('btn-executar-sinc').addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sincronizando...';
        const res = document.getElementById('sinc-resultado');
        res.style.display = 'none';

        postJson('/financeiro/contas-a-pagar/dda/sincronizar', {
            dueDateStart: document.getElementById('sinc-venc-de').value,
            dueDateEnd:   document.getElementById('sinc-venc-ate').value,
        }, function (data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Sincronizar Agora';
            res.style.display = 'block';
            if (data.success) {
                res.innerHTML = '<div class="alert alert-success py-2 mb-0 small">'
                    + '<i class="fas fa-check-circle me-1"></i> ' + data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                res.innerHTML = '<div class="alert alert-danger py-2 mb-0 small">'
                    + '<i class="fas fa-times-circle me-1"></i> ' + (data.message || 'Erro ao sincronizar.') + '</div>';
            }
        });
    });

    // -----------------------------------------------------------------------
    // Botão Ver Detalhes
    // -----------------------------------------------------------------------
    document.querySelectorAll('.btn-dda-detalhar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const body = document.getElementById('modalDdaDetalharBody');
            body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';
            new bootstrap.Modal(document.getElementById('modalDdaDetalhar')).show();

            fetch('/financeiro/contas-a-pagar/dda/detalhar/' + id)
                .then(r => r.json())
                .then(function (data) {
                    if (!data.success) {
                        body.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Erro.') + '</div>';
                        return;
                    }
                    const b = data.boleto;
                    const fmt = v => 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    const dt  = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('pt-BR') : '—';
                    body.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Beneficiário</div>
                                    <div class="fw-bold">${b.beneficiario_nome || '—'}</div>
                                    <div class="small text-muted mt-1">${b.beneficiario_cpf_cnpj || ''}</div>
                                    <div class="small text-muted">${b.beneficiario_banco || ''}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Valores</div>
                                    <div class="fw-bold fs-5 text-primary">${fmt(b.valor_final || b.valor)}</div>
                                    ${b.valor_desconto > 0 ? '<div class="small text-success">Desconto: ' + fmt(b.valor_desconto) + '</div>' : ''}
                                    ${b.valor_juros > 0 ? '<div class="small text-danger">Juros: ' + fmt(b.valor_juros) + '</div>' : ''}
                                    ${b.valor_multa > 0 ? '<div class="small text-danger">Multa: ' + fmt(b.valor_multa) + '</div>' : ''}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Vencimento</div>
                                    <div class="fw-bold">${dt(b.data_vencimento)}</div>
                                    ${b.data_limite_pagamento ? '<div class="small text-muted">Limite: ' + dt(b.data_limite_pagamento) + '</div>' : ''}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Status</div>
                                    <div>${b.status_interno || '—'}</div>
                                    ${b.pago_em ? '<div class="small text-muted">Pago em: ' + dt(b.pago_em) + '</div>' : ''}
                                    ${b.pago_por ? '<div class="small text-muted">Pago por: ' + b.pago_por + '</div>' : ''}
                                </div>
                            </div>
                            ${b.linha_digitavel ? `
                            <div class="col-12">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted mb-1">Linha Digitável</div>
                                    <div class="font-monospace small fw-bold text-break">${b.linha_digitavel}</div>
                                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="navigator.clipboard.writeText('${b.linha_digitavel}').then(()=>this.textContent='Copiado!')">
                                        <i class="fas fa-copy me-1"></i> Copiar
                                    </button>
                                </div>
                            </div>` : ''}
                            ${b.descricao ? `<div class="col-12"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Descrição</div><div>${b.descricao}</div></div></div>` : ''}
                        </div>`;
                })
                .catch(() => {
                    body.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes.</div>';
                });
        });
    });

    // -----------------------------------------------------------------------
    // Botão Importar
    // -----------------------------------------------------------------------
    document.querySelectorAll('.btn-dda-importar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ddaIdAtual = this.dataset.id;
            document.getElementById('importar-beneficiario').textContent = this.dataset.beneficiario;
            document.getElementById('importar-valor').textContent = this.dataset.valor;
            document.getElementById('importar-descricao').value = 'Boleto DDA — ' + this.dataset.beneficiario;
            new bootstrap.Modal(document.getElementById('modalDdaImportar')).show();
        });
    });

    document.getElementById('btn-confirmar-importar').addEventListener('click', function () {
        if (!ddaIdAtual) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Importando...';

        postJson('/financeiro/contas-a-pagar/dda/importar/' + ddaIdAtual, {
            descricao:     document.getElementById('importar-descricao').value,
            plano_id:      document.getElementById('importar-plano').value,
            fornecedor_id: document.getElementById('importar-fornecedor').value,
            observacao:    document.getElementById('importar-observacao').value,
        }, function (data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-file-import me-1"></i> Importar';
            bootstrap.Modal.getInstance(document.getElementById('modalDdaImportar')).hide();
            if (data.success) {
                showToast('<i class="fas fa-check me-1"></i> ' + data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('<i class="fas fa-times me-1"></i> ' + (data.message || 'Erro ao importar.'), 'danger');
            }
        });
    });

    // -----------------------------------------------------------------------
    // Botão Confirmar Pagamento
    // -----------------------------------------------------------------------
    document.querySelectorAll('.btn-dda-pagar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ddaIdAtual = this.dataset.id;
            document.getElementById('pagar-beneficiario').textContent = this.dataset.beneficiario;
            document.getElementById('pagar-valor').textContent = this.dataset.valor;
            document.getElementById('pagar-data').value = new Date().toISOString().slice(0, 10);
            document.getElementById('aviso-pagar-asaas').style.display = 'none';
            document.getElementById('pago-inlaudo').checked = true;
            new bootstrap.Modal(document.getElementById('modalDdaPagar')).show();
        });
    });

    // Mostrar aviso quando selecionar Asaas
    document.querySelectorAll('input[name="pago_por_radio"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('aviso-pagar-asaas').style.display =
                this.value === 'asaas' ? 'block' : 'none';
        });
    });

    document.getElementById('btn-confirmar-pagar').addEventListener('click', function () {
        if (!ddaIdAtual) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Confirmando...';

        const pagoPor = document.querySelector('input[name="pago_por_radio"]:checked').value;
        const dataPag = document.getElementById('pagar-data').value;

        postJson('/financeiro/contas-a-pagar/dda/pagar/' + ddaIdAtual, {
            pago_por:       pagoPor,
            data_pagamento: dataPag,
        }, function (data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Confirmar Pagamento';
            bootstrap.Modal.getInstance(document.getElementById('modalDdaPagar')).hide();
            if (data.success) {
                showToast('<i class="fas fa-check me-1"></i> ' + data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('<i class="fas fa-times me-1"></i> ' + (data.message || 'Erro ao confirmar.'), 'danger');
            }
        });
    });

    // -----------------------------------------------------------------------
    // Botão Ignorar
    // -----------------------------------------------------------------------
    document.querySelectorAll('.btn-dda-ignorar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Deseja ignorar este boleto? Ele não será excluído, apenas marcado como ignorado.')) return;
            const id = this.dataset.id;
            postJson('/financeiro/contas-a-pagar/dda/ignorar/' + id, {}, function (data) {
                if (data.success) {
                    showToast('Boleto ignorado.', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Erro ao ignorar.', 'danger');
                }
            });
        });
    });

}());
</script>
