<?php
$providerLabels = [
    'openai' => 'OpenAI', 'claude' => 'Claude', 'gemini' => 'Gemini',
    'deepseek' => 'DeepSeek', 'mistral' => 'Mistral', 'ollama' => 'Ollama Local',
];
$providerCores = [
    'openai' => '#10b981', 'claude' => '#f59e0b', 'gemini' => '#3b82f6',
    'deepseek' => '#6366f1', 'mistral' => '#ef4444', 'ollama' => '#64748b',
];
?>
<style>
.hubia-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.hubia-card-title{font-size:.85rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem}
.hubia-stat-val{font-size:1.6rem;font-weight:700;color:#1e293b}
.hubia-stat-label{font-size:.75rem;color:#94a3b8}
.hubia-provider-card{display:flex;align-items:center;gap:.75rem;padding:.9rem;border:1px solid #e2e8f0;border-radius:.6rem}
.hubia-provider-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.hubia-provider-dot.ok{background:#10b981}
.hubia-provider-dot.erro{background:#ef4444}
.hubia-provider-dot.off{background:#cbd5e1}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-brain me-2 text-primary"></i>HUB I.A</h4>
    <div class="d-flex gap-2">
      <a href="/hub-ia/chat" class="btn btn-primary btn-sm"><i class="fas fa-comments me-1"></i> Falar com a EVA</a>
      <a href="/hub-ia/conectores" class="btn btn-outline-secondary btn-sm"><i class="fas fa-plug me-1"></i> Conectores</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-1">
    <div class="col-md-3"><div class="hubia-card text-center">
      <div class="hubia-stat-label">Perguntas Hoje</div>
      <div class="hubia-stat-val"><?= (int) $kpis->perguntas_hoje ?></div>
    </div></div>
    <div class="col-md-3"><div class="hubia-card text-center">
      <div class="hubia-stat-label">Tokens Consumidos Hoje</div>
      <div class="hubia-stat-val"><?= number_format((int) $kpis->tokens_hoje, 0, ',', '.') ?></div>
    </div></div>
    <div class="col-md-3"><div class="hubia-card text-center">
      <div class="hubia-stat-label">Custo Hoje (USD)</div>
      <div class="hubia-stat-val">$<?= number_format((float) $kpis->custo_hoje, 4) ?></div>
    </div></div>
    <div class="col-md-3"><div class="hubia-card text-center">
      <div class="hubia-stat-label">Robôs Ativos</div>
      <div class="hubia-stat-val"><?= (int) $robosAtivos ?></div>
    </div></div>
  </div>

  <!-- Status dos provedores -->
  <div class="hubia-card">
    <div class="hubia-card-title"><i class="fas fa-server me-1"></i> Provedores de IA</div>
    <div class="row g-2">
      <?php foreach ($providerLabels as $key => $label): ?>
      <?php
        $status = $statusPorProvider[$key] ?? null;
        $dotClass = $status === 'ok' ? 'ok' : ($status === 'erro' ? 'erro' : 'off');
        $texto = $status === 'ok' ? 'Online' : ($status === 'erro' ? 'Erro na última conexão' : ($status === 'nao_testado' ? 'Configurado (não testado)' : 'Não configurado'));
      ?>
      <div class="col-md-4 col-lg-2">
        <div class="hubia-provider-card">
          <span class="hubia-provider-dot <?= $dotClass ?>"></span>
          <div>
            <div class="fw-semibold" style="font-size:.85rem"><?= $label ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= $texto ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="hubia-card">
        <div class="hubia-card-title">Uso por Módulo (30 dias)</div>
        <canvas id="chartModulo" height="220"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="hubia-card">
        <div class="hubia-card-title">Consumo Diário — Perguntas (14 dias)</div>
        <canvas id="chartDiario" height="220"></canvas>
      </div>
    </div>
  </div>

  <div class="hubia-card">
    <div class="hubia-card-title">Tempo Médio das Respostas (7 dias)</div>
    <div class="hubia-stat-val"><?= number_format((float) $tempoMedioMs, 0) ?> ms</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const consumoPorModulo = <?= json_encode(array_values($consumoPorModulo)) ?>;
const consumoDiario     = <?= json_encode(array_values($consumoDiario)) ?>;

new Chart(document.getElementById('chartModulo'), {
  type: 'doughnut',
  data: {
    labels: consumoPorModulo.map(m => m.modulo || 'hub_ia'),
    datasets: [{ data: consumoPorModulo.map(m => parseInt(m.total)), backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#6366f1','#64748b','#0891b2'] }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('chartDiario'), {
  type: 'line',
  data: {
    labels: consumoDiario.map(d => d.dia),
    datasets: [{ label: 'Perguntas', data: consumoDiario.map(d => parseInt(d.total)), borderColor: '#3b82f6', tension: .3, fill: true, backgroundColor: 'rgba(59,130,246,.1)' }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
