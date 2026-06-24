<?php
/**
 * ERP InLaudo — Marketing: Personalizar Conteúdo da Campanha (Etapa 2)
 */
use App\Core\UI;

$campanha = $campanha ?? null;
if (!$campanha) { header('Location: /marketing/campanhas'); exit(); }

$canal = $campanha->canal;
$canalNomes = ['email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'sdr' => 'SDR'];

UI::sectionHeader(
    'Personalizar Campanha',
    'Passo 2 de 2 — Configure o conteúdo para o canal ' . ($canalNomes[$canal] ?? $canal),
    [
        ['url' => '/marketing/campanhas', 'label' => 'Voltar', 'icon' => 'fas fa-arrow-left', 'color' => 'light'],
        ['url' => '/marketing/campanhas/edit/' . $campanha->id, 'label' => 'Editar Dados', 'icon' => 'fas fa-edit', 'color' => 'outline-secondary'],
    ]
);
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
  <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Indicador de etapas -->
<div class="d-flex align-items-center gap-2 mb-4">
  <div class="d-flex align-items-center gap-2 opacity-50">
    <span class="badge bg-success rounded-circle" style="width:28px;height:28px;line-height:20px;font-size:13px;"><i class="fas fa-check" style="font-size:10px;"></i></span>
    <span class="fw-semibold text-success">Dados Básicos</span>
  </div>
  <div class="flex-grow-1 border-top border-2 border-primary mx-2"></div>
  <div class="d-flex align-items-center gap-2">
    <span class="badge bg-primary rounded-circle" style="width:28px;height:28px;line-height:20px;font-size:13px;">2</span>
    <span class="fw-semibold text-primary">Personalizar Conteúdo</span>
  </div>
</div>

<!-- Info da campanha -->
<div class="alert alert-light border mb-4 d-flex align-items-center gap-3">
  <i class="fas fa-bullhorn text-primary fa-lg"></i>
  <div>
    <strong><?php echo htmlspecialchars($campanha->nome); ?></strong>
    <span class="badge bg-<?php echo ['email'=>'primary','whatsapp'=>'success','telegram'=>'info','sdr'=>'warning'][$canal]??'secondary'; ?> ms-2">
      <?php echo $canalNomes[$canal] ?? $canal; ?>
    </span>
    <span class="badge bg-<?php echo ['rascunho'=>'secondary','ativa'=>'success','pausada'=>'warning','arquivada'=>'dark'][$campanha->status]??'secondary'; ?> ms-1">
      <?php echo ucfirst($campanha->status); ?>
    </span>
  </div>
</div>

<div class="row g-4">
  <!-- Formulário principal -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom fw-semibold">
        <i class="fas fa-paint-brush me-2 text-primary"></i> Conteúdo da Campanha
      </div>
      <div class="card-body">
        <form id="formPersonalizar" method="POST"
              action="/marketing/campanhas/personalizar/<?php echo $campanha->id; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo \App\Core\View::csrfToken(); ?>">

          <?php if ($canal === 'email'): ?>
          <!-- ===== CANAL: E-MAIL ===== -->
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold required">Nome do Remetente</label>
              <input type="text" name="remetente_nome" class="form-control"
                     placeholder="Ex.: InLaudo Saúde"
                     value="<?php echo htmlspecialchars($campanha->remetente_nome ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold required">E-mail do Remetente</label>
              <input type="email" name="remetente_email" class="form-control"
                     placeholder="contato@suaempresa.com.br"
                     value="<?php echo htmlspecialchars($campanha->remetente_email ?? ''); ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Assunto do E-mail</label>
              <input type="text" name="assunto_email" class="form-control" required
                     placeholder="Ex.: Novidades exclusivas para você!"
                     value="<?php echo htmlspecialchars($campanha->assunto_email ?? ''); ?>">
              <div class="form-text">Variáveis disponíveis: <code>{{nome}}</code>, <code>{{email}}</code></div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Tipo de Conteúdo</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="tipo_conteudo" id="tipo_html" value="html"
                       <?php echo ($campanha->tipo_conteudo ?? 'html') === 'html' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="tipo_html">
                  <i class="fas fa-code me-1"></i> HTML
                </label>
                <input type="radio" class="btn-check" name="tipo_conteudo" id="tipo_texto" value="texto"
                       <?php echo ($campanha->tipo_conteudo ?? '') === 'texto' ? 'checked' : ''; ?>>
                <label class="btn btn-outline-secondary" for="tipo_texto">
                  <i class="fas fa-align-left me-1"></i> Texto Puro
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Corpo do E-mail</label>
              <!-- Editor HTML -->
              <div id="editorHtml">
                <div class="border rounded overflow-hidden">
                  <!-- Barra de ferramentas simples -->
                  <div class="bg-light border-bottom px-2 py-1 d-flex gap-1 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('bold')" title="Negrito"><i class="fas fa-bold"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('italic')" title="Itálico"><i class="fas fa-italic"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('underline')" title="Sublinhado"><i class="fas fa-underline"></i></button>
                    <div class="vr mx-1"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('justifyLeft')" title="Alinhar à esquerda"><i class="fas fa-align-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('justifyCenter')" title="Centralizar"><i class="fas fa-align-center"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="execCmd('justifyRight')" title="Alinhar à direita"><i class="fas fa-align-right"></i></button>
                    <div class="vr mx-1"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirLink()" title="Inserir link"><i class="fas fa-link"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirVariavel('{{nome}}')" title="Inserir {{nome}}">{{nome}}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="inserirVariavel('{{email}}')" title="Inserir {{email}}">{{email}}</button>
                    <div class="vr mx-1"></div>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleHtmlSource()" title="Ver/editar HTML bruto"><i class="fas fa-code"></i> HTML</button>
                  </div>
                  <div id="editorVisual" contenteditable="true"
                       class="p-3" style="min-height:250px;outline:none;font-family:sans-serif;"><?php echo $campanha->corpo ?? ''; ?></div>
                  <textarea id="editorSource" name="corpo" class="form-control border-0 rounded-0 d-none"
                            rows="12" style="font-family:monospace;font-size:13px;"><?php echo htmlspecialchars($campanha->corpo ?? ''); ?></textarea>
                </div>
              </div>
              <!-- Editor texto puro -->
              <div id="editorTexto" class="d-none">
                <textarea name="corpo_texto" class="form-control" rows="12"
                          placeholder="Digite o texto da mensagem..."><?php echo $campanha->tipo_conteudo === 'texto' ? ($campanha->corpo ?? '') : ''; ?></textarea>
              </div>
            </div>
          </div>

          <?php elseif ($canal === 'whatsapp'): ?>
          <!-- ===== CANAL: WHATSAPP ===== -->
          <div class="alert alert-warning mb-3">
            <i class="fab fa-whatsapp me-2"></i>
            <strong>Políticas do WhatsApp Business:</strong> Mensagens proativas requerem uso de templates pré-aprovados pela Meta.
            Mensagens fora da janela de 24h precisam de template. Evite conteúdo promocional sem opt-in do usuário.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Número de Origem (WhatsApp Business)</label>
              <input type="text" name="numero_origem" class="form-control"
                     placeholder="+55 11 99999-9999"
                     value="<?php echo htmlspecialchars($campanha->numero_origem ?? ''); ?>">
              <div class="form-text">Número cadastrado no WhatsApp Business API.</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Mensagem</label>
              <textarea name="corpo" class="form-control" rows="8" required
                        placeholder="Olá {{nome}}, temos uma novidade para você!&#10;&#10;Acesse: https://seusite.com.br&#10;&#10;Para cancelar o recebimento, responda SAIR."><?php echo htmlspecialchars($campanha->corpo ?? ''); ?></textarea>
              <div class="form-text">
                Variáveis: <code>{{nome}}</code>, <code>{{email}}</code>.
                Limite recomendado: 1.024 caracteres.
                Inclua sempre opção de opt-out (ex.: "Responda SAIR para cancelar").
              </div>
            </div>
          </div>
          <input type="hidden" name="tipo_conteudo" value="texto">
          <input type="hidden" name="assunto_email" value="">

          <?php elseif ($canal === 'telegram'): ?>
          <!-- ===== CANAL: TELEGRAM ===== -->
          <div class="alert alert-info mb-3">
            <i class="fab fa-telegram me-2"></i>
            <strong>Telegram Bot:</strong> Configure o token do bot e certifique-se que os destinatários iniciaram uma conversa com o bot.
            Suporta Markdown e HTML básico.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Token do Bot Telegram</label>
              <input type="text" name="numero_origem" class="form-control"
                     placeholder="1234567890:ABCDEFghijklmnopqrstuvwxyz"
                     value="<?php echo htmlspecialchars($campanha->numero_origem ?? ''); ?>">
              <div class="form-text">Obtenha o token via @BotFather no Telegram.</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold required">Mensagem</label>
              <textarea name="corpo" class="form-control" rows="8" required
                        placeholder="Olá *{{nome}}*!&#10;&#10;Temos uma novidade especial para você.&#10;&#10;👉 Acesse: https://seusite.com.br"><?php echo htmlspecialchars($campanha->corpo ?? ''); ?></textarea>
              <div class="form-text">
                Suporta formatação Markdown: <code>*negrito*</code>, <code>_itálico_</code>, <code>`código`</code>.
                Variáveis: <code>{{nome}}</code>, <code>{{email}}</code>.
              </div>
            </div>
          </div>
          <input type="hidden" name="tipo_conteudo" value="texto">
          <input type="hidden" name="assunto_email" value="">

          <?php elseif ($canal === 'sdr'): ?>
          <!-- ===== CANAL: SDR ===== -->
          <div class="alert alert-warning mb-3">
            <i class="fas fa-phone-alt me-2"></i>
            <strong>SDR (Sales Development Representative):</strong> Este canal gera tarefas de contato ativo para sua equipe de vendas.
            Cada destinatário receberá uma tarefa de ligação/e-mail manual com o roteiro abaixo.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold required">Roteiro / Script de Abordagem</label>
              <textarea name="corpo" class="form-control" rows="10" required
                        placeholder="Olá {{nome}}, meu nome é [SEU NOME] e estou entrando em contato da InLaudo.&#10;&#10;Motivo da ligação: ...&#10;&#10;Proposta de valor: ...&#10;&#10;Próximo passo: ..."><?php echo htmlspecialchars($campanha->corpo ?? ''); ?></textarea>
              <div class="form-text">
                Este roteiro será exibido para o SDR ao realizar o contato.
                Variáveis: <code>{{nome}}</code>, <code>{{email}}</code>.
              </div>
            </div>
          </div>
          <input type="hidden" name="tipo_conteudo" value="texto">
          <input type="hidden" name="assunto_email" value="">
          <?php endif; ?>

          <!-- Botões de ação -->
          <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-info" onclick="abrirEnvioTeste()">
                <i class="fas fa-paper-plane me-1"></i> Envio de Teste
              </button>
            </div>
            <div class="d-flex gap-2">
              <a href="/marketing/campanhas" class="btn btn-light">Cancelar</a>
              <button type="submit" class="btn btn-success px-4" id="btnSalvar">
                <i class="fas fa-save me-1"></i> Salvar e Ativar Campanha
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Painel lateral: dicas e prévia -->
  <div class="col-lg-4">
    <!-- Dicas por canal -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white border-bottom fw-semibold">
        <i class="fas fa-lightbulb me-2 text-warning"></i> Boas Práticas
      </div>
      <div class="card-body small text-muted">
        <?php if ($canal === 'email'): ?>
        <ul class="ps-3 mb-0">
          <li>Use assuntos curtos (40–60 caracteres) para evitar corte em mobile.</li>
          <li>Inclua sempre um link de descadastramento no rodapé.</li>
          <li>Evite palavras como "GRÁTIS", "PROMOÇÃO" em maiúsculas no assunto.</li>
          <li>Teste em diferentes clientes de e-mail antes de disparar.</li>
          <li>Taxa de abertura saudável: acima de 20%.</li>
        </ul>
        <?php elseif ($canal === 'whatsapp'): ?>
        <ul class="ps-3 mb-0">
          <li>Somente envie para contatos que deram opt-in explícito.</li>
          <li>Use templates aprovados pela Meta para mensagens proativas.</li>
          <li>Mensagens dentro da janela de 24h são mais flexíveis.</li>
          <li>Inclua sempre opção de cancelamento (SAIR).</li>
          <li>Limite de envio: respeite as políticas da API.</li>
        </ul>
        <?php elseif ($canal === 'telegram'): ?>
        <ul class="ps-3 mb-0">
          <li>O bot só pode enviar mensagens para usuários que o iniciaram.</li>
          <li>Use Markdown para formatar: *negrito*, _itálico_.</li>
          <li>Limite de 4.096 caracteres por mensagem.</li>
          <li>Não envie spam — o Telegram pode banir bots abusivos.</li>
        </ul>
        <?php elseif ($canal === 'sdr'): ?>
        <ul class="ps-3 mb-0">
          <li>Personalize o script para cada segmento de público.</li>
          <li>Defina claramente o objetivo da ligação.</li>
          <li>Inclua perguntas de qualificação no roteiro.</li>
          <li>Registre o resultado da interação no CRM após o contato.</li>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ações rápidas -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom fw-semibold">
        <i class="fas fa-bolt me-2 text-primary"></i> Ações Rápidas
      </div>
      <div class="card-body d-grid gap-2">
        <a href="/marketing/disparadores/create?campanha_id=<?php echo $campanha->id; ?>"
           class="btn btn-primary">
          <i class="fas fa-rocket me-2"></i> Criar Disparador
        </a>
        <button type="button" class="btn btn-outline-info" onclick="abrirEnvioTeste()">
          <i class="fas fa-paper-plane me-2"></i> Enviar Teste
        </button>
        <a href="/marketing/campanhas" class="btn btn-outline-secondary">
          <i class="fas fa-list me-2"></i> Ver Todas as Campanhas
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Envio de Teste -->
<div class="modal fade" id="modalTeste" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i> Envio de Teste</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">Envie uma prévia da campanha para verificar como ficará antes do disparo real.</p>
        <?php if ($canal === 'email'): ?>
        <label class="form-label fw-semibold">E-mail para receber o teste</label>
        <input type="email" id="emailTeste" class="form-control"
               placeholder="seu@email.com"
               value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
        <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          Para canais <?php echo $canalNomes[$canal] ?? $canal; ?>, o sistema exibirá uma prévia do conteúdo configurado.
        </div>
        <?php endif; ?>
        <div id="testeResultado" class="mt-3 d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="btnEnviarTeste" onclick="executarTeste()">
          <i class="fas fa-paper-plane me-1"></i> Enviar Teste
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?php echo \App\Core\View::csrfToken(); ?>';
const CANAL      = '<?php echo $canal; ?>';
const CAMPANHA_ID = <?php echo (int)$campanha->id; ?>;

// ── Editor HTML ──────────────────────────────────────────────────────────────
let htmlSourceVisible = false;

function execCmd(cmd) {
  document.getElementById('editorVisual').focus();
  document.execCommand(cmd, false, null);
}

function inserirLink() {
  const url = prompt('URL do link:');
  if (url) document.execCommand('createLink', false, url);
}

function inserirVariavel(v) {
  document.getElementById('editorVisual').focus();
  document.execCommand('insertText', false, v);
}

function toggleHtmlSource() {
  const visual = document.getElementById('editorVisual');
  const source = document.getElementById('editorSource');
  htmlSourceVisible = !htmlSourceVisible;
  if (htmlSourceVisible) {
    source.value = visual.innerHTML;
    visual.classList.add('d-none');
    source.classList.remove('d-none');
  } else {
    visual.innerHTML = source.value;
    source.classList.add('d-none');
    visual.classList.remove('d-none');
  }
}

// Tipo de conteúdo email: alternar entre HTML e texto
document.querySelectorAll('input[name="tipo_conteudo"]').forEach(r => {
  r.addEventListener('change', function () {
    if (this.value === 'html') {
      document.getElementById('editorHtml').classList.remove('d-none');
      document.getElementById('editorTexto').classList.add('d-none');
    } else {
      document.getElementById('editorHtml').classList.add('d-none');
      document.getElementById('editorTexto').classList.remove('d-none');
    }
  });
});

// Antes de submeter: sincronizar conteúdo do editor visual para o textarea
document.getElementById('formPersonalizar').addEventListener('submit', function (e) {
  if (CANAL === 'email') {
    const visual = document.getElementById('editorVisual');
    const source = document.getElementById('editorSource');
    const tipoHtml = document.getElementById('tipo_html');
    if (tipoHtml && tipoHtml.checked) {
      if (!htmlSourceVisible) {
        source.value = visual.innerHTML;
      }
      source.removeAttribute('name');
      source.name = 'corpo';
    } else {
      // texto puro
      const textoArea = document.querySelector('#editorTexto textarea');
      if (textoArea) {
        source.value = textoArea.value;
        source.name  = 'corpo';
        textoArea.removeAttribute('name');
      }
    }
  }
});

// ── Envio de Teste ────────────────────────────────────────────────────────────
function abrirEnvioTeste() {
  document.getElementById('testeResultado').classList.add('d-none');
  new bootstrap.Modal(document.getElementById('modalTeste')).show();
}

function executarTeste() {
  const btn = document.getElementById('btnEnviarTeste');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';

  const emailTeste = document.getElementById('emailTeste')?.value || '';
  const fd = new FormData();
  fd.append('csrf_token', CSRF_TOKEN);
  fd.append('email_teste', emailTeste);

  fetch('/marketing/campanhas/envio-teste/' + CAMPANHA_ID, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('testeResultado');
      el.classList.remove('d-none');
      el.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} mb-0">
        <i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'} me-2"></i>${data.message}
      </div>`;
    })
    .catch(() => {
      document.getElementById('testeResultado').innerHTML =
        '<div class="alert alert-danger mb-0">Erro de comunicação com o servidor.</div>';
      document.getElementById('testeResultado').classList.remove('d-none');
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar Teste';
    });
}

// Salvar via AJAX
document.getElementById('formPersonalizar').addEventListener('submit', function (e) {
  if (!this.dataset.ajax) return; // deixar submit normal
}, true);
</script>
