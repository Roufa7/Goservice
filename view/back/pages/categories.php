<?php
require_once __DIR__ . '/../../../controller/CategorieController.php';
require_once __DIR__ . '/../../../model/Categorie.php';

$categorieController = new CategorieController();
$errors = [];

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($categorieController->hasServices($id)) {
        $errors[] = 'Impossible de supprimer cette catégorie tant que des services y sont rattachés.';
    } else {
        $categorieController->deleteCategorie($id);
        header('Location: index.php?page=categories&deleted=1');
        exit;
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $icone = trim((string) ($_POST['icone'] ?? ''));

    if ($nom === '' || mb_strlen($nom, 'UTF-8') < 3) {
        $errors[] = 'Le nom doit contenir au moins 3 caractères.';
    }
    if ($description === '' || mb_strlen($description, 'UTF-8') < 5) {
        $errors[] = 'La description doit contenir au moins 5 caractères.';
    }
    if ($icone === '') {
        $errors[] = 'Veuillez choisir une icône.';
    }

    if (empty($errors)) {
        $categorieController->addCategorie(new Categorie($nom, $description, $icone));
        header('Location: index.php?page=categories&added=1');
        exit;
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $icone = trim((string) ($_POST['icone'] ?? ''));

    if ($nom === '' || mb_strlen($nom, 'UTF-8') < 3) {
        $errors[] = 'Le nom doit contenir au moins 3 caractères.';
    }
    if ($description === '' || mb_strlen($description, 'UTF-8') < 5) {
        $errors[] = 'La description doit contenir au moins 5 caractères.';
    }
    if ($icone === '') {
        $errors[] = 'Veuillez choisir une icône.';
    }

    if (empty($errors)) {
        $categorieController->updateCategorie(new Categorie($nom, $description, $icone), $id);
        header('Location: index.php?page=categories&updated=1');
        exit;
    }
}

$categories = $categorieController->listCategoriesWithCount();
$editData = isset($_GET['edit']) && is_numeric($_GET['edit']) ? $categorieController->getCategorie((int) $_GET['edit']) : null;
$categorieSelectionnee = isset($_GET['show']) && is_numeric($_GET['show']) ? $categorieController->getCategorie((int) $_GET['show']) : null;
$servicesLies = $categorieSelectionnee ? $categorieController->getServicesByCategorie((int) $categorieSelectionnee['id_categorie']) : [];
$totalCategories = count($categories);
$usedCategories = count(array_filter($categories, static fn(array $cat): bool => (int) ($cat['nb_services'] ?? 0) > 0));
$emptyCategories = max(0, $totalCategories - $usedCategories);
$totalServices = array_reduce($categories, static fn(int $carry, array $cat): int => $carry + (int) ($cat['nb_services'] ?? 0), 0);

$icones = [
    '🔧' => 'Plomberie / Réparation',
    '⚡' => 'Électricité / Énergie',
    '🎨' => 'Peinture / Design',
    '🌿' => 'Jardinage',
    '🧹' => 'Ménage',
    '💻' => 'Informatique',
    '🏗️' => 'Construction',
    '🚗' => 'Transport',
    '📚' => 'Éducation',
    '💊' => 'Santé',
    '🍽️' => 'Restauration',
    '📸' => 'Photo / Vidéo',
    '⚖️' => 'Juridique',
    '💰' => 'Finance',
    '🔒' => 'Sécurité',
    '🏠' => 'Immobilier',
    '✂️' => 'Beauté / Coiffure',
    '🎵' => 'Événementiel',
    '🐾' => 'Animaux',
    '🌍' => 'Langues / International',
];

function adminCategoryAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    return ($root === '/' || $root === '\\') ? '' : rtrim($root, '/');
}

function adminCategoryAssetUrl(string $path = '', string $fallback = 'assets/images/services/default.jpg'): string
{
    $path = trim($path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return adminCategoryAppRoot() . '/' . ltrim($path, '/');
}

function categoryBadgeStyles(string $value, string $type): string
{
    $normalized = mb_strtolower(trim($value), 'UTF-8');
    if ($type === 'availability') {
        return $normalized === 'disponible'
            ? 'background:rgba(41,180,99,.14);color:#4cd774;'
            : 'background:rgba(255,95,95,.14);color:#ff8b8b;';
    }
    return match ($normalized) {
        'validé', 'valide' => 'background:rgba(76,138,255,.14);color:#6ea8ff;',
        'en attente' => 'background:rgba(255,193,7,.14);color:#ffd04d;',
        default => 'background:rgba(160,160,160,.15);color:#d7d7d7;',
    };
}
?>

<style>
.cat-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.cat-kpi{background:var(--panel);border:1px solid var(--border);border-radius:18px;padding:20px 18px;box-shadow:0 10px 24px rgba(7,20,34,.08);} 
.cat-kpi strong{display:block;font-size:30px;line-height:1;font-weight:900;color:var(--text);} 
.cat-kpi span{display:block;margin-top:8px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);} 
.cat-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:18px;} 
.cat-toolbar input,.cat-toolbar select{padding:10px 14px;border:1px solid var(--border);border-radius:12px;background:var(--panel);color:var(--text);font-size:13px;font-family:inherit;outline:none;} 
.cat-toolbar input{flex:1;min-width:220px;} 
.cat-form{background:var(--panel);border:1px solid var(--border);border-radius:22px;padding:22px;box-shadow:0 12px 32px rgba(7,20,34,.08);margin-bottom:22px;} 
.cat-form h3{margin:0 0 16px;font-size:18px;color:var(--text);} 
.cat-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;} 
.cat-field{display:flex;flex-direction:column;gap:6px;} 
.cat-field label{font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;} 
.cat-field input,.cat-field textarea{padding:11px 14px;border:1px solid var(--border);border-radius:12px;background:var(--bg);color:var(--text);font-family:inherit;} 
.cat-field textarea{min-height:96px;resize:vertical;} 
.cat-icons{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:8px;} 
.cat-icon-option{border:1px solid var(--border);border-radius:12px;padding:10px 8px;background:var(--bg);cursor:pointer;text-align:center;transition:.2s ease;} 
.cat-icon-option.selected,.cat-icon-option:hover{border-color:#ee5828;background:rgba(238,88,40,.08);} 
.cat-icon-option .em{display:block;font-size:22px;} 
.cat-icon-option .txt{display:block;margin-top:4px;font-size:11px;color:var(--muted);} 
.cat-table-wrap{background:var(--panel);border:1px solid var(--border);border-radius:22px;overflow:hidden;box-shadow:0 12px 36px rgba(7,20,34,.12);} 
.cat-table{width:100%;border-collapse:collapse;} 
.cat-table th{padding:14px 16px;font-size:11px;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;text-align:left;background:rgba(238,88,40,.04);border-bottom:1px solid var(--border);} 
.cat-table td{padding:14px 16px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);vertical-align:middle;} 
.cat-table tbody tr:hover{background:rgba(238,88,40,.03);} 
.cat-actions{display:flex;gap:8px;flex-wrap:wrap;} 
.cat-pill{display:inline-block;padding:6px 12px;border-radius:999px;background:rgba(238,88,40,.12);color:#ff8b5a;font-weight:800;font-size:11px;} 
.cat-btn-blue,.cat-btn-orange,.cat-btn-red{padding:8px 14px;border-radius:10px;font-weight:800;text-decoration:none;font-size:12px;} 
.cat-btn-blue{background:rgba(76,138,255,.14);color:#8ab7ff;} 
.cat-btn-orange{background:rgba(238,88,40,.14);color:#ff8b5a;} 
.cat-btn-red{background:rgba(255,95,95,.14);color:#ff8b8b;} 
.cat-linked{margin-top:24px;} 
.cat-linked img{width:64px;height:48px;object-fit:cover;border-radius:10px;display:block;background:#10202f;} 
.table-pager{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;padding-top:14px;} 
.table-pager button{border:1px solid var(--border);background:var(--panel);color:var(--text);padding:8px 12px;border-radius:10px;font:inherit;font-size:12px;font-weight:800;cursor:pointer;} 
.table-pager button.active{background:linear-gradient(135deg,#ee5828,#35b86b);color:#fff;border-color:transparent;} 
.table-pager .pager-meta{align-self:center;color:var(--muted);font-size:12px;margin-right:auto;} 
@media(max-width:900px){.cat-form-grid{grid-template-columns:1fr;}} 
</style>

<?php if (isset($_GET['added'])): ?><div class="app-flash app-flash-success">Catégorie ajoutée avec succès.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="app-flash app-flash-success">Catégorie mise à jour avec succès.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="app-flash app-flash-success">Catégorie supprimée.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="error-box" style="margin-bottom:12px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endforeach; ?>

<section class="cat-summary reveal">
    <article class="cat-kpi"><strong><?php echo $totalCategories; ?></strong><span>Total catégories</span></article>
    <article class="cat-kpi"><strong><?php echo $usedCategories; ?></strong><span>Utilisées</span></article>
    <article class="cat-kpi"><strong><?php echo $emptyCategories; ?></strong><span>Sans service</span></article>
    <article class="cat-kpi"><strong><?php echo $totalServices; ?></strong><span>Services liés</span></article>
</section>

<div class="cat-toolbar reveal">
    <input type="text" id="categorySearch" placeholder="Rechercher une catégorie..." autocomplete="off">
    <select id="categorySort">
        <option value="">Trier par</option>
        <option value="az">Nom A → Z</option>
        <option value="za">Nom Z → A</option>
        <option value="services_desc">Plus de services</option>
        <option value="services_asc">Moins de services</option>
    </select>
    <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="index.php?page=exportCategoriesPdf" target="_blank" class="outline-btn" style="text-decoration:none;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;">PDF</a>
        <button class="solid-btn" type="button" onclick="toggleCategoryForm()">+ Ajouter une catégorie</button>
    </div>
</div>

<div id="categoryFormPanel" class="cat-form" style="display:<?php echo $editData || (!empty($errors) && (($_POST['action'] ?? '') === 'add' || ($_POST['action'] ?? '') === 'edit')) ? 'block' : 'none'; ?>;">
    <h3><?php echo $editData ? 'Modifier la catégorie' : 'Nouvelle catégorie'; ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editData ? 'edit' : 'add'; ?>">
        <?php if ($editData): ?><input type="hidden" name="id" value="<?php echo (int) $editData['id_categorie']; ?>"><?php endif; ?>
        <div class="cat-form-grid">
            <div class="cat-field">
                <label>Nom</label>
                <input type="text" name="nom" value="<?php echo htmlspecialchars((string) ($editData['nom'] ?? ($_POST['nom'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="cat-field">
                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars((string) ($editData['description'] ?? ($_POST['description'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <?php $selectedIcon = (string) ($editData['icone'] ?? ($_POST['icone'] ?? '')); ?>
        <div class="cat-field" style="margin-top:16px;">
            <label>Icône</label>
            <input type="hidden" id="categoryIconInput" name="icone" value="<?php echo htmlspecialchars($selectedIcon, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="cat-icons" id="categoryIconGrid">
                <?php foreach ($icones as $emoji => $label): ?>
                    <button type="button" class="cat-icon-option <?php echo $selectedIcon === $emoji ? 'selected' : ''; ?>" data-icon="<?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="em"><?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="txt"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button type="submit" class="solid-btn"><?php echo $editData ? 'Mettre à jour' : 'Enregistrer'; ?></button>
            <?php if ($editData): ?><a href="index.php?page=categories" class="ghost-btn">Annuler</a><?php else: ?><button type="button" class="ghost-btn" onclick="toggleCategoryForm()">Annuler</button><?php endif; ?>
        </div>
    </form>
</div>

<section class="cat-table-wrap reveal">
    <table class="cat-table">
        <thead>
            <tr>
                <th>Icône</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Services liés</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="categoryTableBody">
            <?php foreach ($categories as $cat): ?>
                <tr class="category-row" data-name="<?php echo htmlspecialchars(mb_strtolower((string) ($cat['nom'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" data-services="<?php echo (int) ($cat['nb_services'] ?? 0); ?>">
                    <td style="font-size:22px;"><?php echo htmlspecialchars((string) ($cat['icone'] ?? '🗂️'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="font-weight:700;"><?php echo htmlspecialchars((string) ($cat['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($cat['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="cat-pill"><?php echo (int) ($cat['nb_services'] ?? 0); ?></span></td>
                    <td>
                        <div class="cat-actions">
                            <a href="index.php?page=categories&show=<?php echo (int) ($cat['id_categorie'] ?? 0); ?>" class="cat-btn-orange">Voir services liés</a>
                            <a href="index.php?page=categories&edit=<?php echo (int) ($cat['id_categorie'] ?? 0); ?>" class="cat-btn-blue">Modifier</a>
                            <a href="index.php?page=categories&delete=<?php echo (int) ($cat['id_categorie'] ?? 0); ?>" class="cat-btn-red" onclick="return confirm('Supprimer cette catégorie ?');">Supprimer</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;">Aucune catégorie trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<div id="categoryPager" class="table-pager"></div>

<?php if ($categorieSelectionnee): ?>
<section class="cat-linked reveal">
    <div class="cat-form" style="margin-bottom:0;">
        <h3>Services liés à la catégorie : <span style="color:var(--orange);"><?php echo htmlspecialchars((string) $categorieSelectionnee['nom'], ENT_QUOTES, 'UTF-8'); ?></span></h3>
        <p style="margin:0;color:var(--muted);"><?php echo htmlspecialchars((string) ($categorieSelectionnee['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="cat-table-wrap">
        <table class="cat-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Titre</th>
                    <th>Prix</th>
                    <th>Disponibilité</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody id="linkedServicesBody">
                <?php foreach ($servicesLies as $service): ?>
                    <?php
                    $img = adminCategoryAssetUrl((string) ($service['image'] ?? ''), 'assets/images/services/default.jpg');
                    $dispo = trim((string) ($service['disponibilite'] ?? '—'));
                    $statut = trim((string) ($service['statut'] ?? '—'));
                    ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($service['titre'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars(adminCategoryAssetUrl('', 'assets/images/services/default.jpg'), ENT_QUOTES, 'UTF-8'); ?>';"></td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars((string) ($service['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(number_format((float) ($service['prix'] ?? 0), 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span style="display:inline-block;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:800;<?php echo categoryBadgeStyles($dispo, 'availability'); ?>"><?php echo htmlspecialchars($dispo, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span style="display:inline-block;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:800;<?php echo categoryBadgeStyles($statut, 'status'); ?>"><?php echo htmlspecialchars($statut, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($servicesLies)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;">Aucun service lié à cette catégorie.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div id="linkedServicesPager" class="table-pager"></div>
<?php endif; ?>

<script>
function toggleCategoryForm(){
    const panel = document.getElementById('categoryFormPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

document.querySelectorAll('#categoryIconGrid .cat-icon-option').forEach(button => {
    button.addEventListener('click', function(){
        document.querySelectorAll('#categoryIconGrid .cat-icon-option').forEach(item => item.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('categoryIconInput').value = this.dataset.icon || '';
    });
});

(function(){
    function createPager(tbodyId, rowSelector, pagerId, pageSize){
        const tbody = document.getElementById(tbodyId);
        const pager = document.getElementById(pagerId);
        if(!tbody || !pager) return null;
        let currentPage = 1;
        function rows(){ return Array.from(tbody.querySelectorAll(rowSelector)).filter(row => row.children.length > 1); }
        function build(totalPages, totalRows){
            pager.innerHTML = '';
            if(totalRows <= pageSize) return;
            const meta = document.createElement('span');
            meta.className = 'pager-meta';
            meta.textContent = `Page ${currentPage} / ${totalPages}`;
            pager.appendChild(meta);
            for(let i = 1; i <= totalPages; i++){
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = i;
                if(i === currentPage) btn.classList.add('active');
                btn.addEventListener('click', () => { currentPage = i; render(); });
                pager.appendChild(btn);
            }
        }
        function render(){
            const list = rows().filter(row => (row.dataset.filtered || '1') === '1');
            const totalPages = Math.max(1, Math.ceil(list.length / pageSize));
            if(currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            rows().forEach(row => row.style.display = 'none');
            list.forEach((row, index) => { row.style.display = (index >= start && index < end) ? '' : 'none'; });
            build(totalPages, list.length);
        }
        rows().forEach(row => row.dataset.filtered = '1');
        render();
        return { render, reset(){ currentPage = 1; render(); } };
    }

    const categoryPager = createPager('categoryTableBody', '.category-row', 'categoryPager', 8);
    createPager('linkedServicesBody', 'tr', 'linkedServicesPager', 6);

    const search = document.getElementById('categorySearch');
    const sort = document.getElementById('categorySort');
    const tbody = document.getElementById('categoryTableBody');
    const rows = () => tbody ? Array.from(tbody.querySelectorAll('.category-row')) : [];

    function filterRows(){
        const value = (search?.value || '').trim().toLowerCase();
        rows().forEach(row => {
            row.dataset.filtered = (row.dataset.name || '').includes(value) ? '1' : '0';
        });
        categoryPager && categoryPager.reset();
    }

    function sortRows(){
        const mode = sort?.value || '';
        const list = rows();
        list.sort((a, b) => {
            const nameA = a.dataset.name || '';
            const nameB = b.dataset.name || '';
            const servicesA = parseInt(a.dataset.services || '0', 10);
            const servicesB = parseInt(b.dataset.services || '0', 10);
            if (mode === 'az') return nameA.localeCompare(nameB);
            if (mode === 'za') return nameB.localeCompare(nameA);
            if (mode === 'services_desc') return servicesB - servicesA;
            if (mode === 'services_asc') return servicesA - servicesB;
            return 0;
        });
        list.forEach(row => tbody.appendChild(row));
        categoryPager && categoryPager.render();
    }

    if (search) search.addEventListener('input', filterRows);
    if (sort) sort.addEventListener('change', sortRows);
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