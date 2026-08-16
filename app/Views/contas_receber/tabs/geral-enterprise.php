<?php

$action   = $isEdit ? '/financeiro/contas-a-receber/update/' . ($conta->id ?? '') : '/financeiro/contas-a-receber';
$planos   = $planos   ?? [];
$clientes = $clientes ?? [];
$planoSelecionadoId = (int) ($conta->plano_conta_id ?? 0);
$planoSelecionadoRotulo = '';
foreach ($planos as $plano) {
    if ((int) $plano->id === $planoSelecionadoId) {
        $planoSelecionadoRotulo = (string) $plano->codigo . ' - ' . (string) $plano->nome;
        break;
    }
}

// Meios de pagamento Asaas (geram cobrança automática)
$meiosAsaas = ['pix', 'boleto', 'cartao', 'checkout'];
$meioPagamentoAtual = $conta->meio_pagamento ?? '';
?>

<form id="contaReceberFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">
    <?php echo \App\Core\View::csrfField(); ?>

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-hand-holding-usd section-icon"></i>
            Dados Principais
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="cliente_id" class="form-label required">Cliente</label>
                <select name="cliente_id" id="cliente_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($conta->cliente_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo (int)$c->id; ?>" <?php echo ((int)($conta->cliente_id ?? 0) === (int)$c->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c->razao_social ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group position-relative" data-plano-picker data-plano-tipo="Receita" data-plano-contexto="receita">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="plano_conta_busca" class="form-label required mb-1">Plano de Conta — Receita</label>
                    <?php if (\App\Core\Auth::can('create_plano_contas')): ?>
                        <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalNovoPlanoConta">
                            <i class="fas fa-plus me-1"></i>Nova receita
                        </button>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="plano_conta_id" id="plano_conta_id" value="<?php echo $planoSelecionadoId ?: ''; ?>" required>
                <input type="search" id="plano_conta_busca" class="form-control" autocomplete="off"
                    placeholder="Digite código ou nome da receita"
                    value="<?php echo htmlspecialchars($planoSelecionadoRotulo); ?>"
                    aria-autocomplete="list" aria-controls="plano_conta_resultados" aria-expanded="false" required>
                <div id="plano_conta_resultados" class="dropdown-menu w-100 shadow" role="listbox"></div>
                <div class="form-text">Exibe somente contas de receita. Digite para buscar ou cadastre uma nova.</div>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="aberta"    <?php echo ($conta->status ?? 'aberta') === 'aberta'    ? 'selected' : ''; ?>>Aberta</option>
                    <option value="recebida"  <?php echo ($conta->status ?? '') === 'recebida'  ? 'selected' : ''; ?>>Recebida</option>
                    <option value="cancelada" <?php echo ($conta->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="descricao" class="form-label required">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control"
                       placeholder="Ex.: Parcela contrato"
                       value="<?php echo htmlspecialchars($conta->descricao ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="valor" class="form-label required">Valor (R$)</label>
                <!-- O JS converte para float puro antes do envio -->
                <input type="text" name="valor" id="valor" class="form-control"
                       value="<?php echo htmlspecialchars($conta->valor ?? ''); ?>"
                       placeholder="Ex.: 1.500,00"
                       inputmode="numeric"
                       autocomplete="off"
                       required>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="data_vencimento" class="form-label required">Data de Vencimento</label>
                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control"
                       value="<?php echo htmlspecialchars($conta->data_vencimento ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="data_recebimento" class="form-label">Data de Recebimento</label>
                <input type="date" name="data_recebimento" id="data_recebimento" class="form-control"
                       value="<?php echo htmlspecialchars($conta->data_recebimento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="meio_pagamento" class="form-label">Meio de Pagamento</label>
                <select name="meio_pagamento" id="meio_pagamento" class="form-select">
                    <optgroup label="— Pagamento Manual —">
                        <option value=""          <?php echo $meioPagamentoAtual === ''             ? 'selected' : ''; ?>>(Não definido)</option>
                        <option value="dinheiro"  <?php echo $meioPagamentoAtual === 'dinheiro'     ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="transferencia" <?php echo $meioPagamentoAtual === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                        <option value="cartao"    <?php echo $meioPagamentoAtual === 'cartao'       ? 'selected' : ''; ?>>Cartão (manual)</option>
                        <option value="outro"     <?php echo $meioPagamentoAtual === 'outro'        ? 'selected' : ''; ?>>Outro</option>
                    </optgroup>
                    <optgroup label="— Via Asaas (cobrança automática) —">
                        <option value="pix"      <?php echo $meioPagamentoAtual === 'pix'      ? 'selected' : ''; ?>>PIX (gerado pelo sistema)</option>
                        <option value="boleto"   <?php echo $meioPagamentoAtual === 'boleto'   ? 'selected' : ''; ?>>Boleto Bancário (gerado pelo sistema)</option>
                        <option value="cartao"   <?php echo $meioPagamentoAtual === 'cartao'   ? 'selected' : ''; ?>>Cartão de Crédito (gerado pelo sistema)</option>
                        <option value="checkout" <?php echo $meioPagamentoAtual === 'checkout' ? 'selected' : ''; ?>>Checkout Asaas (cliente escolhe o meio)</option>
                    </optgroup>
                </select>
            </div>
        </div>

        <!-- Painel de informação Asaas — exibido dinamicamente pelo JS -->
        <div id="asaas-info-panel" class="mt-3" style="display:none;">
            <!-- PIX -->
            <div id="asaas-info-pix" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-qrcode fa-lg mt-1"></i>
                <div>
                    <strong>PIX via Asaas</strong><br>
                    Ao salvar, o sistema gerará automaticamente uma cobrança PIX no Asaas.
                    O QR Code ficará disponível no portal do cliente para pagamento imediato.
                    O status é atualizado automaticamente via webhook após a confirmação.
                </div>
            </div>
            <!-- Boleto -->
            <div id="asaas-info-boleto" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-barcode fa-lg mt-1"></i>
                <div>
                    <strong>Boleto Bancário via Asaas</strong><br>
                    Ao salvar, o sistema gerará um boleto bancário no Asaas.
                    O link do boleto ficará disponível no portal do cliente para download e pagamento.
                    O status é atualizado automaticamente via webhook após a compensação.
                </div>
            </div>
            <!-- Cartão -->
            <div id="asaas-info-cartao" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-credit-card fa-lg mt-1"></i>
                <div>
                    <strong>Cartão de Crédito via Asaas</strong><br>
                    Ao salvar, o sistema gerará um link de pagamento por cartão de crédito no Asaas.
                    O cliente poderá pagar via cartão no portal. O status é atualizado automaticamente via webhook.
                </div>
            </div>
            <!-- Checkout -->
            <div id="asaas-info-checkout" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-external-link-alt fa-lg mt-1"></i>
                <div>
                    <strong>Checkout Asaas (múltiplos meios)</strong><br>
                    Ao salvar, o sistema gerará um link de checkout no Asaas.
                    No portal do cliente, ao clicar em "Pagar", o cliente será redirecionado para a
                    página de pagamento do Asaas onde poderá escolher entre PIX, Boleto ou Cartão de Crédito.
                    O status é atualizado automaticamente via webhook após a confirmação.
                </div>
            </div>
            <!-- Asaas não configurado -->
            <div id="asaas-info-nao-configurado" class="alert alert-warning d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-exclamation-triangle fa-lg mt-1"></i>
                <div>
                    <strong>Asaas não configurado</strong><br>
                    A integração com o Asaas não está configurada neste ambiente.
                    A conta será salva normalmente, mas a cobrança <strong>não será gerada automaticamente</strong>.
                    Configure a chave API em <a href="/configuracoes/integracoes" class="alert-link">Integrações → Asaas</a>.
                </div>
            </div>
        </div>

        <?php if ($isEdit && !empty($conta->asaas_payment_id)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mt-2">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>Cobrança Asaas vinculada:</strong>
                <code><?php echo htmlspecialchars($conta->asaas_payment_id); ?></code>
                <?php if (!empty($conta->asaas_subscription_id)): ?>
                    &nbsp;|&nbsp; <strong>Assinatura:</strong>
                    <code><?php echo htmlspecialchars($conta->asaas_subscription_id); ?></code>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-grid form-grid-4">
            <div class="form-group">
                <label class="form-label">Recorrente</label>
                <div class="form-input-group">
                    <input type="checkbox" name="recorrente" id="recorrente" value="1"
                           <?php echo !empty($conta->recorrente) ? 'checked' : ''; ?>>
                    <label for="recorrente" class="ms-2">Sim</label>
                </div>
            </div>

            <div class="form-group">
                <label for="recorrencia_tipo" class="form-label">Tipo de Recorrência</label>
                <select name="recorrencia_tipo" id="recorrencia_tipo" class="form-select">
                    <option value="">(Opcional)</option>
                    <option value="mensal"     <?php echo ($conta->recorrencia_tipo ?? '') === 'mensal'     ? 'selected' : ''; ?>>Mensal</option>
                    <option value="trimestral" <?php echo ($conta->recorrencia_tipo ?? '') === 'trimestral' ? 'selected' : ''; ?>>Trimestral</option>
                    <option value="semestral"  <?php echo ($conta->recorrencia_tipo ?? '') === 'semestral'  ? 'selected' : ''; ?>>Semestral</option>
                    <option value="semanal"    <?php echo ($conta->recorrencia_tipo ?? '') === 'semanal'    ? 'selected' : ''; ?>>Semanal</option>
                    <option value="anual"      <?php echo ($conta->recorrencia_tipo ?? '') === 'anual'      ? 'selected' : ''; ?>>Anual</option>
                    <option value="customizada"<?php echo ($conta->recorrencia_tipo ?? '') === 'customizada'? 'selected' : ''; ?>>Customizada</option>
                </select>
            </div>

            <div class="form-group" id="grupo-intervalo">
                <label for="recorrencia_intervalo" class="form-label">Intervalo</label>
                <input type="number" name="recorrencia_intervalo" id="recorrencia_intervalo"
                       class="form-control" min="1" max="999" placeholder="Ex: 1"
                       value="<?php echo htmlspecialchars($conta->recorrencia_intervalo ?? '1'); ?>">
                <small class="text-muted">Repete a cada N períodos</small>
            </div>

            <div class="form-group" id="grupo-total-parcelas">
                <label for="total_parcelas" class="form-label">Total de Parcelas <span class="text-danger">*</span></label>
                <input type="number" name="total_parcelas" id="total_parcelas"
                       class="form-control" min="2" max="360" placeholder="Ex: 12"
                       value="<?php echo htmlspecialchars($conta->total_parcelas ?? ''); ?>">
                <small class="text-muted">Gera todas as parcelas de uma vez</small>
            </div>
        </div>

        <div id="recorrencia-preview" class="alert alert-info mt-2" style="display:none">
            <i class="fas fa-info-circle me-2"></i>
            <span id="recorrencia-preview-texto"></span>
        </div>

        <div class="form-group mt-2">
            <label for="observacoes" class="form-label">Observações</label>
            <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?php echo htmlspecialchars($conta->observacoes ?? ''); ?></textarea>
        </div>

        <!-- ===== NF AVULSA ===== -->
        <div class="mt-3 p-3" style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="emitir_nf_avulsa" id="emitir_nf_avulsa" value="1"
                           <?php echo !empty($conta->emitir_nf_avulsa) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="emitir_nf_avulsa" style="color:#15803d;">
                        <i class="fas fa-file-invoice me-1"></i> Emitir NFS-e Avulsa
                    </label>
                </div>
                <?php if (!empty($conta->nf_avulsa_status)): ?>
                <?php
                    $nfBadgeMap = [
                        'pendente'    => 'bg-warning text-dark',
                        'emitida'     => 'bg-success',
                        'cancelada'   => 'bg-danger',
                        'erro'        => 'bg-danger',
                        'processando' => 'bg-info',
                    ];
                    $nfBadgeCls = $nfBadgeMap[$conta->nf_avulsa_status] ?? 'bg-secondary';
                ?>
                <span class="badge <?php echo $nfBadgeCls; ?>">
                    NF: <?php echo strtoupper(htmlspecialchars($conta->nf_avulsa_status)); ?>
                </span>
                <?php if (!empty($conta->nf_avulsa_nota_id)): ?>
                <a href="/faturamento/notas-fiscais" target="_blank" class="btn btn-sm btn-outline-success py-0">
                    <i class="fas fa-external-link-alt me-1"></i>Ver Nota
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div id="nf-avulsa-info" style="<?php echo empty($conta->emitir_nf_avulsa) ? 'display:none' : ''; ?>">
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Ao salvar, o sistema emitirá automaticamente uma NFS-e via Asaas vinculada a esta conta.
                    A nota ficará disponível em
                    <a href="/faturamento/notas-fiscais" target="_blank">Faturamento → Notas Fiscais</a>.
                    Certifique-se de que as configurações de NF estão preenchidas em
                    <a href="/configuracoes?tab=notas-fiscais" target="_blank">Configurações → Notas Fiscais</a>.
                </small>
                <?php if (!empty($conta->nf_avulsa_nota_id)): ?>
                <small class="text-success d-block mt-1">
                    <i class="fas fa-check-circle me-1"></i>
                    Nota emitida — ID Asaas: <code><?php echo htmlspecialchars($conta->nf_avulsa_nota_id); ?></code>
                </small>
                <?php endif; ?>
                <?php if (!empty($conta->nf_avulsa_status) && $conta->nf_avulsa_status === 'erro'): ?>
                <small class="text-danger d-block mt-1">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    Erro na emissão. Verifique as configurações em
                    <a href="/configuracoes?tab=notas-fiscais" target="_blank">Configurações → Notas Fiscais</a>.
                </small>
                <?php endif; ?>
            </div>
        </div>
        <!-- ===== FIM NF AVULSA ===== -->

    </section>

</form>

<?php if (\App\Core\Auth::can('create_plano_contas')): ?>
    <div class="modal fade" id="modalNovoPlanoConta" tabindex="-1" aria-labelledby="modalNovoPlanoContaTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalNovoPlanoContaTitulo"><i class="fas fa-plus-circle me-2"></i>Nova conta de receita</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formNovoPlanoContaRapido" novalidate>
                    <div class="modal-body">
                        <div id="plano_conta_rapido_feedback" class="alert d-none" role="alert"></div>
                        <input type="hidden" id="plano_conta_rapido_tipo" value="Receita">
                        <div class="mb-3">
                            <label for="plano_conta_rapido_nome" class="form-label required">Nome da receita</label>
                            <input type="text" id="plano_conta_rapido_nome" class="form-control" placeholder="Ex.: Receita de serviços" maxlength="255" required>
                        </div>
                        <div class="mb-0">
                            <label for="plano_conta_rapido_codigo" class="form-label">Código</label>
                            <input type="text" id="plano_conta_rapido_codigo" class="form-control" placeholder="Opcional — será gerado automaticamente">
                            <div class="form-text">O código deve ser único neste tenant.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnSalvarPlanoContaRapido"><i class="fas fa-save me-1"></i>Salvar e selecionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="/assets/js/plano-conta-rapido.js"></script>
<script>
// Preview dinâmico de recorrência
(function () {
    var isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;

    function updateRecorrenciaPreview() {
        var recorrente = document.getElementById('recorrente');
        var tipo       = document.getElementById('recorrencia_tipo');
        var intervalo  = document.getElementById('recorrencia_intervalo');
        var total      = document.getElementById('total_parcelas');
        var preview    = document.getElementById('recorrencia-preview');
        var previewTxt = document.getElementById('recorrencia-preview-texto');
        var grupoTotal = document.getElementById('grupo-total-parcelas');

        if (!recorrente || !tipo || !preview) return;

        var ativo = recorrente.checked && tipo.value !== '';
        grupoTotal.style.display = ativo ? '' : 'none';

        if (!ativo || isEdit) { preview.style.display = 'none'; return; }

        var n = parseInt(total ? total.value : 0) || 0;
        var inv = parseInt(intervalo ? intervalo.value : 1) || 1;
        if (n < 2) { preview.style.display = 'none'; return; }

        var tipoLabel = {mensal:'mês',trimestral:'trimestre',semestral:'semestre',semanal:'semana',anual:'ano',customizada:'período'};
        var label = tipoLabel[tipo.value] || tipo.value;
        var msg = 'Serão geradas <strong>' + n + ' parcelas</strong> com vencimento a cada <strong>' + inv + ' ' + label + (inv > 1 ? 's' : '') + '</strong>.';
        previewTxt.innerHTML = msg;
        preview.style.display = 'block';
    }

    ['recorrente','recorrencia_tipo','recorrencia_intervalo','total_parcelas'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', updateRecorrenciaPreview);
        if (el && el.tagName === 'INPUT' && el.type === 'number') el.addEventListener('input', updateRecorrenciaPreview);
    });

    updateRecorrenciaPreview();
})();

// Painel de informação dinâmico para meios de pagamento Asaas
(function () {
    var asaasConfigured = <?php echo \App\Services\AsaasService::isConfigured() ? 'true' : 'false'; ?>;
    var meiosAsaas      = ['pix', 'boleto', 'cartao', 'checkout'];

    function updateAsaasPanel() {
        var meio  = document.getElementById('meio_pagamento');
        var panel = document.getElementById('asaas-info-panel');
        if (!meio || !panel) return;

        var val = meio.value;

        // Esconde todos os sub-painéis
        ['pix', 'boleto', 'cartao', 'checkout', 'nao-configurado'].forEach(function (k) {
            var el = document.getElementById('asaas-info-' + k);
            if (el) el.style.display = 'none';
        });

        if (meiosAsaas.indexOf(val) === -1) {
            panel.style.display = 'none';
            return;
        }

        panel.style.display = 'block';

        if (!asaasConfigured) {
            document.getElementById('asaas-info-nao-configurado').style.display = 'flex';
        } else {
            var target = document.getElementById('asaas-info-' + val);
            if (target) target.style.display = 'flex';
        }
    }

    var select = document.getElementById('meio_pagamento');
    if (select) {
        select.addEventListener('change', updateAsaasPanel);
        updateAsaasPanel(); // estado inicial
    }
})();

// Toggle painel NF Avulsa
(function () {
    var chk   = document.getElementById('emitir_nf_avulsa');
    var panel = document.getElementById('nf-avulsa-info');
    if (!chk || !panel) return;
    chk.addEventListener('change', function () {
        panel.style.display = chk.checked ? '' : 'none';
    });
})();
</script>
