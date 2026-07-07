<?php
$viagem = $viagem ?? null;
$rotas  = $rotas  ?? [];
$isEdit = $viagem !== null;
$action = $isEdit ? "/rdv/viagens/{$viagem->id}/update" : '/rdv/viagens/store';
?>
<style>
.rdv-form-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);max-width:860px;margin:0 auto}
.rdv-form-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.rota-livre-badge{display:inline-block;font-size:.7rem;padding:.2em .6em;background:#fef3c7;color:#92400e;border-radius:20px;font-weight:600}
</style>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
  <?= htmlspecialchars($_SESSION['flash_error']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid">
  <div class="rdv-form-card">
    <div class="rdv-form-title">
      <i class="fas fa-route text-primary"></i>
      <?= $isEdit ? 'Editar Viagem — ' . htmlspecialchars($viagem->codigo) : 'Nova Viagem / RDV' ?>
    </div>

    <form method="POST" action="<?= $action ?>">
      <div class="row g-3">

        <!-- Nome -->
        <div class="col-12">
          <label class="form-label fw-semibold">Nome da Viagem <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control" id="inputNome"
                 value="<?= htmlspecialchars($viagem->nome ?? '') ?>"
                 placeholder="Ex: Visita Triângulo Mineiro — Jul/2026">
          <div class="form-text">Se deixar vazio, o nome será gerado automaticamente com o período.</div>
        </div>

        <!-- Rota -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Rota Comercial</label>
          <select name="rota_id" class="form-select" id="selectRota">
            <option value="">— Sem rota (avulsa) —</option>
            <?php foreach ($rotas as $r): ?>
            <option value="<?= $r->id ?>"
                    data-tipo="<?= $r->tipo ?>"
                    <?= (int)($viagem->rota_id ?? 0) === (int)$r->id ? 'selected' : '' ?>>
              <?= htmlspecialchars($r->nome) ?>
              <?php if ($r->tipo === 'livre'): ?> [Livre]<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div id="rotaLivreBadge" class="mt-1" style="display:none">
            <span class="rota-livre-badge"><i class="fas fa-info-circle me-1"></i>Rota Livre — sem controle de clientes/leads</span>
          </div>
          <div class="form-text">
            <a href="/rdv/rotas/create" target="_blank"><i class="fas fa-plus me-1"></i>Criar nova rota</a>
          </div>
        </div>

        <!-- Motivo -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Motivo da Viagem</label>
          <input type="text" name="motivo" class="form-control"
                 value="<?= htmlspecialchars($viagem->motivo ?? '') ?>"
                 placeholder="Ex: Visita comercial, treinamento, etc.">
        </div>

        <!-- Período -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Período Início <span class="text-danger">*</span></label>
          <input type="date" name="periodo_inicio" class="form-control" id="inputPeriodoIni"
                 value="<?= $viagem->periodo_inicio ?? date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Período Fim <span class="text-danger">*</span></label>
          <input type="date" name="periodo_fim" class="form-control" id="inputPeriodoFim"
                 value="<?= $viagem->periodo_fim ?? date('Y-m-d', strtotime('+3 days')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Valor Previsto (R$)</label>
          <input type="text" name="valor_previsto" class="form-control money-mask"
                 value="<?= number_format((float)($viagem->valor_previsto ?? 0), 2, ',', '.') ?>"
                 placeholder="0,00">
        </div>

        <!-- Localização -->
        <div class="col-md-5">
          <label class="form-label fw-semibold">Cidade Principal</label>
          <input type="text" name="cidade" class="form-control"
                 value="<?= htmlspecialchars($viagem->cidade ?? '') ?>"
                 placeholder="Ex: Uberlândia">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Estado</label>
          <select name="estado" class="form-select">
            <option value="">UF</option>
            <?php foreach (['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'] as $uf): ?>
            <option value="<?= $uf ?>" <?= ($viagem->estado ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">País</label>
          <input type="text" name="pais" class="form-control"
                 value="<?= htmlspecialchars($viagem->pais ?? 'Brasil') ?>">
        </div>

        <!-- Observações -->
        <div class="col-12">
          <label class="form-label fw-semibold">Observações</label>
          <textarea name="observacoes" class="form-control" rows="3"
                    placeholder="Informações adicionais sobre a viagem..."><?= htmlspecialchars($viagem->observacoes ?? '') ?></textarea>
        </div>

        <!-- Botões -->
        <div class="col-12 d-flex gap-2 justify-content-end">
          <a href="<?= $isEdit ? "/rdv/viagens/{$viagem->id}" : '/rdv/viagens' ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> <?= $isEdit ? 'Salvar Alterações' : 'Criar Viagem' ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Auto-nome baseado no período
const inputNome = document.getElementById('inputNome');
const inputIni  = document.getElementById('inputPeriodoIni');
const inputFim  = document.getElementById('inputPeriodoFim');

function atualizarNomeAuto() {
  if (inputNome.value.trim() !== '') return;
  const ini = inputIni.value;
  const fim = inputFim.value;
  if (ini && fim) {
    const fmtIni = ini.split('-').reverse().join('/');
    const fmtFim = fim.split('-').reverse().join('/');
    inputNome.placeholder = `Registro de Despesas ${fmtIni} até ${fmtFim}`;
  }
}
inputIni.addEventListener('change', atualizarNomeAuto);
inputFim.addEventListener('change', atualizarNomeAuto);

// Rota livre badge
const selectRota = document.getElementById('selectRota');
const rotaLivreBadge = document.getElementById('rotaLivreBadge');
selectRota.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  rotaLivreBadge.style.display = opt.dataset.tipo === 'livre' ? 'block' : 'none';
});
// Verificar no load
(function() {
  const opt = selectRota.options[selectRota.selectedIndex];
  if (opt && opt.dataset.tipo === 'livre') rotaLivreBadge.style.display = 'block';
})();

// Máscara de valor
document.querySelectorAll('.money-mask').forEach(el => {
  el.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    v = (parseInt(v||'0')/100).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    this.value = v;
  });
});
</script>
