<?php
/**
 * ERP InLaudo — Marketing: Formulário de Disparador
 */
use App\Core\UI;

$disparador = $disparador ?? null;
$isEdit     = $isEdit     ?? false;
$campanhas  = $campanhas  ?? [];
$titulo     = $isEdit ? 'Editar Disparador' : 'Novo Disparador';
$action     = '/marketing/disparadores';

// Pré-selecionar campanha via GET
$campanhaPreSel = (int) ($_GET['campanha_id'] ?? ($disparador->campanha_id ?? 0));

UI::sectionHeader($titulo, 'Configure o público, segmentação e parâmetros de envio', [
    ['url' => '/marketing/disparadores', 'label' => 'Voltar', 'icon' => 'fas fa-arrow-left', 'color' => 'light'],
]);
?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($campanhas)): ?>
<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle me-2"></i>
  Nenhuma campanha ativa encontrada. <a href="/marketing/campanhas/create" class="alert-link">Crie uma campanha</a> primeiro.
</div>
<?php else: ?>

<form method="POST" action="<?php echo $action; ?>" id="formDisparador">
  <input type="hidden" name="csrf_token" value="<?php echo \App\Core\View::csrfToken(); ?>">

  <div class="row g-4">
    <!-- Coluna principal -->
    <div class="col-lg-8">

      <!-- Bloco 1: Campanha e Nome -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
          <i class="fas fa-bullhorn me-2 text-primary"></i> 1. Selecionar Campanha
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold required">Campanha</label>
              <select name="campanha_id" id="campanha_id" class="form-select" required onchange="atualizarInfoCampanha()">
                <option value="">— Selecione uma campanha —</option>
                <?php foreach ($campanhas as $c): ?>
                <option value="<?php echo $c->id; ?>"
                        data-canal="<?php echo $c->canal; ?>"
                        <?php echo (int)$c->id === $campanhaPreSel ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c->nome); ?> (<?php echo strtoupper($c->canal); ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold required">Nome do Disparador</label>
              <input type="text" name="nome" class="form-control" required
                     placeholder="Ex.: Disparo Junho 2026"
                     value="<?php echo htmlspecialchars($disparador->nome ?? ''); ?>">
            </div>
            <div class="col-12" id="infoCampanha" style="display:none;">
              <div class="alert alert-light border mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-info-circle text-primary"></i>
                <span id="infoCampanhaTexto"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bloco 2: Público -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
          <i class="fas fa-users me-2 text-success"></i> 2. Selecionar Público
        </div>
        <div class="card-body">
          <div class="row g-3 mb-3">
            <?php
            $publicos = [
              'clientes'      => ['icon' => 'fas fa-building',    'label' => 'Clientes',      'desc' => 'Base de clientes cadastrados no ERP.'],
              'leads'         => ['icon' => 'fas fa-user-plus',   'label' => 'Leads (CRM)',   'desc' => 'Leads cadastrados no módulo CRM.'],
              'oportunidades' => ['icon' => 'fas fa-chart-line',  'label' => 'Oportunidades', 'desc' => 'Oportunidades abertas no funil CRM.'],
            ];
            $publicoAtual = $disparador->publico ?? 'leads';
            foreach ($publicos as $k => $v):
            ?>
            <div class="col-md-4">
              <label class="publico-card w-100 h-100 cursor-pointer <?php echo $publicoAtual === $k ? 'selected' : ''; ?>"
                     for="publico_<?php echo $k; ?>">
                <input type="radio" name="publico" id="publico_<?php echo $k; ?>" value="<?php echo $k; ?>"
                       class="d-none" <?php echo $publicoAtual === $k ? 'checked' : ''; ?>
                       onchange="onPublicoChange('<?php echo $k; ?>')">
                <div class="card border-2 h-100 p-3 text-center publico-option
                            <?php echo $publicoAtual === $k ? 'border-success bg-success bg-opacity-5' : 'border-light'; ?>">
                  <i class="<?php echo $v['icon']; ?> fa-2x text-success mb-2"></i>
                  <div class="fw-semibold"><?php echo $v['label']; ?></div>
                  <div class="text-muted small mt-1"><?php echo $v['desc']; ?></div>
                </div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Contador de destinatários -->
          <div class="alert alert-info d-flex align-items-center gap-3 mb-0" id="contadorDestinatarios">
            <i class="fas fa-users fa-lg"></i>
            <div>
              <strong id="totalDestinatarios">—</strong> destinatário(s) selecionado(s)
              <span class="text-muted small ms-2" id="msgSegmento"></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" onclick="contarDestinatarios()">
              <i class="fas fa-sync-alt me-1"></i> Atualizar
            </button>
          </div>
        </div>
      </div>

      <!-- Bloco 3: Segmentação -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
          <i class="fas fa-filter me-2 text-warning"></i> 3. Segmentação (opcional)
        </div>
        <div class="card-body">

          <!-- Segmentação: Clientes -->
          <div id="seg_clientes" class="seg-panel d-none">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Estado (UF)</label>
                <input type="text" name="seg_estado" class="form-control form-control-sm"
                       placeholder="Ex.: SP, RJ, MG" maxlength="2" oninput="contarDestinatarios()">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold">Exigir e-mail cadastrado</label>
                <select name="seg_tem_email" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="1" selected>Sim — somente com e-mail</option>
                  <option value="0">Não — incluir todos</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Segmentação: Leads -->
          <div id="seg_leads" class="seg-panel">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Status do Lead</label>
                <select name="seg_status" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="">Todos</option>
                  <option value="novo">Novo</option>
                  <option value="contatado">Contatado</option>
                  <option value="qualificado">Qualificado</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Segmento</label>
                <select name="seg_segmento" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="">Todos</option>
                  <option value="clinica_imagem">Clínica de Imagem</option>
                  <option value="hospital">Hospital</option>
                  <option value="upa_pronto_socorro">UPA / Pronto-Socorro</option>
                  <option value="laboratorio">Laboratório</option>
                  <option value="clinica_ortopedica">Clínica Ortopédica</option>
                  <option value="clinica_oncologica">Clínica Oncológica</option>
                  <option value="consultorio_medico">Consultório Médico</option>
                  <option value="outro">Outro</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Origem</label>
                <select name="seg_origem" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="">Todas</option>
                  <option value="indicacao">Indicação</option>
                  <option value="site">Site</option>
                  <option value="evento">Evento</option>
                  <option value="linkedin">LinkedIn</option>
                  <option value="prospeccao_ativa">Prospecção Ativa</option>
                  <option value="parceiro">Parceiro</option>
                  <option value="outro">Outro</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Estado (UF)</label>
                <input type="text" name="seg_estado" class="form-control form-control-sm"
                       placeholder="Ex.: SP" maxlength="2" oninput="contarDestinatarios()">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Exigir e-mail</label>
                <select name="seg_tem_email" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="1" selected>Sim</option>
                  <option value="0">Não</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Segmentação: Oportunidades -->
          <div id="seg_oportunidades" class="seg-panel d-none">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Etapa do Funil</label>
                <select name="seg_etapa_funil" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="">Todas as etapas</option>
                  <option value="prospeccao">Prospecção</option>
                  <option value="qualificacao">Qualificação</option>
                  <option value="proposta">Proposta</option>
                  <option value="negociacao">Negociação</option>
                  <option value="fechamento">Fechamento</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Exigir e-mail</label>
                <select name="seg_tem_email" class="form-select form-select-sm" onchange="contarDestinatarios()">
                  <option value="1" selected>Sim</option>
                  <option value="0">Não</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bloco 4: Configurações de envio -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
          <i class="fas fa-cog me-2 text-secondary"></i> 4. Configurações de Envio
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tamanho do Lote</label>
              <input type="number" name="lote_tamanho" class="form-control" min="1" max="50" value="5">
              <div class="form-text">Envios por lote (1–50). Recomendado: 5.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Intervalo entre Lotes (segundos)</label>
              <input type="number" name="intervalo_envio" class="form-control" min="1" max="300" value="5">
              <div class="form-text">Pausa entre lotes para evitar blacklist.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Agendar Para (opcional)</label>
              <input type="datetime-local" name="agendado_para" class="form-control"
                     value="<?php echo $disparador->agendado_para ?? ''; ?>">
              <div class="form-text">Deixe vazio para iniciar manualmente.</div>
            </div>
          </div>
          <div class="alert alert-warning mt-3 mb-0 small">
            <i class="fas fa-shield-alt me-2"></i>
            <strong>Anti-Blacklist:</strong> Para e-mails, recomendamos lotes de 5 com intervalo de 5s.
            Nunca envie mais de 500 e-mails/hora por domínio sem aquecimento de IP.
          </div>
        </div>
      </div>

    </div>

    <!-- Coluna lateral: resumo -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
        <div class="card-header bg-white border-bottom fw-semibold">
          <i class="fas fa-clipboard-list me-2 text-primary"></i> Resumo do Disparo
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-3">
            <tr>
              <td class="text-muted small">Campanha</td>
              <td class="fw-semibold small" id="resumoCampanha">—</td>
            </tr>
            <tr>
              <td class="text-muted small">Canal</td>
              <td id="resumoCanal">—</td>
            </tr>
            <tr>
              <td class="text-muted small">Público</td>
              <td id="resumoPublico">—</td>
            </tr>
            <tr>
              <td class="text-muted small">Destinatários</td>
              <td class="fw-bold text-primary" id="resumoTotal">—</td>
            </tr>
            <tr>
              <td class="text-muted small">Lote</td>
              <td id="resumoLote">5 a cada 5s</td>
            </tr>
          </table>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Criar Disparador
            </button>
            <a href="/marketing/disparadores" class="btn btn-light">Cancelar</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php endif; ?>

<style>
.publico-card, .canal-card { cursor: pointer; }
.publico-option, .canal-option { border-radius: .5rem; transition: all .15s; }
.publico-card:hover .publico-option { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
</style>

<script>
const CSRF_TOKEN = '<?php echo \App\Core\View::csrfToken(); ?>';

function atualizarInfoCampanha() {
  const sel = document.getElementById('campanha_id');
  const opt = sel.options[sel.selectedIndex];
  const canal = opt.dataset.canal || '';
  const nome  = opt.text;
  const info  = document.getElementById('infoCampanha');
  const texto = document.getElementById('infoCampanhaTexto');
  const resumo = document.getElementById('resumoCampanha');
  const resumoCanal = document.getElementById('resumoCanal');

  if (sel.value) {
    const canalNomes = { email: 'E-mail', whatsapp: 'WhatsApp', telegram: 'Telegram', sdr: 'SDR' };
    texto.textContent = 'Canal: ' + (canalNomes[canal] || canal);
    info.style.display = '';
    resumo.textContent = nome;
    resumoCanal.textContent = canalNomes[canal] || canal;
  } else {
    info.style.display = 'none';
    resumo.textContent = '—';
    resumoCanal.textContent = '—';
  }
  contarDestinatarios();
}

function onPublicoChange(publico) {
  document.querySelectorAll('.publico-card').forEach(card => {
    card.classList.remove('selected');
    const opt = card.querySelector('.publico-option');
    opt.className = opt.className.replace(/border-\w+|bg-\w+\s+bg-opacity-5/g, '').trim();
    opt.classList.add('border-light');
  });
  const label = document.querySelector(`label[for="publico_${publico}"]`);
  if (label) {
    label.classList.add('selected');
    const opt = label.querySelector('.publico-option');
    opt.classList.remove('border-light');
    opt.classList.add('border-success', 'bg-success', 'bg-opacity-5');
  }

  document.querySelectorAll('.seg-panel').forEach(p => p.classList.add('d-none'));
  const panel = document.getElementById('seg_' + publico);
  if (panel) panel.classList.remove('d-none');

  const nomes = { clientes: 'Clientes', leads: 'Leads (CRM)', oportunidades: 'Oportunidades' };
  document.getElementById('resumoPublico').textContent = nomes[publico] || publico;

  contarDestinatarios();
}

function contarDestinatarios() {
  const publico = document.querySelector('input[name="publico"]:checked')?.value || 'leads';
  const panel   = document.getElementById('seg_' + publico);
  const params  = new URLSearchParams({ publico });

  if (panel) {
    panel.querySelectorAll('select, input').forEach(el => {
      if (el.name && el.value) params.set(el.name.replace('seg_', ''), el.value);
    });
  }

  document.getElementById('totalDestinatarios').textContent = '...';
  document.getElementById('resumoTotal').textContent = '...';

  fetch('/marketing/disparadores/segmento-count?' + params.toString())
    .then(r => r.json())
    .then(data => {
      const t = data.total ?? 0;
      document.getElementById('totalDestinatarios').textContent = t;
      document.getElementById('resumoTotal').textContent = t;
    })
    .catch(() => {
      document.getElementById('totalDestinatarios').textContent = '—';
    });
}

// Atualizar resumo de lote
document.querySelectorAll('input[name="lote_tamanho"], input[name="intervalo_envio"]').forEach(el => {
  el.addEventListener('input', () => {
    const lote = document.querySelector('input[name="lote_tamanho"]').value;
    const int  = document.querySelector('input[name="intervalo_envio"]').value;
    document.getElementById('resumoLote').textContent = lote + ' a cada ' + int + 's';
  });
});

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
  atualizarInfoCampanha();
  onPublicoChange(document.querySelector('input[name="publico"]:checked')?.value || 'leads');
});
</script>
