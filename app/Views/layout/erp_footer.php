</main>
</div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="/assets/js/csrf-protection.js"></script>
<!-- Flatpickr: datepicker leve para campos de data -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

<!-- Enterprise Form Scripts -->
<script src="/assets/js/sidebar.js"></script>
<script src="/assets/js/form-tabs.js"></script>

<!-- Scripts Específicos por Página -->
<?php
// Detecta a página atual para carregar scripts específicos
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$pageScripts = [];

// Clientes
if (strpos($currentPath, '/clientes') !== false) {
    $pageScripts[] = '/assets/js/clientes-form.js';
}

// Contas a Receber
if (strpos($currentPath, '/financeiro/contas-a-receber') !== false || strpos($currentPath, '/financeiro/receber') !== false) {
    $pageScripts[] = '/assets/js/contas-receber-form.js';
}

// Contas a Pagar
if (strpos($currentPath, '/financeiro/contas-a-pagar') !== false) {
    // Adicionar script específico quando existir
}

// Contratos
if (strpos($currentPath, '/contratos') !== false) {
    $pageScripts[] = '/assets/js/contratos.js';
}

// Apuração Prestador / Cliente
if (strpos($currentPath, '/faturamento/apuracao') !== false) {
    $pageScripts[] = '/assets/js/apuracao.js';
}

// Carrega os scripts específicos
foreach ($pageScripts as $script) {
    echo "<script src=\"{$script}\"></script>\n";
}
?>

<script>
// ============================================================
// Sistema de Notificações — ERP inlaudo
// ============================================================
(function () {
  'use strict';

  const POLL_INTERVAL = 60000; // atualiza a cada 60 segundos
  const I18N = {
    noNotifications: <?php echo json_encode(t('common.no_notifications'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
  };
  let notifAberto    = false;

  // Cores por tipo de notificação
  const COR_MAP = {
    primary : '#00529B',
    warning : '#f59e0b',
    danger  : '#ef4444',
    success : '#10b981',
    info    : '#3b82f6',
  };

  // ---- Atualizar badge ----
  function atualizarBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'inline-flex';
      badge.style.alignItems = 'center';
      badge.style.justifyContent = 'center';
    } else {
      badge.style.display = 'none';
    }
  }

  // ---- Renderizar item de notificação ----
  function renderItem(n) {
    const cor     = COR_MAP[n.cor] || COR_MAP.primary;
    const lida    = n.lida == 1;
    const bgColor = lida ? '#fff' : '#f0f7ff';
    const link    = n.link || '#';
    return `
      <div
        class="notif-item d-flex align-items-start gap-2 px-3 py-2"
        id="notif-item-${n.id}"
        style="border-bottom:1px solid #f1f5f9;background:${bgColor};cursor:pointer;transition:background .15s;"
        onclick="notifClicar(${n.id}, '${link.replace(/'/g, "\\'")}')"
        onmouseenter="this.style.background='#e8f4fd'"
        onmouseleave="this.style.background='${bgColor}'"
      >
        <div style="flex-shrink:0;width:34px;height:34px;border-radius:50%;background:${cor}15;display:flex;align-items:center;justify-content:center;margin-top:2px;">
          <i class="${n.icone}" style="color:${cor};font-size:.85rem;"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.8rem;font-weight:${lida ? '400' : '600'};color:#1a202c;line-height:1.3;">${escHtml(n.titulo)}</div>
          ${n.mensagem ? `<div style="font-size:.73rem;color:#64748b;margin-top:2px;line-height:1.35;">${escHtml(n.mensagem)}</div>` : ''}
          <div style="font-size:.68rem;color:#a0aec0;margin-top:3px;">${n.created_at_fmt}</div>
        </div>
        ${!lida ? '<div style="flex-shrink:0;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-top:6px;"></div>' : ''}
      </div>`;
  }

  // ---- Carregar notificações via AJAX ----
  function carregarNotificacoes() {
    fetch('/api/notificacoes/recentes', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;

        atualizarBadge(data.count_nao_lidas);

        const lista  = document.getElementById('notifLista');
        const empty  = document.getElementById('notifEmpty');
        if (!lista) return;

        if (!data.notificacoes || data.notificacoes.length === 0) {
          lista.innerHTML = '<div class="text-center py-4 text-muted" style="font-size:.85rem;"><i class="far fa-bell-slash fa-2x mb-2 d-block"></i>' + escHtml(I18N.noNotifications) + '</div>';
          return;
        }

        lista.innerHTML = data.notificacoes.map(renderItem).join('');
      })
      .catch(() => {});
  }

  // ---- Clicar em uma notificação ----
  window.notifClicar = function (id, link) {
    fetch('/api/notificacoes/marcar-lida/' + id, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
      const item = document.getElementById('notif-item-' + id);
      if (item) {
        item.style.background = '#fff';
        const dot = item.querySelector('[style*="border-radius:50%;background:#ef4444"]');
        if (dot) dot.remove();
        const titulo = item.querySelector('div[style*="font-weight"]');
        if (titulo) titulo.style.fontWeight = '400';
      }
      // Atualizar badge
      const badge = document.getElementById('notifBadge');
      if (badge && badge.style.display !== 'none') {
        const atual = parseInt(badge.textContent) || 0;
        atualizarBadge(Math.max(0, atual - 1));
      }
      if (link && link !== '#') {
        window.location.href = link;
      }
    }).catch(() => {
      if (link && link !== '#') window.location.href = link;
    });
  };

  // ---- Marcar todas como lidas ----
  window.notifMarcarTodas = function () {
    fetch('/api/notificacoes/marcar-todas-lidas', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
      atualizarBadge(0);
      carregarNotificacoes();
    }).catch(() => {});
  };

  // ---- Ao abrir o dropdown, recarregar lista ----
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('notifDropdownBtn');
    if (btn) {
      btn.addEventListener('shown.bs.dropdown', function () {
        notifAberto = true;
        carregarNotificacoes();
      });
      btn.addEventListener('hidden.bs.dropdown', function () {
        notifAberto = false;
      });
    }

    // Carregar badge imediatamente
    fetch('/api/notificacoes/count', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(d => atualizarBadge(d.count || 0))
      .catch(() => {});

    // Polling a cada 60s para atualizar o badge
    setInterval(function () {
      fetch('/api/notificacoes/count', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => atualizarBadge(d.count || 0))
        .catch(() => {});
    }, POLL_INTERVAL);
  });

  // ---- Helper: escapar HTML ----
  function escHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
</script>

</body>

</html>