<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-doc-row{display:flex;align-items:center;gap:.75rem;padding:.85rem 0;border-bottom:1px solid #f1f5f9}
.hubia-doc-row:last-child{border-bottom:none}
.hubia-doc-icon{width:38px;height:38px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-book me-2 text-primary"></i>Base de Conhecimento</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpload"><i class="fas fa-upload me-1"></i> Enviar Documento</button>
  </div>

  <div class="alert alert-info py-2" style="font-size:.85rem">
    <i class="fas fa-info-circle me-1"></i>
    Extração de texto real nesta fase: apenas <strong>.txt</strong>. PDF/DOCX/XLSX ficam arquivados, mas exigem uma
    biblioteca de parsing (composer) instalada no servidor para entrarem na base de busca — o motivo aparece na coluna Status.
  </div>

  <div class="hubia-card">
    <?php if (empty($documentos)): ?>
    <p class="text-muted text-center py-4 mb-0">Nenhum documento enviado ainda.</p>
    <?php else: ?>
    <?php foreach ($documentos as $d): ?>
    <div class="hubia-doc-row">
      <div class="hubia-doc-icon"><i class="fas fa-file-<?= $d->tipo === 'pdf' ? 'pdf' : ($d->tipo === 'txt' ? 'alt' : ($d->tipo === 'xlsx' ? 'excel' : 'word')) ?>"></i></div>
      <div class="flex-grow-1">
        <div class="fw-semibold"><?= htmlspecialchars($d->nome_original) ?> <span class="badge bg-light text-dark border ms-1"><?= strtoupper($d->tipo) ?></span></div>
        <div class="text-muted" style="font-size:.78rem">
          <?= number_format($d->tamanho_bytes / 1024, 0) ?> KB
          <?php if ($d->categoria): ?> · <?= htmlspecialchars($d->categoria) ?><?php endif; ?>
          <?php if ($d->status === 'pronto'): ?>
            · <span class="text-success">Pronto — <?= (int) $d->total_chunks ?> trecho(s) indexado(s)</span>
          <?php elseif ($d->status === 'erro'): ?>
            · <span class="text-danger" title="<?= htmlspecialchars($d->mensagem_erro ?? '') ?>">Erro: <?= htmlspecialchars(mb_substr($d->mensagem_erro ?? '', 0, 80)) ?>…</span>
          <?php else: ?>
            · <span class="text-warning">Processando…</span>
          <?php endif; ?>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="<?= $d->id ?>"><i class="fas fa-trash"></i></button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="modalUpload" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enviar Documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label fw-semibold">Arquivo (PDF, Word, Excel, TXT — até 15 MB)</label>
          <input type="file" class="form-control" id="inputArquivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
        </div>
        <div class="mb-2">
          <label class="form-label fw-semibold">Categoria (opcional)</label>
          <input type="text" class="form-control" id="inputCategoria" placeholder="Ex: Manual, POP, Contrato, CNES">
        </div>
        <div id="uploadStatus" style="display:none" class="mt-2"><span class="spinner-border spinner-border-sm me-1"></span> Enviando e processando…</div>
        <div id="alertUpload" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnUpload">Enviar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('btnUpload').addEventListener('click', async function() {
  const arquivo = document.getElementById('inputArquivo').files[0];
  if (!arquivo) {
    document.getElementById('alertUpload').innerHTML = '<div class="alert alert-danger py-2 mb-0">Selecione um arquivo.</div>';
    return;
  }
  const fd = new FormData();
  fd.append('arquivo', arquivo);
  fd.append('categoria', document.getElementById('inputCategoria').value.trim());

  this.disabled = true;
  document.getElementById('uploadStatus').style.display = 'block';
  document.getElementById('alertUpload').innerHTML = '';

  try {
    const resp = await fetch('/hub-ia/conhecimento/upload', { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) {
      window.location.reload();
    } else {
      document.getElementById('alertUpload').innerHTML = '<div class="alert alert-danger py-2 mb-0">' + (data.error || 'Erro ao enviar.') + '</div>';
    }
  } catch (e) {
    document.getElementById('alertUpload').innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro de comunicação: ' + e.message + '</div>';
  } finally {
    this.disabled = false;
    document.getElementById('uploadStatus').style.display = 'none';
  }
});

document.querySelectorAll('.btn-excluir').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir este documento e seus trechos indexados?')) return;
    const resp = await fetch(`/hub-ia/conhecimento/${this.dataset.id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro ao excluir.');
  });
});
</script>
