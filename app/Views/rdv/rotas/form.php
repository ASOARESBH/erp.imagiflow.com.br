<?php
$rota   = $rota ?? null;
$isEdit = $rota !== null;
$action = $isEdit ? "/rdv/rotas/{$rota->id}/update" : '/rdv/rotas/store';
?>
<style>
.rota-form-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);max-width:700px;margin:0 auto}
</style>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-2" role="alert">
  <?= htmlspecialchars($_SESSION['flash_error']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid">
  <div class="rota-form-card">
    <div class="d-flex align-items-center gap-2 mb-3">
      <i class="fas fa-map-marked-alt text-primary fa-lg"></i>
      <h5 class="mb-0 fw-bold"><?= $isEdit ? 'Editar Rota — ' . htmlspecialchars($rota->nome) : 'Nova Rota Comercial' ?></h5>
    </div>

    <form method="POST" action="<?= $action ?>">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold">Nome da Rota <span class="text-danger">*</span></label>
          <input type="text" name="nome" class="form-control"
                 value="<?= htmlspecialchars($rota->nome ?? '') ?>"
                 placeholder="Ex: Triângulo Mineiro, Sul de Minas, SP Interior..." required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Tipo de Rota</label>
          <select name="tipo" class="form-select" id="selectTipo">
            <option value="padrao" <?= ($rota->tipo ?? 'padrao') === 'padrao' ? 'selected' : '' ?>>Padrão — com controle de clientes/leads</option>
            <option value="livre"  <?= ($rota->tipo ?? '') === 'livre'  ? 'selected' : '' ?>>Livre — sem controle de clientes</option>
          </select>
          <div id="infoLivre" class="form-text text-warning" style="display:none">
            <i class="fas fa-info-circle me-1"></i>Rota livre: não vincula clientes, leads ou oportunidades. Ideal para viagens avulsas.
          </div>
          <div id="infoPadrao" class="form-text">
            <i class="fas fa-info-circle me-1"></i>Rota padrão: você pode adicionar clientes, leads e oportunidades para acompanhamento.
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Região</label>
          <input type="text" name="regiao" class="form-control"
                 value="<?= htmlspecialchars($rota->regiao ?? '') ?>"
                 placeholder="Ex: Triângulo Mineiro, Vale do Paraíba...">
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">Estado Principal</label>
          <select name="estado" class="form-select">
            <option value="">UF</option>
            <?php foreach (['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'] as $uf): ?>
            <option value="<?= $uf ?>" <?= ($rota->estado ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" class="form-control" rows="3"
                    placeholder="Descreva a rota, cidades abrangidas, objetivo..."><?= htmlspecialchars($rota->descricao ?? '') ?></textarea>
        </div>

        <div class="col-12 d-flex gap-2 justify-content-end">
          <a href="/rdv/rotas" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?= $isEdit ? 'Salvar Alterações' : 'Criar Rota' ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
const sel = document.getElementById('selectTipo');
const infoLivre  = document.getElementById('infoLivre');
const infoPadrao = document.getElementById('infoPadrao');
sel.addEventListener('change', function() {
  infoLivre.style.display  = this.value === 'livre'  ? 'block' : 'none';
  infoPadrao.style.display = this.value === 'padrao' ? 'block' : 'none';
});
// Verificar no load
(function() {
  infoLivre.style.display  = sel.value === 'livre'  ? 'block' : 'none';
  infoPadrao.style.display = sel.value === 'padrao' ? 'block' : 'none';
})();
</script>
