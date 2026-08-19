<?php
/** @var array $locations */
/** @var array $trail */
/** @var int $days */
/** @var int $userId */
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQfQb8rHk8tnM2B0e6k7B6zF+Y6K2sM=" crossorigin="">
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Mapa da Equipe</h1>
            <p class="text-muted mb-0">Visualize a última localização pontual registrada em cadastros, visitas e despesas de campo.</p>
        </div>
        <a href="/rdv/viagens" class="btn btn-outline-primary"><i class="fas fa-route me-1"></i> Ver viagens RDV</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/equipe/mapa" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="days" class="form-label">Período</label>
                    <select id="days" name="days" class="form-select">
                        <option value="1" <?= $days === 1 ? 'selected' : '' ?>>Hoje / últimas 24 horas</option>
                        <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Últimos 7 dias</option>
                        <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Últimos 30 dias</option>
                        <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Últimos 90 dias</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="user_id" class="form-label">Colaborador</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">Toda a equipe</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= (int) $location->user_id ?>" <?= $userId === (int) $location->user_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $location->user_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i> Aplicar filtros</button>
                    <a class="btn btn-light" href="/equipe/mapa">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-0">
                    <div id="team-map" class="rounded" style="min-height: 520px;" aria-label="Mapa das localizações da equipe"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h2 class="h6 mb-0">Últimos registros</h2>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 520px;">
                    <?php if (empty($locations)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-map-marker-alt fa-2x mb-3 d-block"></i>
                            Nenhuma localização foi registrada no período selecionado.
                        </div>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                            <div class="list-group-item">
                                <div class="fw-semibold"><?= htmlspecialchars((string) $location->user_name) ?></div>
                                <div class="small text-muted mb-1">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $location->contexto))) ?>
                                    <?php if (!empty($location->captured_at)): ?> · <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $location->captured_at))) ?><?php endif; ?>
                                </div>
                                <div class="small text-muted">Precisão: <?= htmlspecialchars((string) ($location->accuracy_meters ?? 'não informada')) ?> m</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(() => {
    const locations = <?= json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const trail = <?= json_encode($trail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const map = L.map('team-map', { scrollWheelZoom: true }).setView([-14.2350, -51.9253], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = [];
    locations.forEach((location) => {
        const lat = Number(location.latitude);
        const lng = Number(location.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        const popup = `<strong>${escapeHtml(location.user_name || 'Colaborador')}</strong><br>` +
            `${escapeHtml(String(location.contexto || '').replaceAll('_', ' '))}<br>` +
            `${escapeHtml(formatDate(location.captured_at))}`;
        L.marker([lat, lng]).addTo(map).bindPopup(popup);
        bounds.push([lat, lng]);
    });

    if (trail.length > 1) {
        const points = trail
            .map((location) => [Number(location.latitude), Number(location.longitude)])
            .filter((point) => Number.isFinite(point[0]) && Number.isFinite(point[1]));
        if (points.length > 1) {
            L.polyline(points, { color: '#00529B', weight: 4, opacity: 0.75 }).addTo(map);
            bounds.push(...points);
        }
    }
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    }
    function formatDate(value) {
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? 'Data não disponível' : date.toLocaleString('pt-BR');
    }
})();
</script>
