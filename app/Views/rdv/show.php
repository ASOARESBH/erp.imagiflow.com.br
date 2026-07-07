<?php
$viagem  = $viagem  ?? null;
$despesas= $despesas ?? [];
$resumoCategoria = $resumoCategoria ?? [];
$resumoForma     = $resumoForma     ?? [];
$resumoDia       = $resumoDia       ?? [];
$historico       = $historico       ?? [];
$categorias      = $categorias      ?? [];
$formas          = $formas          ?? [];
$rotaClientes    = $rotaClientes    ?? [];
$isAdmin         = $isAdmin         ?? false;
$aba             = $aba             ?? 'resumo';

$statusCfg = [
    'aberto'    => ['label' => 'Aberto',    'color' => 'primary',   'icon' => 'fa-folder-open'],
    'iniciado'  => ['label' => 'Iniciado',  'color' => 'info',      'icon' => 'fa-plane-departure'],
    'concluido' => ['label' => 'Concluído', 'color' => 'success',   'icon' => 'fa-check-circle'],
    'cancelado' => ['label' => 'Cancelado', 'color' => 'danger',    'icon' => 'fa-times-circle'],
];
$sc = $statusCfg[$viagem->status] ?? ['label' => $viagem->status, 'color' => 'secondary', 'icon' => 'fa-circle'];

$totalDespesas = array_sum(array_column($despesas, 'valor'));
?>
<style>
.rdv-show-header{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem 1.5rem;margin-bottom:1rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.rdv-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.rdv-card-title{font-size:.85rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.75rem}
.rdv-stat{text-align:center;padding:.75rem}
.rdv-stat-val{font-size:1.5rem;font-weight:700;color:#1e293b}
.rdv-stat-label{font-size:.75rem;color:#94a3b8}
.despesa-row{display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #f1f5f9}
.despesa-row:last-child{border-bottom:none}
.despesa-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.timeline-item{display:flex;gap:.75rem;padding:.5rem 0}
.timeline-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;flex-shrink:0;margin-top:.35rem}
.timeline-dot.criacao{background:#3b82f6}
.timeline-dot.status_change{background:#f59e0b}
.timeline-dot.despesa_add{background:#10b981}
.timeline-dot.despesa_del{background:#ef4444}
.timeline-dot.aprovacao{background:#8b5cf6}
.timeline-dot.financeiro{background:#0891b2}
.cat-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem;text-align:center;min-width:120px}
.cat-card-val{font-size:1.1rem;font-weight:700}
.cat-card-label{font-size:.7rem;color:#64748b;margin-top:.2rem}
.fora-periodo-badge{font-size:.65rem;background:#fef3c7;color:#92400e;padding:.2em .5em;border-radius:4px}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-2" role="alert">
  <?= htmlspecialchars($_SESSION['flash_success']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid">

  <!-- HEADER DA VIAGEM -->
  <div class="rdv-show-header">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="fw-bold" style="font-size:1.1rem"><?= htmlspecialchars($viagem->codigo) ?></span>
          <span class="badge bg-<?= $sc['color'] ?>-subtle text-<?= $sc['color'] ?>" style="font-size:.75rem">
            <i class="fas <?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
          </span>
          <?php if ($viagem->aprovacao_status): ?>
          <?php $acCfg = ['aprovado'=>'success','reprovado'=>'danger','pendente'=>'warning']; ?>
          <span class="badge bg-<?= $acCfg[$viagem->aprovacao_status] ?? 'secondary' ?>-subtle text-<?= $acCfg[$viagem->aprovacao_status] ?? 'secondary' ?>" style="font-size:.75rem">
            <?= ucfirst($viagem->aprovacao_status) ?>
          </span>
          <?php endif; ?>
        </div>
        <div style="font-size:.9rem;color:#475569"><?= htmlspecialchars($viagem->nome) ?></div>
        <?php if ($viagem->rota_nome): ?>
        <div class="mt-1"><small class="text-muted"><i class="fas fa-map-marked-alt me-1"></i><?= htmlspecialchars($viagem->rota_nome) ?></small></div>
        <?php endif; ?>
        <div class="mt-1 d-flex gap-3 flex-wrap" style="font-size:.8rem;color:#64748b">
          <span><i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($viagem->periodo_inicio)) ?> até <?= date('d/m/Y', strtotime($viagem->periodo_fim)) ?></span>
          <span><i class="fas fa-clock me-1"></i><?= (int)$viagem->total_dias ?> dias</span>
          <?php if ((int)$viagem->dias_restantes > 0 && $viagem->status === 'iniciado'): ?>
          <span class="text-warning"><i class="fas fa-hourglass-half me-1"></i><?= (int)$viagem->dias_restantes ?> dias restantes</span>
          <?php endif; ?>
          <?php if ($viagem->cidade): ?>
          <span><i class="fas fa-map-pin me-1"></i><?= htmlspecialchars($viagem->cidade) ?><?= $viagem->estado ? ', ' . $viagem->estado : '' ?></span>
          <?php endif; ?>
          <span><i class="fas fa-user me-1"></i><?= htmlspecialchars($viagem->vendedor_nome ?? '—') ?></span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <!-- Mudar Status -->
        <?php if ($viagem->status !== 'cancelado' && $viagem->status !== 'concluido'): ?>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-exchange-alt me-1"></i> Status
          </button>
          <ul class="dropdown-menu">
            <?php foreach ($statusCfg as $k => $s): ?>
            <?php if ($k !== $viagem->status): ?>
            <li><a class="dropdown-item btn-mudar-status" href="#" data-status="<?= $k ?>">
              <i class="fas <?= $s['icon'] ?> me-2 text-<?= $s['color'] ?>"></i><?= $s['label'] ?>
            </a></li>
            <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <a href="/rdv/viagens/<?= $viagem->id ?>/edit" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-edit me-1"></i> Editar
        </a>
        <button class="btn btn-sm btn-success" id="btnAddDespesa">
          <i class="fas fa-plus me-1"></i> Registrar Despesa
        </button>
        <a href="/rdv/viagens" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
      </div>
    </div>
  </div>

  <!-- ABAS -->
  <ul class="nav nav-tabs mb-3" id="rdvTabs">
    <li class="nav-item"><a class="nav-link <?= $aba === 'resumo'    ? 'active' : '' ?>" href="?aba=resumo"><i class="fas fa-chart-pie me-1"></i>Resumo</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'despesas'  ? 'active' : '' ?>" href="?aba=despesas"><i class="fas fa-receipt me-1"></i>Despesas <span class="badge bg-secondary ms-1"><?= count($despesas) ?></span></a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'dashboard' ? 'active' : '' ?>" href="?aba=dashboard"><i class="fas fa-chart-bar me-1"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link <?= $aba === 'timeline'  ? 'active' : '' ?>" href="?aba=timeline"><i class="fas fa-history me-1"></i>Timeline</a></li>
    <?php if ($isAdmin): ?>
    <li class="nav-item"><a class="nav-link <?= $aba === 'aprovacao' ? 'active' : '' ?>" href="?aba=aprovacao"><i class="fas fa-check-double me-1"></i>Aprovação</a></li>
    <?php endif; ?>
    <?php if ($viagem->rota_nome && $rotaClientes): ?>
    <li class="nav-item"><a class="nav-link <?= $aba === 'rota' ? 'active' : '' ?>" href="?aba=rota"><i class="fas fa-map-marked-alt me-1"></i>Rota</a></li>
    <?php endif; ?>
  </ul>

  <!-- ABA: RESUMO -->
  <?php if ($aba === 'resumo'): ?>
  <div class="row g-3">
    <div class="col-md-3">
      <div class="rdv-card text-center">
        <div class="rdv-stat-label">Valor Previsto</div>
        <div class="rdv-stat-val">R$ <?= number_format((float)$viagem->valor_previsto, 2, ',', '.') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="rdv-card text-center">
        <div class="rdv-stat-label">Valor Realizado</div>
        <div class="rdv-stat-val" style="color:#059669" id="valorReal">R$ <?= number_format((float)$viagem->valor_real, 2, ',', '.') ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="rdv-card text-center">
        <div class="rdv-stat-label">Qtd. Despesas</div>
        <div class="rdv-stat-val"><?= count($despesas) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="rdv-card text-center">
        <div class="rdv-stat-label">Dias de Viagem</div>
        <div class="rdv-stat-val"><?= (int)$viagem->total_dias ?></div>
      </div>
    </div>

    <!-- Cards por categoria -->
    <?php if ($resumoCategoria): ?>
    <div class="col-12">
      <div class="rdv-card">
        <div class="rdv-card-title"><i class="fas fa-tags me-1"></i> Despesas por Categoria</div>
        <div class="d-flex gap-2 flex-wrap" id="catCards">
          <?php foreach ($resumoCategoria as $rc): ?>
          <div class="cat-card">
            <div style="color:<?= htmlspecialchars($rc->cor ?? '#6b7280') ?>">
              <i class="fas <?= htmlspecialchars($rc->icone ?? 'fa-receipt') ?>"></i>
            </div>
            <div class="cat-card-val" style="color:<?= htmlspecialchars($rc->cor ?? '#1e293b') ?>">
              R$ <?= number_format((float)$rc->total, 2, ',', '.') ?>
            </div>
            <div class="cat-card-label"><?= htmlspecialchars($rc->categoria ?? 'Outros') ?></div>
            <div style="font-size:.65rem;color:#94a3b8"><?= (int)$rc->quantidade ?> despesa(s)</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Integração financeira -->
    <?php if ($isAdmin && $viagem->aprovacao_status === 'aprovado'): ?>
    <div class="col-12">
      <div class="rdv-card">
        <div class="rdv-card-title"><i class="fas fa-dollar-sign me-1"></i> Integração Financeira</div>
        <?php if ($viagem->conta_pagar_id): ?>
        <div class="alert alert-success mb-0">
          <i class="fas fa-check-circle me-2"></i>
          Conta a pagar <strong>#<?= $viagem->conta_pagar_id ?></strong> gerada no financeiro.
          <a href="/financeiro/contas-a-pagar/<?= $viagem->conta_pagar_id ?>" class="ms-2 btn btn-sm btn-outline-success">Ver conta</a>
        </div>
        <?php else: ?>
        <p class="text-muted mb-2">A viagem está aprovada. Deseja gerar a conta a pagar no financeiro?</p>
        <button class="btn btn-primary" id="btnGerarContaPagar">
          <i class="fas fa-file-invoice-dollar me-1"></i> Gerar Conta a Pagar
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ABA: DESPESAS -->
  <?php elseif ($aba === 'despesas'): ?>
  <div class="rdv-card">
    <div class="rdv-card-title"><i class="fas fa-receipt me-1"></i> Lista de Despesas</div>
    <?php if (empty($despesas)): ?>
    <div class="text-center py-4 text-muted">
      <i class="fas fa-receipt fa-2x mb-2 d-block"></i>
      Nenhuma despesa registrada ainda.
      <br><button class="btn btn-primary btn-sm mt-2" id="btnAddDespesa2">+ Registrar Despesa</button>
    </div>
    <?php else: ?>
    <div id="listaDespesas">
      <?php foreach ($despesas as $d): ?>
      <div class="despesa-row" id="desp-<?= $d->id ?>">
        <div class="despesa-icon" style="background:<?= htmlspecialchars($d->categoria_cor ?? '#e2e8f0') ?>20;color:<?= htmlspecialchars($d->categoria_cor ?? '#6b7280') ?>">
          <i class="fas <?= htmlspecialchars($d->categoria_icone ?? 'fa-receipt') ?>"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-semibold" style="font-size:.875rem"><?= htmlspecialchars($d->descricao) ?>
            <?php if ($d->fora_periodo): ?>
            <span class="fora-periodo-badge ms-1">⚠ Fora do período</span>
            <?php endif; ?>
          </div>
          <div style="font-size:.75rem;color:#64748b">
            <?php if ($d->fornecedor): ?><span class="me-2"><i class="fas fa-store me-1"></i><?= htmlspecialchars($d->fornecedor) ?></span><?php endif; ?>
            <?php if ($d->data_documento): ?><span class="me-2"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($d->data_documento)) ?></span><?php endif; ?>
            <?php if ($d->categoria_nome): ?><span class="me-2"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($d->categoria_nome) ?></span><?php endif; ?>
            <?php if ($d->forma_nome): ?><span><i class="fas fa-credit-card me-1"></i><?= htmlspecialchars($d->forma_nome) ?></span><?php endif; ?>
          </div>
          <?php if ($d->ocr_status): ?>
          <div class="mt-1">
            <?php $ocrCfg = ['concluido'=>['success','fa-check'],'processando'=>['warning','fa-spinner fa-spin'],'erro'=>['danger','fa-exclamation'],'pendente'=>['secondary','fa-clock']]; ?>
            <?php $oc = $ocrCfg[$d->ocr_status] ?? ['secondary','fa-circle']; ?>
            <span class="badge bg-<?= $oc[0] ?>-subtle text-<?= $oc[0] ?>" style="font-size:.65rem">
              <i class="fas <?= $oc[1] ?> me-1"></i>OCR: <?= ucfirst($d->ocr_status) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($d->arquivo): ?>
        <a href="<?= htmlspecialchars($d->arquivo) ?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Ver comprovante">
          <i class="fas fa-paperclip"></i>
        </a>
        <?php endif; ?>
        <div class="fw-bold" style="color:#059669;min-width:90px;text-align:right">
          R$ <?= number_format((float)$d->valor, 2, ',', '.') ?>
        </div>
        <div class="d-flex gap-1">
          <button class="btn btn-xs btn-outline-secondary btn-edit-despesa"
                  data-id="<?= $d->id ?>"
                  data-descricao="<?= htmlspecialchars($d->descricao) ?>"
                  data-valor="<?= number_format((float)$d->valor, 2, ',', '.') ?>"
                  data-categoria="<?= $d->categoria_id ?>"
                  data-forma="<?= $d->forma_pagamento_id ?>"
                  data-data="<?= $d->data_documento ?>"
                  data-fornecedor="<?= htmlspecialchars($d->fornecedor ?? '') ?>"
                  data-cidade="<?= htmlspecialchars($d->cidade ?? '') ?>"
                  title="Editar"><i class="fas fa-edit"></i></button>
          <button class="btn btn-xs btn-outline-danger btn-del-despesa" data-id="<?= $d->id ?>" title="Excluir">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
      <strong>Total</strong>
      <strong style="color:#059669">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></strong>
    </div>
    <?php endif; ?>
  </div>

  <!-- ABA: DASHBOARD -->
  <?php elseif ($aba === 'dashboard'): ?>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="rdv-card">
        <div class="rdv-card-title">Despesas por Categoria</div>
        <canvas id="chartCategoria" height="250"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="rdv-card">
        <div class="rdv-card-title">Despesas por Dia</div>
        <canvas id="chartDia" height="250"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="rdv-card">
        <div class="rdv-card-title">Forma de Pagamento</div>
        <canvas id="chartForma" height="250"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="rdv-card">
        <div class="rdv-card-title">Previsto vs Realizado</div>
        <canvas id="chartPrevReal" height="250"></canvas>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
  const catData  = <?= json_encode(array_values($resumoCategoria)) ?>;
  const diaData  = <?= json_encode(array_values($resumoDia)) ?>;
  const formaData= <?= json_encode(array_values($resumoForma)) ?>;

  // Gráfico pizza — categorias
  new Chart(document.getElementById('chartCategoria'), {
    type: 'doughnut',
    data: {
      labels: catData.map(c => c.categoria || 'Outros'),
      datasets: [{
        data: catData.map(c => parseFloat(c.total)),
        backgroundColor: catData.map(c => c.cor || '#94a3b8'),
      }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });

  // Gráfico linha — por dia
  new Chart(document.getElementById('chartDia'), {
    type: 'line',
    data: {
      labels: diaData.map(d => d.dia),
      datasets: [{ label: 'Despesas (R$)', data: diaData.map(d => parseFloat(d.total)), borderColor: '#3b82f6', tension: .3, fill: true, backgroundColor: 'rgba(59,130,246,.1)' }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  // Gráfico barras — forma pagamento
  new Chart(document.getElementById('chartForma'), {
    type: 'bar',
    data: {
      labels: formaData.map(f => f.forma || 'Outros'),
      datasets: [{ label: 'R$', data: formaData.map(f => parseFloat(f.total)), backgroundColor: '#6366f1' }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  // Gráfico barras — previsto vs realizado
  new Chart(document.getElementById('chartPrevReal'), {
    type: 'bar',
    data: {
      labels: ['Previsto', 'Realizado'],
      datasets: [{ data: [<?= (float)$viagem->valor_previsto ?>, <?= (float)$viagem->valor_real ?>], backgroundColor: ['#94a3b8', '#10b981'] }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
  </script>

  <!-- ABA: TIMELINE -->
  <?php elseif ($aba === 'timeline'): ?>
  <div class="rdv-card">
    <div class="rdv-card-title"><i class="fas fa-history me-1"></i> Timeline da Viagem</div>
    <?php if (empty($historico)): ?>
    <p class="text-muted">Nenhum evento registrado.</p>
    <?php else: ?>
    <div style="position:relative;padding-left:1.5rem;border-left:2px solid #e2e8f0">
      <?php foreach ($historico as $h): ?>
      <div class="timeline-item mb-3">
        <div class="timeline-dot <?= $h->tipo ?>" style="position:absolute;left:-6px"></div>
        <div>
          <div style="font-size:.875rem;font-weight:600"><?= htmlspecialchars($h->descricao) ?></div>
          <div style="font-size:.75rem;color:#94a3b8">
            <?= htmlspecialchars($h->usuario_nome ?? '—') ?> — <?= date('d/m/Y H:i', strtotime($h->created_at)) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ABA: APROVAÇÃO -->
  <?php elseif ($aba === 'aprovacao' && $isAdmin): ?>
  <div class="rdv-card">
    <div class="rdv-card-title"><i class="fas fa-check-double me-1"></i> Aprovação da Viagem</div>
    <?php if ($viagem->aprovacao_status === 'aprovado'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Viagem aprovada em <?= $viagem->aprovado_em ? date('d/m/Y H:i', strtotime($viagem->aprovado_em)) : '—' ?>.</div>
    <?php elseif ($viagem->aprovacao_status === 'reprovado'): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Viagem reprovada.</div>
    <?php else: ?>
    <p class="text-muted mb-3">Status atual: <strong><?= $viagem->status ?></strong> — Valor realizado: <strong>R$ <?= number_format((float)$viagem->valor_real, 2, ',', '.') ?></strong></p>
    <div class="d-flex gap-2 mb-3">
      <button class="btn btn-success" id="btnAprovar"><i class="fas fa-check me-1"></i> Aprovar</button>
      <button class="btn btn-danger"  id="btnReprovar"><i class="fas fa-times me-1"></i> Reprovar</button>
    </div>
    <div id="formAprovacao" style="display:none">
      <textarea class="form-control mb-2" id="obsAprovacao" rows="3" placeholder="Observação (opcional)..."></textarea>
      <button class="btn btn-primary btn-sm" id="btnConfirmarAprovacao">Confirmar</button>
      <button class="btn btn-outline-secondary btn-sm ms-1" id="btnCancelarAprovacao">Cancelar</button>
    </div>
    <?php endif; ?>
  </div>

  <!-- ABA: ROTA -->
  <?php elseif ($aba === 'rota'): ?>
  <div class="rdv-card">
    <div class="rdv-card-title"><i class="fas fa-map-marked-alt me-1"></i> Clientes da Rota — <?= htmlspecialchars($viagem->rota_nome ?? '') ?></div>
    <?php if (empty($rotaClientes)): ?>
    <p class="text-muted">Nenhum cliente/lead associado a esta rota.</p>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm" style="font-size:.875rem">
        <thead class="table-light"><tr><th>#</th><th>Nome</th><th>Tipo</th><th>Cidade</th><th>Oportunidade</th></tr></thead>
        <tbody>
          <?php foreach ($rotaClientes as $i => $rc): ?>
          <tr>
            <td><?= $rc->ordem ?: ($i+1) ?></td>
            <td><?= htmlspecialchars($rc->cliente_nome ?? $rc->lead_nome ?? '—') ?></td>
            <td><?= $rc->cliente_id ? '<span class="badge bg-primary-subtle text-primary">Cliente</span>' : '<span class="badge bg-info-subtle text-info">Lead</span>' ?></td>
            <td><?= htmlspecialchars($rc->cliente_cidade ?? '—') ?></td>
            <td><?= htmlspecialchars($rc->oportunidade_titulo ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <a href="/rdv/rotas/<?= $viagem->rota_id ?>" class="btn btn-sm btn-outline-primary mt-2">
      <i class="fas fa-edit me-1"></i> Gerenciar Rota
    </a>
  </div>
  <?php endif; ?>

</div>

<!-- MODAL: Registrar Despesa -->
<div class="modal fade" id="modalDespesa" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Registrar Despesa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Tipo de despesa -->
        <div class="mb-3 d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoDespesa" id="tipoSimples" value="simples" checked>
            <label class="form-check-label" for="tipoSimples"><strong>Simples</strong> — Upload + OCR automático</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipoDespesa" id="tipoCompleta" value="completa">
            <label class="form-check-label" for="tipoCompleta"><strong>Completa</strong> — Preencher manualmente</label>
          </div>
        </div>

        <!-- Upload OCR -->
        <div id="blocoUpload" class="mb-3">
          <label class="form-label fw-semibold">Comprovante (foto ou PDF)</label>
          <div class="d-flex gap-2">
            <input type="file" class="form-control" id="inputArquivo" name="arquivo" accept="image/*,application/pdf" capture="environment">
          </div>
          <div id="ocrStatus" class="mt-2" style="display:none">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Processando OCR...
          </div>
          <div id="ocrResultado" class="mt-2 p-2 bg-light rounded" style="display:none;font-size:.8rem"></div>
        </div>

        <div id="blocoFormDespesa">
          <div class="row g-2">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dDescricao" placeholder="Ex: Almoço com cliente, Combustível...">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Valor (R$) <span class="text-danger">*</span></label>
              <input type="text" class="form-control money-mask" id="dValor" placeholder="0,00">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Categoria</label>
              <select class="form-select" id="dCategoria">
                <option value="">— Selecionar —</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->nome) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Forma de Pagamento</label>
              <select class="form-select" id="dForma">
                <option value="">— Selecionar —</option>
                <?php foreach ($formas as $f): ?>
                <option value="<?= $f->id ?>"><?= htmlspecialchars($f->nome) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Data do Documento</label>
              <input type="date" class="form-control" id="dData" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Hora</label>
              <input type="time" class="form-control" id="dHora">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Fornecedor</label>
              <input type="text" class="form-control" id="dFornecedor" placeholder="Nome do estabelecimento">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">CNPJ Fornecedor</label>
              <input type="text" class="form-control" id="dCnpj" placeholder="00.000.000/0000-00">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Nº Documento</label>
              <input type="text" class="form-control" id="dNumDoc">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Cidade</label>
              <input type="text" class="form-control" id="dCidade">
            </div>
          </div>
        </div>

        <!-- Alerta fora do período -->
        <div id="alertaForaPeriodo" class="alert alert-warning mt-3" style="display:none">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>⚠ Despesa fora do período da viagem.</strong><br>
          Período: <?= date('d/m/Y', strtotime($viagem->periodo_inicio)) ?> até <?= date('d/m/Y', strtotime($viagem->periodo_fim)) ?><br>
          <div class="mt-2">
            <button class="btn btn-sm btn-warning" id="btnManterForaPeriodo">Sim, manter</button>
            <button class="btn btn-sm btn-outline-secondary ms-1" id="btnCancelarForaPeriodo">Cancelar</button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarDespesa">
          <i class="fas fa-save me-1"></i> Salvar Despesa
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const VIAGEM_ID     = <?= $viagem->id ?>;
const PERIODO_INI   = '<?= $viagem->periodo_inicio ?>';
const PERIODO_FIM   = '<?= $viagem->periodo_fim ?>';
let   aceitarForaPeriodo = false;
let   arquivoSalvo       = null;

// Abrir modal
document.querySelectorAll('#btnAddDespesa, #btnAddDespesa2').forEach(b => {
  b && b.addEventListener('click', () => {
    aceitarForaPeriodo = false;
    arquivoSalvo = null;
    document.getElementById('alertaForaPeriodo').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalDespesa')).show();
  });
});

// Tipo de despesa
document.querySelectorAll('input[name="tipoDespesa"]').forEach(r => {
  r.addEventListener('change', function() {
    document.getElementById('blocoUpload').style.display = this.value === 'simples' ? 'block' : 'none';
  });
});

// Upload e OCR
document.getElementById('inputArquivo').addEventListener('change', async function() {
  if (!this.files.length) return;
  const file = this.files[0];
  arquivoSalvo = file;

  // Tentar OCR
  document.getElementById('ocrStatus').style.display = 'block';
  document.getElementById('ocrResultado').style.display = 'none';

  const fd = new FormData();
  fd.append('arquivo', file);

  try {
    const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/ocr`, { method: 'POST', body: fd });
    const data = await resp.json();
    document.getElementById('ocrStatus').style.display = 'none';

    if (data.success && data.ocr && !data.ocr.erro) {
      const ocr = data.ocr;
      arquivoSalvo = data.arquivo;

      // Preencher campos
      if (ocr.valor)       document.getElementById('dValor').value    = parseFloat(ocr.valor).toFixed(2).replace('.',',');
      if (ocr.data)        document.getElementById('dData').value     = ocr.data;
      if (ocr.hora)        document.getElementById('dHora').value     = ocr.hora;
      if (ocr.fornecedor)  document.getElementById('dFornecedor').value = ocr.fornecedor;
      if (ocr.cnpj)        document.getElementById('dCnpj').value     = ocr.cnpj;
      if (ocr.cidade)      document.getElementById('dCidade').value   = ocr.cidade;

      // Preencher descrição automática
      if (ocr.fornecedor && !document.getElementById('dDescricao').value) {
        document.getElementById('dDescricao').value = ocr.fornecedor;
      }

      // Categoria sugerida
      if (ocr.categoria_sugerida) {
        const sel = document.getElementById('dCategoria');
        for (let i = 0; i < sel.options.length; i++) {
          if (sel.options[i].text.toLowerCase().includes(ocr.categoria_sugerida.toLowerCase())) {
            sel.selectedIndex = i; break;
          }
        }
      }

      // Forma de pagamento
      if (ocr.forma_pagamento) {
        const sel = document.getElementById('dForma');
        for (let i = 0; i < sel.options.length; i++) {
          if (sel.options[i].text.toLowerCase().includes(ocr.forma_pagamento.toLowerCase())) {
            sel.selectedIndex = i; break;
          }
        }
      }

      document.getElementById('ocrResultado').style.display = 'block';
      document.getElementById('ocrResultado').innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> OCR concluído! Campos preenchidos automaticamente.';
    } else {
      document.getElementById('ocrResultado').style.display = 'block';
      document.getElementById('ocrResultado').innerHTML = '<i class="fas fa-exclamation-circle text-warning me-1"></i> OCR não conseguiu extrair dados. Preencha manualmente.';
    }
  } catch (e) {
    document.getElementById('ocrStatus').style.display = 'none';
    console.error('Erro OCR:', e);
  }
});

// Validar período ao mudar data
document.getElementById('dData').addEventListener('change', function() {
  const data = this.value;
  if (data && (data < PERIODO_INI || data > PERIODO_FIM)) {
    document.getElementById('alertaForaPeriodo').style.display = 'block';
    aceitarForaPeriodo = false;
  } else {
    document.getElementById('alertaForaPeriodo').style.display = 'none';
    aceitarForaPeriodo = false;
  }
});

document.getElementById('btnManterForaPeriodo').addEventListener('click', () => {
  aceitarForaPeriodo = true;
  document.getElementById('alertaForaPeriodo').style.display = 'none';
});
document.getElementById('btnCancelarForaPeriodo').addEventListener('click', () => {
  document.getElementById('dData').value = '';
  document.getElementById('alertaForaPeriodo').style.display = 'none';
});

// Salvar despesa
document.getElementById('btnSalvarDespesa').addEventListener('click', async function() {
  const descricao = document.getElementById('dDescricao').value.trim();
  const valorRaw  = document.getElementById('dValor').value.replace(/\./g,'').replace(',','.');
  const valor     = parseFloat(valorRaw);
  const data      = document.getElementById('dData').value;

  if (!descricao || !valor || valor <= 0) {
    alert('Preencha descrição e valor.');
    return;
  }

  // Verificar fora do período sem confirmação
  if (data && (data < PERIODO_INI || data > PERIODO_FIM) && !aceitarForaPeriodo) {
    document.getElementById('alertaForaPeriodo').style.display = 'block';
    return;
  }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

  const fd = new FormData();
  fd.append('descricao',          descricao);
  fd.append('valor',              valorRaw);
  fd.append('categoria_id',       document.getElementById('dCategoria').value);
  fd.append('forma_pagamento_id', document.getElementById('dForma').value);
  fd.append('data_documento',     data);
  fd.append('hora_documento',     document.getElementById('dHora').value);
  fd.append('fornecedor',         document.getElementById('dFornecedor').value);
  fd.append('cnpj_fornecedor',    document.getElementById('dCnpj').value);
  fd.append('numero_documento',   document.getElementById('dNumDoc').value);
  fd.append('cidade',             document.getElementById('dCidade').value);
  fd.append('tipo',               document.querySelector('input[name="tipoDespesa"]:checked').value);

  // Arquivo (se simples e não processado via OCR)
  const inputArq = document.getElementById('inputArquivo');
  if (inputArq.files.length && !arquivoSalvo) {
    fd.append('arquivo', inputArq.files[0]);
  }

  try {
    const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/despesas/add`, { method: 'POST', body: fd });
    const data2 = await resp.json();

    if (data2.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalDespesa')).hide();
      // Atualizar valor real
      const vrEl = document.getElementById('valorReal');
      if (vrEl) vrEl.textContent = 'R$ ' + data2.valor_real;
      // Recarregar para mostrar nova despesa
      window.location.reload();
    } else {
      alert('Erro: ' + (data2.error || 'Falha ao salvar despesa.'));
    }
  } catch (e) {
    alert('Erro de comunicação: ' + e.message);
    console.error(e);
  } finally {
    this.disabled = false;
    this.innerHTML = '<i class="fas fa-save me-1"></i> Salvar Despesa';
  }
});

// Excluir despesa
document.querySelectorAll('.btn-del-despesa').forEach(btn => {
  btn.addEventListener('click', async function() {
    if (!confirm('Excluir esta despesa?')) return;
    const id = this.dataset.id;
    const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/despesas/${id}/delete`, { method: 'POST' });
    const data = await resp.json();
    if (data.success) {
      document.getElementById('desp-' + id)?.remove();
      const vrEl = document.getElementById('valorReal');
      if (vrEl) vrEl.textContent = 'R$ ' + data.valor_real;
    } else {
      alert('Erro ao excluir: ' + (data.error || ''));
    }
  });
});

// Mudar status
document.querySelectorAll('.btn-mudar-status').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    const status = this.dataset.status;
    if (!confirm(`Alterar status para "${status}"?`)) return;
    const fd = new FormData();
    fd.append('status', status);
    const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/status`, { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.success) window.location.reload();
    else alert('Erro: ' + (data.error || ''));
  });
});

// Aprovação
let statusAprovacao = '';
document.getElementById('btnAprovar')?.addEventListener('click', () => {
  statusAprovacao = 'aprovado';
  document.getElementById('formAprovacao').style.display = 'block';
});
document.getElementById('btnReprovar')?.addEventListener('click', () => {
  statusAprovacao = 'reprovado';
  document.getElementById('formAprovacao').style.display = 'block';
});
document.getElementById('btnCancelarAprovacao')?.addEventListener('click', () => {
  document.getElementById('formAprovacao').style.display = 'none';
});
document.getElementById('btnConfirmarAprovacao')?.addEventListener('click', async () => {
  const obs = document.getElementById('obsAprovacao').value;
  const fd  = new FormData();
  fd.append('status', statusAprovacao);
  fd.append('observacao', obs);
  const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/aprovar`, { method: 'POST', body: fd });
  const data = await resp.json();
  if (data.success) window.location.reload();
  else alert('Erro: ' + (data.error || ''));
});

// Gerar conta a pagar
document.getElementById('btnGerarContaPagar')?.addEventListener('click', async function() {
  if (!confirm('Gerar conta a pagar no financeiro para esta viagem?')) return;
  this.disabled = true;
  const resp = await fetch(`/rdv/viagens/${VIAGEM_ID}/gerar-conta-pagar`, { method: 'POST' });
  const data = await resp.json();
  if (data.success) {
    alert('Conta a pagar #' + data.conta_pagar_id + ' gerada com sucesso!');
    window.location.reload();
  } else {
    alert('Erro: ' + (data.error || ''));
    this.disabled = false;
  }
});

// Máscara de valor
document.querySelectorAll('.money-mask').forEach(el => {
  el.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    v = (parseInt(v||'0')/100).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    this.value = v;
  });
});
</script>
