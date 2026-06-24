<?php
/**
 * ERP InLaudo — Marketing: Formulário de Campanha (Etapa 1 — Dados Básicos)
 */
use App\Core\UI;

$campanha = $campanha ?? null;
$isEdit   = $isEdit   ?? false;
$titulo   = $isEdit ? 'Editar Campanha' : 'Nova Campanha';
$action   = $isEdit ? '/marketing/campanhas/update/' . $campanha->id : '/marketing/campanhas';

UI::sectionHeader($titulo, 'Passo 1 de 2 — Defina o nome, canal e tipo da campanha', [
    ['url' => '/marketing/campanhas', 'label' => 'Voltar', 'icon' => 'fas fa-arrow-left', 'color' => 'light'],
]);
?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Indicador de etapas -->
<div class="d-flex align-items-center gap-2 mb-4">
  <div class="d-flex align-items-center gap-2">
    <span class="badge bg-primary rounded-circle" style="width:28px;height:28px;line-height:20px;font-size:13px;">1</span>
    <span class="fw-semibold text-primary">Dados Básicos</span>
  </div>
  <div class="flex-grow-1 border-top border-2 border-secondary opacity-25 mx-2"></div>
  <div class="d-flex align-items-center gap-2 opacity-50">
    <span class="badge bg-secondary rounded-circle" style="width:28px;height:28px;line-height:20px;font-size:13px;">2</span>
    <span class="fw-semibold">Personalizar Conteúdo</span>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form method="POST" action="<?php echo $action; ?>" id="formCampanha">
      <input type="hidden" name="csrf_token" value="<?php echo \App\Core\View::csrfToken(); ?>">

      <div class="row g-4">
        <!-- Nome da campanha -->
        <div class="col-12">
          <label class="form-label fw-semibold required">Nome da Campanha</label>
          <input type="text" name="nome" class="form-control" required
                 placeholder="Ex.: Campanha de Boas-Vindas — Junho 2026"
                 value="<?php echo htmlspecialchars($campanha->nome ?? ''); ?>">
          <div class="form-text">Um nome descritivo para identificar esta campanha internamente.</div>
        </div>

        <!-- Descrição -->
        <div class="col-12">
          <label class="form-label fw-semibold">Descrição (opcional)</label>
          <textarea name="descricao" class="form-control" rows="2"
                    placeholder="Objetivo desta campanha, público-alvo, contexto..."><?php echo htmlspecialchars($campanha->descricao ?? ''); ?></textarea>
        </div>

        <!-- Canal de envio -->
        <div class="col-12">
          <label class="form-label fw-semibold required">Canal de Envio</label>
          <div class="row g-3 mt-1" id="canalCards">

            <?php
            $canais = [
              'email'    => ['icon' => 'fas fa-envelope',    'label' => 'E-mail',   'desc' => 'Envio por e-mail com suporte a HTML e texto. Ideal para newsletters e comunicados.', 'color' => 'primary'],
              'whatsapp' => ['icon' => 'fab fa-whatsapp',    'label' => 'WhatsApp', 'desc' => 'Mensagens via WhatsApp Business. Respeita políticas de template aprovado.', 'color' => 'success'],
              'telegram' => ['icon' => 'fab fa-telegram',    'label' => 'Telegram', 'desc' => 'Envio via bot do Telegram. Requer configuração de token e chat IDs.', 'color' => 'info'],
              'sdr'      => ['icon' => 'fas fa-phone-alt',   'label' => 'SDR',      'desc' => 'Gera tarefas de contato ativo para a equipe de vendas (ligações/e-mails manuais).', 'color' => 'warning'],
            ];
            $canalAtual = $campanha->canal ?? 'email';
            foreach ($canais as $k => $v):
            ?>
            <div class="col-6 col-md-3">
              <label class="canal-card w-100 h-100 cursor-pointer <?php echo $canalAtual === $k ? 'selected' : ''; ?>"
                     for="canal_<?php echo $k; ?>">
                <input type="radio" name="canal" id="canal_<?php echo $k; ?>" value="<?php echo $k; ?>"
                       class="d-none" <?php echo $canalAtual === $k ? 'checked' : ''; ?>>
                <div class="card border-2 h-100 p-3 text-center canal-option
                            <?php echo $canalAtual === $k ? 'border-' . $v['color'] . ' bg-' . $v['color'] . ' bg-opacity-5' : 'border-light'; ?>">
                  <i class="<?php echo $v['icon']; ?> fa-2x text-<?php echo $v['color']; ?> mb-2"></i>
                  <div class="fw-semibold"><?php echo $v['label']; ?></div>
                  <div class="text-muted small mt-1"><?php echo $v['desc']; ?></div>
                </div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Botão avançar -->
        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
          <a href="/marketing/campanhas" class="btn btn-light">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-arrow-right me-1"></i>
            <?php echo $isEdit ? 'Salvar e Continuar' : 'Avançar para Personalização'; ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
.canal-card { cursor: pointer; }
.canal-card input:checked + .canal-option,
.canal-card.selected .canal-option {
  border-width: 2px !important;
}
.canal-option { border-radius: .5rem; transition: all .15s; }
.canal-card:hover .canal-option { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
</style>

<script>
document.querySelectorAll('input[name="canal"]').forEach(radio => {
  radio.addEventListener('change', function () {
    document.querySelectorAll('.canal-card').forEach(card => {
      card.classList.remove('selected');
      const opt = card.querySelector('.canal-option');
      opt.className = opt.className.replace(/border-\w+|bg-\w+\s+bg-opacity-5/g, '').trim();
      opt.classList.add('border-light');
    });
    const label = this.closest('.canal-card');
    label.classList.add('selected');
    const colors = { email: 'primary', whatsapp: 'success', telegram: 'info', sdr: 'warning' };
    const c = colors[this.value] || 'primary';
    const opt = label.querySelector('.canal-option');
    opt.classList.remove('border-light');
    opt.classList.add('border-' + c, 'bg-' + c, 'bg-opacity-5');
  });
});
</script>
