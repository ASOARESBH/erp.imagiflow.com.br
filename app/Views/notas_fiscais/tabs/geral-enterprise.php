<?php

$action  = $isEdit ? '/faturamento/notas-fiscais/update/' . ($nota->id ?? '') : '/faturamento/notas-fiscais';
$clientes = $clientes ?? [];
$configNfs = $configNfs ?? null;   // passado pelo controller ao abrir o form

// Origem da emissão (para edição)
$origemEmissao = $nota->origem_emissao ?? 'manual';
$isAsaas       = ($origemEmissao === 'asaas');
?>

<form id="notaFiscalFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- ── Seção: Dados Principais ─────────────────────────────────────── -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-file-invoice section-icon"></i>
            Dados da Nota Fiscal
        </h2>

        <div class="form-grid form-grid-3">
            <!-- Cliente -->
            <div class="form-group">
                <label for="cliente_id" class="form-label required">Cliente</label>
                <select name="cliente_id" id="cliente_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($nota->cliente_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo (int)$c->id; ?>"
                            <?php echo ((int)($nota->cliente_id ?? 0) === (int)$c->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($c->razao_social ?? '') . ' (' . ($c->cpf_cnpj ?? '') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data de Emissão -->
            <div class="form-group">
                <label for="data_emissao" class="form-label required">Data de Emissão</label>
                <input type="date" name="data_emissao" id="data_emissao" class="form-control"
                       value="<?php echo htmlspecialchars($nota->data_emissao ?? date('Y-m-d')); ?>" required>
            </div>

            <!-- Valor Total -->
            <div class="form-group">
                <label for="valor_total" class="form-label required">Valor Total (R$)</label>
                <input type="number" step="0.01" min="0.01" name="valor_total" id="valor_total" class="form-control"
                       value="<?php echo htmlspecialchars($nota->valor_total ?? ''); ?>"
                       placeholder="0,00" required>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <!-- Número NF (obrigatório apenas para emissão manual) -->
            <div class="form-group" id="grp_numero_nf">
                <label for="numero_nf" class="form-label" id="lbl_numero_nf">
                    Número da NF
                    <span class="text-muted small" id="hint_numero_nf">(preenchido automaticamente pelo Asaas)</span>
                </label>
                <input type="text" name="numero_nf" id="numero_nf" class="form-control"
                       value="<?php echo htmlspecialchars($nota->numero_nf ?? ''); ?>"
                       placeholder="Deixe em branco para emissão via Asaas">
            </div>

            <!-- Série -->
            <div class="form-group">
                <label for="serie" class="form-label">Série</label>
                <input type="text" name="serie" id="serie" class="form-control"
                       value="<?php echo htmlspecialchars($nota->serie ?? ($configNfs->serie_nf ?? '')); ?>"
                       placeholder="Ex: 1">
            </div>
        </div>

        <!-- Status (apenas para edição manual) -->
        <?php if ($isEdit && !$isAsaas): ?>
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="rascunho"    <?php echo ($nota->status ?? 'rascunho') === 'rascunho'    ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="emitida"     <?php echo ($nota->status ?? '') === 'emitida'     ? 'selected' : ''; ?>>Emitida</option>
                    <option value="importada"   <?php echo ($nota->status ?? '') === 'importada'   ? 'selected' : ''; ?>>Importada</option>
                    <option value="cancelada"   <?php echo ($nota->status ?? '') === 'cancelada'   ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>
        <?php elseif (!$isEdit): ?>
        <input type="hidden" name="status" value="rascunho">
        <?php endif; ?>

        <?php if (!empty($nota->xml_path)): ?>
        <div class="alert alert-light border mt-3 mb-0">
            <strong>XML vinculado:</strong> <?php echo htmlspecialchars($nota->xml_path); ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Seção: Emissão via Asaas ────────────────────────────────────── -->
    <?php if (!$isEdit || !$isAsaas): ?>
    <section class="form-section mt-3">
        <h2 class="form-section-title">
            <i class="fas fa-bolt section-icon" style="color:#00b37e"></i>
            Emissão via Asaas (NFS-e)
        </h2>

        <?php if ($isEdit && $isAsaas): ?>
        <div class="alert alert-info py-2 small">
            <i class="fas fa-info-circle me-1"></i>
            Esta nota foi emitida via Asaas. Para reemitir, use o botão na tela de visualização.
        </div>
        <?php else: ?>

        <!-- Checkbox: emitir via Asaas -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="emitir_via_asaas" id="emitir_via_asaas" value="1"
                   <?php echo !empty($_GET['asaas']) ? 'checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="emitir_via_asaas" style="color:#15803d;">
                <i class="fas fa-bolt me-1" style="color:#00b37e"></i>
                Emitir NFS-e via Asaas ao salvar
            </label>
            <div class="text-muted small mt-1">
                Ao marcar esta opção, a nota será enviada ao Asaas para emissão junto à prefeitura.
                O número e PDF serão preenchidos automaticamente após a autorização.
            </div>
        </div>

        <!-- Painel de dados de serviço (visível quando Asaas marcado) -->
        <div id="painel_asaas_nf" style="<?php echo !empty($_GET['asaas']) ? '' : 'display:none'; ?>">

            <div class="alert alert-light border-start border-4 border-success py-2 mb-3 small">
                <i class="fas fa-info-circle me-1 text-success"></i>
                Os campos abaixo complementam os dados configurados em
                <a href="/configuracoes?tab=notas-fiscais" target="_blank">Configurações → Notas Fiscais</a>.
                Se não preenchidos, serão usados os valores padrão das configurações.
            </div>

            <div class="form-grid form-grid-2">
                <!-- Descrição do Serviço -->
                <div class="form-group">
                    <label for="servico_descricao" class="form-label">Descrição do Serviço</label>
                    <input type="text" name="servico_descricao" id="servico_descricao" class="form-control"
                           value="<?php echo htmlspecialchars($nota->servico_descricao ?? ($configNfs->service_description ?? 'PRESTAÇÃO DE SERVIÇOS')); ?>"
                           placeholder="Ex: PRESTAÇÃO DE SERVIÇOS DE RADIOLOGIA">
                </div>

                <!-- Código do Serviço Municipal -->
                <div class="form-group">
                    <label for="servico_codigo" class="form-label">Código do Serviço Municipal</label>
                    <input type="text" name="servico_codigo" id="servico_codigo" class="form-control"
                           value="<?php echo htmlspecialchars($nota->servico_codigo ?? ($configNfs->municipal_service_code ?? '')); ?>"
                           placeholder="Ex: 14.02.01.001">
                </div>
            </div>

            <div class="form-grid form-grid-2">
                <!-- Deduções -->
                <div class="form-group">
                    <label for="deducoes" class="form-label">Deduções (R$)</label>
                    <input type="number" step="0.01" min="0" name="deducoes" id="deducoes" class="form-control"
                           value="<?php echo htmlspecialchars($nota->deducoes ?? ($configNfs->deductions ?? '0.00')); ?>"
                           placeholder="0,00">
                </div>

                <!-- Observações -->
                <div class="form-group">
                    <label for="observacoes_nf" class="form-label">Observações da NF</label>
                    <input type="text" name="observacoes_nf" id="observacoes_nf" class="form-control"
                           value="<?php echo htmlspecialchars($nota->observacoes_nf ?? ($configNfs->observations ?? '')); ?>"
                           placeholder="Observações que aparecerão na nota">
                </div>
            </div>

            <!-- Conta a Receber vinculada (opcional) -->
            <div class="form-group">
                <label for="conta_receber_id" class="form-label">
                    Vincular à Conta a Receber
                    <span class="text-muted small">(opcional — vincula o pagamento Asaas à NF)</span>
                </label>
                <input type="number" name="conta_receber_id" id="conta_receber_id" class="form-control"
                       value="<?php echo htmlspecialchars($nota->conta_receber_id ?? ''); ?>"
                       placeholder="ID da conta a receber (deixe em branco para emissão avulsa)">
                <div class="form-text text-muted">
                    Se informado, o sistema buscará o pagamento Asaas vinculado para associar à NF.
                </div>
            </div>

        </div><!-- /painel_asaas_nf -->
        <?php endif; ?>
    </section>
    <?php endif; ?>

</form>

<script>
(function() {
    var chk   = document.getElementById('emitir_via_asaas');
    var painel = document.getElementById('painel_asaas_nf');
    var hintNumero = document.getElementById('hint_numero_nf');
    var inputNumero = document.getElementById('numero_nf');

    function togglePainel() {
        if (!chk || !painel) return;
        if (chk.checked) {
            painel.style.display = '';
            if (hintNumero) hintNumero.style.display = '';
            if (inputNumero) inputNumero.removeAttribute('required');
        } else {
            painel.style.display = 'none';
            if (hintNumero) hintNumero.style.display = 'none';
        }
    }

    if (chk) {
        chk.addEventListener('change', togglePainel);
        togglePainel(); // estado inicial
    }
})();
</script>
