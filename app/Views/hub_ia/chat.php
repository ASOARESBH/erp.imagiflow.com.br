<style>
.hubia-chat-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;flex-direction:column;height:calc(100vh - 220px);min-height:420px}
.hubia-chat-header{padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:.75rem}
.hubia-chat-body{flex:1;overflow-y:auto;padding:1.25rem}
.hubia-msg{max-width:75%;margin-bottom:1rem;padding:.7rem 1rem;border-radius:.75rem;font-size:.9rem;line-height:1.5;white-space:pre-wrap}
.hubia-msg.user{background:#3b82f6;color:#fff;margin-left:auto;border-bottom-right-radius:.2rem}
.hubia-msg.eva{background:#f1f5f9;color:#1e293b;margin-right:auto;border-bottom-left-radius:.2rem}
.hubia-msg.erro{background:#fee2e2;color:#991b1b;margin-right:auto}
.hubia-msg-meta{font-size:.68rem;opacity:.7;margin-top:.3rem}
.hubia-chat-footer{padding:1rem 1.25rem;border-top:1px solid #e2e8f0}
.hubia-sql-table{font-size:.75rem;max-height:260px;overflow:auto;display:block}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-comments me-2 text-primary"></i>Chat com a EVA</h4>
    <select class="form-select form-select-sm" style="width:auto" id="selAgente">
      <?php if (empty($agentes)): ?>
      <option value="">Nenhum agente ativo cadastrado</option>
      <?php endif; ?>
      <?php foreach ($agentes as $a): ?>
      <option value="<?= $a->id ?>"><?= htmlspecialchars($a->avatar . ' ' . $a->nome) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="hubia-chat-wrap">
    <div class="hubia-chat-header">
      <span style="font-size:1.4rem">🤖</span>
      <div>
        <div class="fw-bold">Olá! Sou a EVA.</div>
        <div class="text-muted" style="font-size:.8rem">Como posso ajudar? Experimente: "Quanto faturamos este mês?" ou "Quantos contratos vencem hoje?"</div>
      </div>
    </div>
    <div class="hubia-chat-body" id="chatBody"></div>
    <div class="hubia-chat-footer">
      <form id="formChat" class="d-flex gap-2" onsubmit="return false;">
        <input type="text" class="form-control" id="inputPergunta" placeholder="Digite sua pergunta..." autocomplete="off">
        <button type="submit" class="btn btn-primary" id="btnEnviar"><i class="fas fa-paper-plane"></i></button>
      </form>
    </div>
  </div>
</div>

<script>
const chatBody = document.getElementById('chatBody');

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function addMensagem(texto, tipo) {
  const div = document.createElement('div');
  div.className = 'hubia-msg ' + tipo;
  div.innerHTML = escapeHtml(texto).replace(/\n/g, '<br>');
  chatBody.appendChild(div);
  chatBody.scrollTop = chatBody.scrollHeight;
  return div;
}

function addTabela(linhas) {
  if (!linhas.length) {
    addMensagem('A consulta não retornou nenhuma linha.', 'eva');
    return;
  }
  const cols = Object.keys(linhas[0]);
  let html = '<table class="table table-sm table-bordered hubia-sql-table"><thead><tr>' +
    cols.map(c => `<th>${escapeHtml(c)}</th>`).join('') + '</tr></thead><tbody>';
  linhas.forEach(l => {
    html += '<tr>' + cols.map(c => `<td>${escapeHtml(String(l[c] ?? ''))}</td>`).join('') + '</tr>';
  });
  html += '</tbody></table>';
  const div = document.createElement('div');
  div.className = 'hubia-msg eva';
  div.style.maxWidth = '95%';
  div.innerHTML = html;
  chatBody.appendChild(div);
  chatBody.scrollTop = chatBody.scrollHeight;
}

document.getElementById('formChat').addEventListener('submit', async function() {
  const input = document.getElementById('inputPergunta');
  const pergunta = input.value.trim();
  const agenteId = document.getElementById('selAgente').value;
  if (!pergunta) return;
  if (!agenteId) { alert('Cadastre e selecione um agente em HUB I.A → Robôs IA.'); return; }

  addMensagem(pergunta, 'user');
  input.value = '';
  document.getElementById('btnEnviar').disabled = true;

  const pensando = addMensagem('Pensando...', 'eva');

  try {
    const fd = new FormData();
    fd.append('agente_id', agenteId);
    fd.append('pergunta', pergunta);
    const resp = await fetch('/hub-ia/chat/enviar', { method: 'POST', body: fd });
    const data = await resp.json();
    pensando.remove();

    if (!data.success) {
      addMensagem(data.error || data.erro || 'Não foi possível processar a pergunta.', 'erro');
    } else if (data.tipo === 'sql') {
      addMensagem(`Consulta ao banco de dados (${data.total_linhas} linha(s)):`, 'eva');
      addTabela(data.linhas);
      const meta = addMensagem('SQL gerado: ' + data.sql_gerado, 'eva');
      meta.style.fontSize = '.72rem';
      meta.style.opacity = '.75';
    } else {
      const div = addMensagem(data.resposta || '(sem resposta)', 'eva');
      if (data.tokens_total || data.tempo_ms) {
        const meta = document.createElement('div');
        meta.className = 'hubia-msg-meta';
        meta.textContent = [
          data.tokens_total ? `${data.tokens_total} tokens` : null,
          data.tempo_ms ? `${(data.tempo_ms/1000).toFixed(1)}s` : null,
        ].filter(Boolean).join(' · ');
        div.appendChild(meta);
      }
    }
  } catch (e) {
    pensando.remove();
    addMensagem('Erro de comunicação: ' + e.message, 'erro');
  } finally {
    document.getElementById('btnEnviar').disabled = false;
  }
});
</script>
