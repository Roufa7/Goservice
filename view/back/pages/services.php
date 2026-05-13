<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$ctrl = new ServiceController();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $ctrl->deleteService((int)$_GET['delete']);
    header('Location: index.php?page=services');
    exit;
}

$services    = $ctrl->listServicesWithCategories();
$stats       = $ctrl->getStatsGlobales();
$topServices = $ctrl->getTopServicesWithReservations(5);
$parCat      = $ctrl->getServicesParCategorie();

$totalServices = (int)($stats['total']       ?? count($services));
$totalValides  = (int)($stats['valides']     ?? 0);
$totalAttente  = (int)($stats['en_attente']  ?? 0);
$totalDispo    = (int)($stats['disponibles'] ?? 0);
$totalDesact   = (int)($stats['desactives']  ?? 0);
$prixMoyen     = (float)($stats['prix_moyen'] ?? 0);

function dBadge(string $d): string {
    return trim($d) === 'Disponible'
        ? 'background:rgba(41,180,99,.14);color:#4cd774;'
        : 'background:rgba(255,95,95,.14);color:#ff8b8b;';
}

function sBadge(string $s): string {
    $s = trim($s);
    if ($s === 'Validé')     return 'background:rgba(76,138,255,.14);color:#6ea8ff;';
    if ($s === 'En attente') return 'background:rgba(255,193,7,.14);color:#ffd04d;';
    return 'background:rgba(160,160,160,.15);color:#aaa;';
}
?>

<?php if(isset($_GET['added'])): ?>
<div style="background:rgba(76,175,80,.12);border:1px solid rgba(76,175,80,.3);color:#4cd774;border-radius:12px;padding:13px 18px;margin-bottom:18px;font-size:14px;">✓ Service ajouté avec succès.</div>
<?php endif; ?>

<?php if(isset($_GET['updated'])): ?>
<div style="background:rgba(76,175,80,.12);border:1px solid rgba(76,175,80,.3);color:#4cd774;border-radius:12px;padding:13px 18px;margin-bottom:18px;font-size:14px;">✓ Service mis à jour avec succès.</div>
<?php endif; ?>

<style>
/* ── Toggle btn ── */
.stats-toggle-wrap{display:flex;justify-content:flex-end;margin-bottom:16px;}
.stats-toggle-btn{border:none;background:linear-gradient(135deg,#ee5828,#c94718);color:#fff;padding:10px 20px;border-radius:12px;font-weight:800;font-size:13px;cursor:pointer;box-shadow:0 8px 20px rgba(238,88,40,.25);font-family:inherit;}

/* ── KPI Cards ── */
.sk-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:26px;}
.sk-card{background:var(--panel);border:1px solid var(--border);border-radius:18px;padding:20px 16px;display:flex;align-items:center;gap:13px;box-shadow:0 8px 24px rgba(7,20,34,.07);transition:transform .2s;}
.sk-card:hover{transform:translateY(-3px);}
.sk-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:21px;flex-shrink:0;}
.sk-num{font-size:26px;font-weight:900;color:var(--text);line-height:1;font-family:'Poppins',sans-serif;}
.sk-lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;}
.ic-or{background:rgba(238,88,40,.12);}
.ic-gr{background:rgba(41,180,99,.12);}
.ic-yw{background:rgba(255,193,7,.15);}
.ic-bl{background:rgba(76,138,255,.14);}
.ic-pu{background:rgba(162,93,255,.14);}

/* ── Charts row ── */
.ck-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:26px;}
@media(max-width:1000px){.ck-row{grid-template-columns:1fr;}}
.ck-panel{background:var(--panel);border:1px solid var(--border);border-radius:20px;padding:22px;box-shadow:0 8px 24px rgba(7,20,34,.07);}
.ck-title{font-size:13px;font-weight:900;color:var(--text);text-transform:uppercase;letter-spacing:.05em;}
.ck-sub{font-size:11px;color:var(--muted);margin:5px 0 14px;}
.ck-canvas{height:210px;position:relative;}

/* ── Top 5 ── */
.top-row{display:flex;flex-direction:column;gap:9px;}
.top-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:12px;background:rgba(238,88,40,.05);border:1px solid var(--border);}
.top-rank{width:28px;height:28px;border-radius:8px;background:rgba(238,88,40,.13);color:#ee5828;font-weight:900;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.top-title{font-weight:800;color:var(--text);font-size:13px;flex:1;}
.top-cat{color:var(--muted);font-size:11px;}
.top-badge{padding:3px 10px;border-radius:99px;background:rgba(238,88,40,.12);color:#ee5828;font-size:11px;font-weight:800;white-space:nowrap;}

/* ── Toolbar ── */
.srv-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.srv-toolbar input,.srv-toolbar select{padding:10px 14px;border:1px solid var(--border);border-radius:12px;background:var(--panel);color:var(--text);font-size:13px;font-family:inherit;outline:none;transition:border-color .2s;}
.srv-toolbar input:focus,.srv-toolbar select:focus{border-color:#ee5828;}
.srv-toolbar input{flex:1;min-width:220px;}
.srv-count{font-size:12px;color:var(--muted);white-space:nowrap;}

/* ── Table ── */
.srv-table-wrap{background:var(--panel);border:1px solid var(--border);border-radius:22px;overflow:hidden;box-shadow:0 12px 36px rgba(7,20,34,.12);}
.srv-table{width:100%;border-collapse:collapse;}
.srv-table thead tr{background:rgba(238,88,40,.04);border-bottom:1px solid var(--border);}
.srv-table th{padding:14px 16px;font-size:11px;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;text-align:left;}
.srv-table td{padding:14px 16px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);vertical-align:middle;}
.srv-table tbody tr:last-child td{border:none;}
.srv-table tbody tr:hover{background:rgba(238,88,40,.03);}
.srv-img{width:62px;height:46px;object-fit:cover;border-radius:10px;display:block;}
.srv-actions{display:flex;gap:7px;}
.btn-edit-s{padding:6px 13px;border-radius:9px;font-size:11px;font-weight:800;background:rgba(76,138,255,.14);color:#6ea8ff;text-decoration:none;border:1px solid rgba(76,138,255,.18);}
.btn-del-s{padding:6px 13px;border-radius:9px;font-size:11px;font-weight:800;background:rgba(255,95,95,.12);color:#ff8b8b;text-decoration:none;border:1px solid rgba(255,95,95,.18);}

/* ── Pagination dynamique ── */
.srv-pagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin:22px 0 6px;
}
.srv-page-btn{
    min-width:38px;
    height:38px;
    padding:0 12px;
    border-radius:12px;
    border:1px solid var(--border);
    background:var(--panel);
    color:var(--text);
    font-weight:800;
    font-size:13px;
    cursor:pointer;
    transition:.2s ease;
    font-family:inherit;
}
.srv-page-btn:hover{
    border-color:#ee5828;
    color:#ee5828;
}
.srv-page-btn.active{
    background:#ee5828;
    color:white;
    border-color:#ee5828;
    box-shadow:0 8px 20px rgba(238,88,40,.22);
}
.srv-page-btn:disabled{
    opacity:.45;
    cursor:not-allowed;
}
.srv-empty-row td{
    text-align:center;
    padding:32px!important;
    color:var(--muted);
}
</style>

<!-- Toggle btn -->
<div class="stats-toggle-wrap">
    <button type="button" id="toggleStatsBtn" class="stats-toggle-btn">Masquer les statistiques</button>
</div>

<!-- STATS ZONE -->
<div id="statsZone">

    <!-- KPI -->
    <div class="sk-grid reveal">
        <div class="sk-card"><div class="sk-icon ic-or">⚙️</div><div><div class="sk-num"><?= $totalServices ?></div><div class="sk-lbl">Total services</div></div></div>
        <div class="sk-card"><div class="sk-icon ic-gr">✓</div><div><div class="sk-num"><?= $totalValides ?></div><div class="sk-lbl">Validés</div></div></div>
        <div class="sk-card"><div class="sk-icon ic-yw">⏳</div><div><div class="sk-num"><?= $totalAttente ?></div><div class="sk-lbl">En attente</div></div></div>
        <div class="sk-card"><div class="sk-icon ic-bl">📡</div><div><div class="sk-num"><?= $totalDispo ?></div><div class="sk-lbl">Disponibles</div></div></div>
        <div class="sk-card"><div class="sk-icon ic-pu">💶</div><div><div class="sk-num"><?= number_format($prixMoyen,0) ?>€</div><div class="sk-lbl">Prix moyen</div></div></div>
    </div>

    <!-- Graphiques -->
    <div class="ck-row reveal">

        <!-- Barres par catégorie -->
        <div class="ck-panel">
            <div class="ck-title">Services par catégorie</div>
            <div class="ck-sub">Répartition des services</div>
            <div class="ck-canvas"><canvas id="chartCat"></canvas></div>
        </div>

        <!-- Donut statuts -->
        <div class="ck-panel">
            <div class="ck-title">Répartition des statuts</div>
            <div class="ck-sub">Validé · En attente · Désactivé</div>
            <div class="ck-canvas"><canvas id="chartStatut"></canvas></div>
        </div>

        <!-- Top 5 réservations -->
        <div class="ck-panel">
            <div class="ck-title">Top 5 services réservés</div>
            <div class="ck-sub">Par nombre de réservations réelles</div>
            <div class="top-row">
                <?php if(!empty($topServices)): ?>
                    <?php foreach($topServices as $i => $ts): ?>
                    <div class="top-item">
                        <div class="top-rank"><?= $i+1 ?></div>
                        <div>
                            <div class="top-title"><?= htmlspecialchars($ts['titre']??'') ?></div>
                            <div class="top-cat"><?= htmlspecialchars($ts['nom_categorie']??'') ?></div>
                        </div>
                        <div class="top-badge"><?= (int)$ts['nb_reservations'] ?> rés.</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color:var(--muted);font-size:13px;text-align:center;padding:20px;">Aucun service.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div><!-- /statsZone -->

<!-- Toolbar -->
<section class="action-bar reveal" style="margin-bottom:24px;">
    <div class="search-box">
        <input type="text" id="srvSearch" placeholder="Rechercher titre, catégorie..." autocomplete="off">

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
    </div>

    <div class="export-bar" style="display:flex; gap:12px; align-items:center;">
        <a href="index.php?page=exportServicesPdf" target="_blank" class="outline-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            🖨️ Exporter PDF
        </a>

        <a href="index.php?page=addService" class="solid-btn" style="text-decoration:none;">
            + Ajouter un service
        </a>
    </div>
</section>

<div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
    <span class="srv-count" id="srvCount"><?= $totalServices ?> service(s)</span>
</div>

<!-- Table -->
<div class="srv-table-wrap reveal">
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
        <?php foreach($services as $s):
            $img = !empty($s['image']) ? '/GoService_v3/'.ltrim($s['image'],'/') : '/GoService_v3/assets/images/services/default.jpg';
        ?>
        <tr class="srv-row"
            data-titre="<?= mb_strtolower(htmlspecialchars($s['titre']??'')) ?>"
            data-categorie="<?= mb_strtolower(htmlspecialchars($s['nom_categorie']??'')) ?>"
            data-statut="<?= mb_strtolower(trim(htmlspecialchars($s['statut']??''))) ?>"
            data-prix="<?= (float)($s['prix']??0) ?>"
            data-id="<?= (int)($s['id_service']??0) ?>">

            <td><img class="srv-img" src="<?= htmlspecialchars($img) ?>" alt=""></td>

            <td style="font-weight:700;"><?= htmlspecialchars($s['titre']??'') ?></td>

            <td><?= htmlspecialchars($s['nom_categorie']??'') ?></td>

            <td style="font-weight:800;color:#ee5828;">
                <?= number_format((float)($s['prix']??0),2,',',' ') ?> €
            </td>

            <td>
                <span style="display:inline-block;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:800;<?= dBadge((string)($s['disponibilite']??'')) ?>">
                    <?= htmlspecialchars($s['disponibilite']??'') ?>
                </span>
            </td>

            <td>
                <span style="display:inline-block;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:800;<?= sBadge((string)($s['statut']??'')) ?>">
                    <?= htmlspecialchars($s['statut']??'') ?>
                </span>
            </td>

            <td>
                <div class="srv-actions">
                    <a class="btn-edit-s" href="index.php?page=editService&id=<?= (int)$s['id_service'] ?>">Modifier</a>
                    <a class="btn-del-s" href="index.php?page=services&delete=<?= (int)$s['id_service'] ?>" onclick="return confirm('Supprimer ce service ?')">Supprimer</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>

        <tr id="srvEmptyRow" class="srv-empty-row" style="display:none;">
            <td colspan="7">Aucun service trouvé.</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="srv-pagination" id="srvPagination"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
const isDark = document.body.classList.contains('dark');
const tickColor = isDark ? '#c8d0da' : '#617084';
const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
const legendColor = isDark ? '#c8d0da' : '#617084';

const catLabels = <?= json_encode(array_column($parCat,'nom')) ?>;
const catData   = <?= json_encode(array_map(fn($c)=>(int)$c['nb'], $parCat)) ?>;
const statuts   = [<?= $totalValides ?>, <?= $totalAttente ?>, <?= $totalDesact ?>];
const pal = ['#ee5828','#6ea8ff','#4cd774','#ffd04d','#b97aff','#ff8b8b','#5ce5d0','#ffb347','#f06292'];

// Barres catégories
new Chart(document.getElementById('chartCat'), {
    type: 'bar',
    data: {
        labels: catLabels,
        datasets: [{
            label:'Services',
            data: catData,
            backgroundColor: pal,
            borderRadius:8,
            borderSkipped:false
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{display:false} },
        scales:{
            x:{ ticks:{color:tickColor, font:{size:10}}, grid:{display:false} },
            y:{ ticks:{color:tickColor, stepSize:1}, grid:{color:gridColor} }
        }
    }
});

// Donut statuts
new Chart(document.getElementById('chartStatut'), {
    type:'doughnut',
    data:{
        labels:['Validé','En attente','Désactivé'],
        datasets:[{
            data:statuts,
            backgroundColor:['#4cd774','#ffd04d','#aaaaaa'],
            borderWidth:0,
            hoverOffset:8
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        cutout:'68%',
        plugins:{
            legend:{
                position:'bottom',
                labels:{color:legendColor, font:{size:11}, padding:14, boxWidth:12}
            }
        }
    }
});

/* =========================================================
   Recherche + filtre + tri + pagination sur TOUS les services
   Important :
   - Tous les <tr> restent dans le DOM
   - La pagination est appliquée après la recherche/filtre/tri
   - Donc la recherche fonctionne même si le service était en page 2, 3, etc.
========================================================= */

(function(){
    const perPage = 8;

    const search = document.getElementById('srvSearch');
    const statut = document.getElementById('srvFilterStatut');
    const sort   = document.getElementById('srvSort');
    const count  = document.getElementById('srvCount');
    const tbody  = document.getElementById('srvTableBody');
    const pagination = document.getElementById('srvPagination');
    const emptyRow = document.getElementById('srvEmptyRow');

    let currentPage = 1;

    const allRows = () => Array.from(tbody.querySelectorAll('tr.srv-row'));

    function normalize(str){
        return (str || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function getFilteredRows(){
        const q  = normalize(search.value.trim());
        const st = normalize(statut.value.trim());

        return allRows().filter(row => {
            const titre = normalize(row.dataset.titre || '');
            const cat   = normalize(row.dataset.categorie || '');
            const rowSt = normalize(row.dataset.statut || '');

            const matchText = q === '' || titre.includes(q) || cat.includes(q);
            const matchStatut = st === '' || rowSt === st;

            return matchText && matchStatut;
        });
    }

    function applySortToDom(){
        const v = sort.value;
        const list = allRows();

        list.sort((a,b) => {
            if(v === 'az')        return (a.dataset.titre || '').localeCompare(b.dataset.titre || '');
            if(v === 'za')        return (b.dataset.titre || '').localeCompare(a.dataset.titre || '');
            if(v === 'prix_asc')  return parseFloat(a.dataset.prix || 0) - parseFloat(b.dataset.prix || 0);
            if(v === 'prix_desc') return parseFloat(b.dataset.prix || 0) - parseFloat(a.dataset.prix || 0);
            if(v === 'date_desc') return parseInt(b.dataset.id || 0) - parseInt(a.dataset.id || 0);
            if(v === 'date_asc')  return parseInt(a.dataset.id || 0) - parseInt(b.dataset.id || 0);
            return 0;
        });

        list.forEach(row => tbody.insertBefore(row, emptyRow));
    }

    function buildPagination(totalPages){
        pagination.innerHTML = '';

        if(totalPages <= 1){
            return;
        }

        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'srv-page-btn';
        prev.textContent = '‹';
        prev.disabled = currentPage === 1;
        prev.addEventListener('click', () => {
            currentPage--;
            render();
        });
        pagination.appendChild(prev);

        for(let i = 1; i <= totalPages; i++){
            if(
                i === 1 ||
                i === totalPages ||
                Math.abs(i - currentPage) <= 1
            ){
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'srv-page-btn' + (i === currentPage ? ' active' : '');
                btn.textContent = i;
                btn.addEventListener('click', () => {
                    currentPage = i;
                    render();
                });
                pagination.appendChild(btn);
            } else if (
                i === currentPage - 2 ||
                i === currentPage + 2
            ){
                const dots = document.createElement('button');
                dots.type = 'button';
                dots.className = 'srv-page-btn';
                dots.textContent = '...';
                dots.disabled = true;
                pagination.appendChild(dots);
            }
        }

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'srv-page-btn';
        next.textContent = '›';
        next.disabled = currentPage === totalPages;
        next.addEventListener('click', () => {
            currentPage++;
            render();
        });
        pagination.appendChild(next);
    }

    function render(){
        const filtered = getFilteredRows();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));

        if(currentPage > totalPages){
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;

        allRows().forEach(row => {
            row.style.display = 'none';
        });

        filtered.slice(start, end).forEach(row => {
            row.style.display = '';
        });

        emptyRow.style.display = total === 0 ? '' : 'none';
        count.textContent = total + ' service(s)';
        buildPagination(totalPages);
    }

    search.addEventListener('input', () => {
        currentPage = 1;
        render();
    });

    statut.addEventListener('change', () => {
        currentPage = 1;
        render();
    });

    sort.addEventListener('change', () => {
        currentPage = 1;
        applySortToDom();
        render();
    });

    applySortToDom();
    render();
})();

// Toggle stats
document.getElementById('toggleStatsBtn').addEventListener('click', function(){
    const z = document.getElementById('statsZone');
    const hidden = z.style.display === 'none';

    z.style.display = hidden ? 'block' : 'none';
    this.textContent = hidden ? 'Masquer les statistiques' : 'Afficher les statistiques';
});
</script>
