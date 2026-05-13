<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$ctrl = new ServiceController();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $ctrl->deleteService((int) $_GET['delete']);
    header('Location: index.php?page=services');
    exit;
}

$services = $ctrl->listServicesWithCategories();
$stats = $ctrl->getStatsGlobales();
$topServices = $ctrl->getTopServicesWithReservations(5);
$parCat = $ctrl->getServicesParCategorie();

$totalServices = (int) ($stats['total'] ?? count($services));
$totalValides = (int) ($stats['valides'] ?? 0);
$totalAttente = (int) ($stats['en_attente'] ?? 0);
$totalDispo = (int) ($stats['disponibles'] ?? 0);
$prixMoyen = (float) ($stats['prix_moyen'] ?? 0);

function adminAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    return ($root === '/' || $root === '\\') ? '' : rtrim($root, '/');
}

function adminAssetUrl(string $path = '', string $fallback = 'assets/images/services/default.jpg'): string
{
    $path = trim($path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return adminAppRoot() . '/' . ltrim($path, '/');
}

function serviceStatusBadge(string $value): array
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    return match ($normalized) {
        'validé', 'valide' => ['Validé', 'background:rgba(76,138,255,.14);color:#6ea8ff;'],
        'en attente' => ['En attente', 'background:rgba(255,193,7,.14);color:#ffd04d;'],
        'désactivé', 'desactive' => ['Désactivé', 'background:rgba(160,160,160,.15);color:#d7d7d7;'],
        default => [trim($value) !== '' ? trim($value) : '—', 'background:rgba(160,160,160,.15);color:#d7d7d7;'],
    };
}

function serviceAvailabilityBadge(string $value): array
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    return match ($normalized) {
        'disponible' => ['Disponible', 'background:rgba(41,180,99,.14);color:#4cd774;'],
        'indisponible' => ['Indisponible', 'background:rgba(255,95,95,.14);color:#ff8b8b;'],
        default => [trim($value) !== '' ? trim($value) : '—', 'background:rgba(160,160,160,.15);color:#d7d7d7;'],
    };
}
?>

<style>
.srv-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.srv-kpi{background:var(--panel);border:1px solid var(--border);border-radius:18px;padding:20px 18px;box-shadow:0 10px 24px rgba(7,20,34,.08);}
.srv-kpi strong{display:block;font-size:30px;line-height:1;font-weight:900;color:var(--text);} 
.srv-kpi span{display:block;margin-top:8px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);} 
.srv-layout{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;}
.srv-panel{background:var(--panel);border:1px solid var(--border);border-radius:22px;padding:22px;box-shadow:0 12px 32px rgba(7,20,34,.08);} 
.srv-panel h3{margin:0 0 8px;font-size:18px;color:var(--text);} 
.srv-panel p{margin:0 0 16px;color:var(--muted);font-size:13px;} 
.srv-top-list{display:flex;flex-direction:column;gap:10px;} 
.srv-top-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;background:rgba(238,88,40,.05);border:1px solid var(--border);} 
.srv-top-rank{width:30px;height:30px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(238,88,40,.12);color:#ee5828;font-weight:900;font-size:12px;} 
.srv-top-meta{flex:1;min-width:0;} 
.srv-top-meta strong{display:block;color:var(--text);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;} 
.srv-top-meta span{display:block;color:var(--muted);font-size:12px;} 
.srv-top-badge{padding:5px 10px;border-radius:999px;background:rgba(238,88,40,.12);color:#ee5828;font-size:11px;font-weight:800;white-space:nowrap;} 
.srv-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:14px;} 
.srv-toolbar input,.srv-toolbar select{padding:10px 14px;border:1px solid var(--border);border-radius:12px;background:var(--panel);color:var(--text);font-size:13px;font-family:inherit;outline:none;} 
.srv-toolbar input{flex:1;min-width:220px;} 
.srv-toolbar input:focus,.srv-toolbar select:focus{border-color:#ee5828;} 
.srv-count{font-size:12px;color:var(--muted);} 
.srv-table-wrap{background:var(--panel);border:1px solid var(--border);border-radius:22px;overflow:hidden;box-shadow:0 12px 36px rgba(7,20,34,.12);} 
.srv-table{width:100%;border-collapse:collapse;} 
.srv-table thead tr{background:rgba(238,88,40,.04);border-bottom:1px solid var(--border);} 
.srv-table th{padding:14px 16px;font-size:11px;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;text-align:left;} 
.srv-table td{padding:14px 16px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);vertical-align:middle;} 
.srv-table tbody tr:hover{background:rgba(238,88,40,.03);} 
.srv-img{width:64px;height:48px;object-fit:cover;border-radius:10px;display:block;background:#10202f;} 
.srv-actions{display:flex;gap:8px;flex-wrap:wrap;} 
.btn-edit-s,.btn-del-s{padding:6px 13px;border-radius:9px;font-size:11px;font-weight:800;text-decoration:none;} 
.btn-edit-s{background:rgba(76,138,255,.14);color:#6ea8ff;border:1px solid rgba(76,138,255,.18);} 
.btn-del-s{background:rgba(255,95,95,.12);color:#ff8b8b;border:1px solid rgba(255,95,95,.18);} 
.table-pager{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;padding-top:14px;} 
.table-pager button{border:1px solid var(--border);background:var(--panel);color:var(--text);padding:8px 12px;border-radius:10px;font:inherit;font-size:12px;font-weight:800;cursor:pointer;transition:.2s ease;} 
.table-pager button:hover{border-color:#ee5828;color:#ee5828;transform:translateY(-1px);} 
.table-pager button.active{background:linear-gradient(135deg,#ee5828,#35b86b);color:#fff;border-color:transparent;} 
.table-pager .pager-meta{align-self:center;color:var(--muted);font-size:12px;margin-right:auto;} 
@media(max-width:1000px){.srv-layout{grid-template-columns:1fr;}} 
</style>

<section class="srv-summary reveal">
    <article class="srv-kpi"><strong><?php echo $totalServices; ?></strong><span>Total services</span></article>
    <article class="srv-kpi"><strong><?php echo $totalValides; ?></strong><span>Validés</span></article>
    <article class="srv-kpi"><strong><?php echo $totalAttente; ?></strong><span>En attente</span></article>
    <article class="srv-kpi"><strong><?php echo $totalDispo; ?></strong><span>Disponibles</span></article>
    <article class="srv-kpi"><strong><?php echo number_format($prixMoyen, 0); ?> €</strong><span>Prix moyen</span></article>
</section>

<section class="srv-layout reveal">
    <article class="srv-panel">
        <h3>Répartition par catégorie</h3>
        <p>Vue rapide du nombre de services par catégorie.</p>
        <div class="top-contributors-list">
            <?php foreach ($parCat as $row): ?>
                <div class="srv-top-item">
                    <div class="srv-top-rank">#</div>
                    <div class="srv-top-meta">
                        <strong><?php echo htmlspecialchars((string) ($row['nom'] ?? 'Catégorie'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span><?php echo (int) ($row['nb'] ?? 0); ?> service(s)</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="srv-panel">
        <h3>Top services réservés</h3>
        <p>Les prestations les plus demandées.</p>
        <div class="srv-top-list">
            <?php if (!empty($topServices)): ?>
                <?php foreach ($topServices as $index => $service): ?>
                    <div class="srv-top-item">
                        <div class="srv-top-rank"><?php echo $index + 1; ?></div>
                        <div class="srv-top-meta">
                            <strong><?php echo htmlspecialchars((string) ($service['titre'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars((string) ($service['nom_categorie'] ?? 'Catégorie'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <span class="srv-top-badge"><?php echo (int) ($service['nb_reservations'] ?? 0); ?> rés.</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun service mis en avant pour le moment.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<div class="srv-toolbar reveal">
    <input type="text" id="srvSearch" placeholder="Rechercher un service ou une catégorie..." autocomplete="off">
    <select id="srvFilterStatut">
        <option value="">Tous les statuts</option>
        <option value="validé">Validé</option>
        <option value="en attente">En attente</option>
        <option value="désactivé">Désactivé</option>
    </select>
    <select id="srvSort">
        <option value="">Trier par</option>
        <option value="az">Nom A → Z</option>
        <option value="za">Nom Z → A</option>
        <option value="prix_asc">Prix ↑</option>
        <option value="prix_desc">Prix ↓</option>
        <option value="date_desc">Plus récents</option>
        <option value="date_asc">Plus anciens</option>
    </select>
    <span class="srv-count" id="srvCount"><?php echo $totalServices; ?> service(s)</span>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="index.php?page=addService" class="outline-btn" style="text-decoration:none;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;">+ Ajouter</a>
        <a href="index.php?page=exportServicesPdf" target="_blank" class="outline-btn" style="text-decoration:none;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;">PDF</a>
    </div>
</div>

<div class="srv-table-wrap reveal">
    <div id="srvPagerTop" class="table-pager"></div>
    <table class="srv-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Disponibilité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="srvTableBody">
            <?php foreach ($services as $service): ?>
                <?php
                $image = adminAssetUrl((string) ($service['image'] ?? ''), 'assets/images/services/default.jpg');
                [$availabilityLabel, $availabilityStyle] = serviceAvailabilityBadge((string) ($service['disponibilite'] ?? ''));
                [$statusLabel, $statusStyle] = serviceStatusBadge((string) ($service['statut'] ?? ''));
                ?>
                <tr
                    class="srv-row"
                    data-titre="<?php echo htmlspecialchars(mb_strtolower((string) ($service['titre'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                    data-categorie="<?php echo htmlspecialchars(mb_strtolower((string) ($service['nom_categorie'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                    data-statut="<?php echo htmlspecialchars(mb_strtolower($statusLabel, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>"
                    data-prix="<?php echo (float) ($service['prix'] ?? 0); ?>"
                    data-id="<?php echo (int) ($service['id_service'] ?? 0); ?>"
                >
                    <td><img class="srv-img" src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars(adminAssetUrl('', 'assets/images/services/default.jpg'), ENT_QUOTES, 'UTF-8'); ?>';"></td>
                    <td style="font-weight:700;"><?php echo htmlspecialchars((string) ($service['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($service['nom_categorie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="font-weight:800;color:#ee5828;"><?php echo htmlspecialchars(number_format((float) ($service['prix'] ?? 0), 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span style="display:inline-block;padding:5px 12px;border-radius:999px;font-size:11px;font-weight:800;<?php echo $availabilityStyle; ?>"><?php echo htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><span style="display:inline-block;padding:5px 12px;border-radius:999px;font-size:11px;font-weight:800;<?php echo $statusStyle; ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td>
                        <div class="srv-actions">
                            <a class="btn-edit-s" href="index.php?page=editService&id=<?php echo (int) ($service['id_service'] ?? 0); ?>">Modifier</a>
                            <a class="btn-del-s" href="index.php?page=services&delete=<?php echo (int) ($service['id_service'] ?? 0); ?>" onclick="return confirm('Supprimer ce service ?')">Supprimer</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($services)): ?>
                <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--muted);">Aucun service trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div id="srvPagerBottom" class="table-pager" style="margin-top:14px;"></div>

<script>
(function(){
    const search = document.getElementById('srvSearch');
    const statut = document.getElementById('srvFilterStatut');
    const sort = document.getElementById('srvSort');
    const count = document.getElementById('srvCount');
    const tbody = document.getElementById('srvTableBody');
    const pagerTop = document.getElementById('srvPagerTop');
    const pagerBottom = document.getElementById('srvPagerBottom');
    const rows = () => Array.from(tbody.querySelectorAll('tr.srv-row'));
    const pageSize = 6;
    let currentPage = 1;

    function visibleRows(){ return rows().filter(row => (row.dataset.filtered || '1') === '1'); }

    function drawPagers(totalPages){
        [pagerTop, pagerBottom].forEach(pager => {
            if (!pager) return;
            pager.innerHTML = '';
            if (totalPages <= 1) return;
            const meta = document.createElement('span');
            meta.className = 'pager-meta';
            meta.textContent = `Page ${currentPage} / ${totalPages}`;
            pager.appendChild(meta);
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = i;
                if (i === currentPage) btn.classList.add('active');
                btn.addEventListener('click', () => { currentPage = i; render(); });
                pager.appendChild(btn);
            }
        });
    }

    function render(){
        const visible = visibleRows();
        const totalPages = Math.max(1, Math.ceil(visible.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        rows().forEach(row => row.style.display = 'none');
        visible.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        count.textContent = `${visible.length} service(s)`;
        drawPagers(totalPages);
    }

    function filter(){
        const q = search.value.trim().toLowerCase();
        const st = statut.value.toLowerCase();
        rows().forEach(row => {
            const ok = (q === '' || (row.dataset.titre || '').includes(q) || (row.dataset.categorie || '').includes(q))
                && (st === '' || (row.dataset.statut || '') === st);
            row.dataset.filtered = ok ? '1' : '0';
        });
        currentPage = 1;
        render();
    }

    function applySort(){
        const v = sort.value;
        const list = rows();
        list.sort((a, b) => {
            if (v === 'az') return (a.dataset.titre || '').localeCompare(b.dataset.titre || '');
            if (v === 'za') return (b.dataset.titre || '').localeCompare(a.dataset.titre || '');
            if (v === 'prix_asc') return parseFloat(a.dataset.prix || 0) - parseFloat(b.dataset.prix || 0);
            if (v === 'prix_desc') return parseFloat(b.dataset.prix || 0) - parseFloat(a.dataset.prix || 0);
            if (v === 'date_desc') return parseInt(b.dataset.id || 0, 10) - parseInt(a.dataset.id || 0, 10);
            if (v === 'date_asc') return parseInt(a.dataset.id || 0, 10) - parseInt(b.dataset.id || 0, 10);
            return 0;
        });
        list.forEach(row => tbody.appendChild(row));
        render();
    }

    rows().forEach(row => row.dataset.filtered = '1');
    search.addEventListener('input', filter);
    statut.addEventListener('change', filter);
    sort.addEventListener('change', applySort);
    render();
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function imageFallbackHook() {
    document.querySelectorAll('img.srv-img, .cat-linked img, .srv-card-image').forEach(function (img) {
        img.addEventListener('error', function () {
            this.onerror = null;
            this.src = '../../assets/images/services/default.jpg';
        });
        if (!img.getAttribute('src')) {
            img.src = '../../assets/images/services/default.jpg';
        }
    });
});
</script>