<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-card-title{font-size:.85rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem}
.hubia-stat-val{font-size:1.5rem;font-weight:700;color:#1e293b}
.hubia-stat-label{font-size:.75rem;color:#94a3b8}
</style>

<div class="container-fluid">
  <h4 class="mb-3"><i class="fas fa-chart-bar me-2 text-primary"></i>HUB I.A — Relatórios</h4>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $aba === 'historico' ? 'active' : '' ?>" href="?aba=historico"><i class="fas fa-history me-1"></i>Histórico</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'logs' ? 'active' : '' ?>" href="?aba=logs"><i class="fas fa-list me-1"></i>Logs</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'custos' ? 'active' : '' ?>" href="?aba=custos"><i class="fas fa-dollar-sign me-1"></i>Custos</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'monitoramento' ? 'active' : '' ?>" href="?aba=monitoramento"><i class="fas fa-heartbeat me-1"></i>Monitoramento</a></li>
  </ul>

  <?php if ($aba === 'historico'): ?>
  <div class="hubia-card">
    <div class="hubia-card-title">Histórico de Conversas (últimas 100)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover" style="font-size:.82rem">
        <thead class="table-light"><tr><th>Data</th><th>Agente</th><th>Usuário</th><th>Pergunta</th><th>Módulo</th><th>Provider/Modelo</th><th>Tokens</th><th>Custo</th><th>Tempo</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($historico as $h): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($h->created_at)) ?></td>
            <td><?= htmlspecialchars($h->agente_nome ?? '—') ?></td>
            <td><?= htmlspecialchars($h->usuario_nome ?? '—') ?></td>
            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($h->pergunta) ?>"><?= htmlspecialchars(mb_substr($h->pergunta, 0, 60)) ?></td>
            <td><?= htmlspecialchars($h->modulo_origem) ?></td>
            <?php $providerModelo = trim(($h->provider ?? '') . (($h->provider && $h->modelo) ? ' / ' : '') . ($h->modelo ?? '')); ?>
            <td><?= htmlspecialchars($providerModelo !== '' ? $providerModelo : '—') ?></td>
            <td><?= $h->tokens_total !== null ? number_format((int) $h->tokens_total, 0, ',', '.') : '—' ?></td>
            <td><?= $h->custo_estimado_usd !== null ? '$' . number_format((float) $h->custo_estimado_usd, 5) : '—' ?></td>
            <td><?= $h->tempo_ms !== null ? $h->tempo_ms . 'ms' : '—' ?></td>
            <td><span class="badge bg-<?= $h->status === 'sucesso' ? 'success' : 'danger' ?>"><?= ucfirst($h->status) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($historico)): ?>
          <tr><td colspan="10" class="text-center text-muted py-3">Nenhum registro encontrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php elseif ($aba === 'logs'): ?>
  <div class="hubia-card">
    <div class="hubia-card-title">Logs Técnicos (últimos 200)</div>
    <div class="table-responsive">
      <table class="table table-sm table-hover" style="font-size:.82rem">
        <thead class="table-light"><tr><th>Data</th><th>Conector</th><th>Provider</th><th>Status HTTP</th><th>Tempo</th><th>Tokens</th><th>Erro</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr class="<?= $l->erro ? 'table-danger' : '' ?>">
            <td><?= date('d/m/Y H:i:s', strtotime($l->created_at)) ?></td>
            <td><?= htmlspecialchars($l->conector_nome ?? '—') ?></td>
            <td><?= htmlspecialchars($l->provider ?? '—') ?></td>
            <td><?= $l->status_http ?? '—' ?></td>
            <td><?= $l->tempo_ms !== null ? $l->tempo_ms . 'ms' : '—' ?></td>
            <td><?= $l->tokens_total !== null ? number_format((int) $l->tokens_total, 0, ',', '.') : '—' ?></td>
            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($l->erro ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">Nenhum log encontrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php elseif ($aba === 'custos'): ?>
  <div class="row g-3">
    <?php foreach ($custosPorProvider as $c): ?>
    <div class="col-md-3">
      <div class="hubia-card text-center">
        <div class="hubia-stat-label"><?= htmlspecialchars(ucfirst($c->provider)) ?></div>
        <div class="hubia-stat-val">$<?= number_format((float) $c->custo, 4) ?></div>
        <div class="text-muted" style="font-size:.75rem"><?= (int) $c->total ?> chamada(s) · <?= number_format((int) $c->tokens, 0, ',', '.') ?> tokens</div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($custosPorProvider)): ?>
    <div class="col-12"><div class="hubia-card text-center text-muted py-4">Nenhum custo registrado nos últimos 30 dias.</div></div>
    <?php endif; ?>
  </div>
  <div class="hubia-card">
    <div class="hubia-card-title">Consumo Diário (30 dias)</div>
    <canvas id="chartCustoDiario" height="220"></canvas>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
  const consumoDiario = <?= json_encode(array_values($consumoDiario)) ?>;
  new Chart(document.getElementById('chartCustoDiario'), {
    type: 'bar',
    data: {
      labels: consumoDiario.map(d => d.dia),
      datasets: [{ label: 'Custo (USD)', data: consumoDiario.map(d => parseFloat(d.custo)), backgroundColor: '#6366f1' }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
  </script>

  <?php elseif ($aba === 'monitoramento'): ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="hubia-card text-center">
        <div class="hubia-stat-label">Tempo Médio de Resposta (7 dias)</div>
        <div class="hubia-stat-val"><?= number_format((float) $tempoMedioMs, 0) ?> ms</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="hubia-card text-center">
        <div class="hubia-stat-label">Taxa de Falhas (7 dias)</div>
        <div class="hubia-stat-val" style="color:<?= $taxaFalhas > 10 ? '#ef4444' : '#10b981' ?>"><?= $taxaFalhas ?>%</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="hubia-card text-center">
        <div class="hubia-stat-label">Robôs Cadastrados</div>
        <div class="hubia-stat-val"><?= count($agentes) ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
