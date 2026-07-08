<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-card-title{font-size:.85rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem}
.hubia-tabela-list{max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem}
</style>

<div class="container-fluid">
  <h4 class="mb-3"><i class="fas fa-database me-2 text-primary"></i>Banco de Dados</h4>

  <div class="alert alert-warning py-2" style="font-size:.85rem">
    <i class="fas fa-shield-alt me-1"></i>
    A IA sempre consulta a <strong>mesma conexão já configurada no .env</strong> deste ERP — nunca um host/usuário/senha
    alternativo. Só tabelas/views explicitamente marcadas abaixo ficam visíveis para a IA, e apenas comandos
    <strong>SELECT</strong> são aceitos (validado antes da execução e reforçado com <code>SET SESSION TRANSACTION READ ONLY</code>
    no banco). Para uma camada extra de segurança, considere criar um usuário MySQL dedicado, somente leitura, para esta finalidade.
  </div>

  <div class="hubia-card">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="hubia-card-title mb-0">Configuração</div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="chkAtivo" <?= $config->ativo ? 'checked' : '' ?>>
        <label class="form-check-label" for="chkAtivo">Consulta via IA habilitada</label>
      </div>
    </div>

    <label class="form-label fw-semibold">Tabelas/Views liberadas</label>
    <div class="hubia-tabela-list">
      <?php foreach ($todasTabelas as $t): ?>
      <div class="form-check">
        <input class="form-check-input tabela-check" type="checkbox" id="tab_<?= htmlspecialchars($t) ?>" value="<?= htmlspecialchars($t) ?>"
               <?= in_array($t, $tabelasLiberadas, true) ? 'checked' : '' ?>>
        <label class="form-check-label" for="tab_<?= htmlspecialchars($t) ?>" style="font-size:.85rem"><?= htmlspecialchars($t) ?></label>
      </div>
      <?php endforeach; ?>
    </div>

    <div id="alertBanco" class="mt-2"></div>
    <button class="btn btn-primary mt-3" id="btnSalvarBanco"><i class="fas fa-save me-1"></i> Salvar Configuração</button>
  </div>

  <div class="hubia-card">
    <div class="hubia-card-title">Testar Consulta (NL → SQL)</div>
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Conector</label>
        <select class="form-select" id="testConector">
          <?php foreach ($conectores as $c): ?>
          <option value="<?= $c->id ?>"><?= htmlspecialchars($c->nome) ?> (<?= $c->provider ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label fw-semibold">Pergunta</label>
        <input type="text" class="form-control" id="testPergunta" placeholder="Ex: Quantos clientes ativos existem?">
      </div>
    </div>
    <button class="btn btn-outline-primary mt-2" id="btnTestarConsulta"><i class="fas fa-play me-1"></i> Testar</button>
    <div id="resultadoTeste" class="mt-3"></div>
  </div>
</div>

<script>
document.getElementById('btnSalvarBanco').addEventListener('click', async function() {
  const fd = new FormData();
  document.querySelectorAll('.tabela-check:checked').forEach(el => fd.append('tabelas[]', el.value));
  fd.append('ativo', document.getElementById('chkAtivo').checked ? '1' : '0');

  this.disabled = true;
  try {
    const resp = await fetch('/hub-ia/banco/salvar', { method: 'POST', body: fd });
    const data = await resp.json();
    document.getElementById('alertBanco').innerHTML = data.success
      ? '<div class="alert alert-success py-2 mb-0">Configuração salva.</div>'
      : '<div class="alert alert-danger py-2 mb-0">Erro ao salvar.</div>';
  } catch (e) {
    document.getElementById('alertBanco').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});

document.getElementById('btnTestarConsulta').addEventListener('click', async function() {
  const pergunta = document.getElementById('testPergunta').value.trim();
  const conectorId = document.getElementById('testConector').value;
  if (!pergunta || !conectorId) { alert('Informe a pergunta e selecione um conector.'); return; }

  const resultDiv = document.getElementById('resultadoTeste');
  this.disabled = true;
  resultDiv.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Consultando…';

  try {
    const fd = new FormData();
    fd.append('pergunta', pergunta);
    fd.append('conector_id', conectorId);
    const resp = await fetch('/hub-ia/banco/testar-consulta', { method: 'POST', body: fd });
    const data = await resp.json();

    if (!data.success) {
      resultDiv.innerHTML = '<div class="alert alert-danger py-2">' + (data.erro || data.error || 'Falha na consulta.') +
        (data.sql_gerado ? '<br><code>' + data.sql_gerado + '</code>' : '') + '</div>';
      return;
    }

    let html = '<div class="alert alert-success py-2">SQL gerado: <code>' + data.sql_gerado + '</code></div>';
    if (data.linhas && data.linhas.length) {
      const cols = Object.keys(data.linhas[0]);
      html += '<div style="max-height:300px;overflow:auto"><table class="table table-sm table-bordered"><thead><tr>' +
        cols.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
      data.linhas.forEach(l => {
        html += '<tr>' + cols.map(c => `<td>${l[c] ?? ''}</td>`).join('') + '</tr>';
      });
      html += '</tbody></table></div>';
    } else {
      html += '<p class="text-muted">Nenhuma linha retornada.</p>';
    }
    resultDiv.innerHTML = html;
  } catch (e) {
    resultDiv.innerHTML = '<div class="alert alert-danger py-2">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
  }
});
</script>
