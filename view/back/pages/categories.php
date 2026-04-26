<?php
require_once __DIR__ . '/../../../controller/CategorieController.php';
require_once __DIR__ . '/../../../model/Categorie.php';

$categorieController = new CategorieController();
$errors  = [];
$success = '';

// ── SUPPRESSION ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($categorieController->hasServices($id)) {
        $errors[] = "Impossible de supprimer : cette catégorie contient des services. Supprimez d'abord les services associés.";
    } else {
        $categorieController->deleteCategorie($id);
        header('Location: index.php?page=categories&deleted=1');
        exit;
    }
}

// ── AJOUT ──
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icone       = trim($_POST['icone'] ?? '');

    if ($nom === '') {
        $errors[] = "Le nom est obligatoire.";
    } elseif (strlen($nom) < 3) {
        $errors[] = "Le nom doit contenir au moins 3 caractères.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $nom)) {
        $errors[] = "Le nom doit contenir uniquement des lettres et des espaces.";
    }

    if ($description === '' || strlen($description) < 5) {
        $errors[] = "La description doit contenir au moins 5 caractères.";
    }

    if ($icone === '') {
        $errors[] = "Veuillez choisir une icône.";
    }

    if (empty($errors)) {
        $cat = new Categorie($nom, $description, $icone);
        $categorieController->addCategorie($cat);
        header('Location: index.php?page=categories&added=1');
        exit;
    }
}

// ── MODIFICATION ──
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id          = (int)($_POST['id'] ?? 0);
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icone       = trim($_POST['icone'] ?? '');

    if ($nom === '') {
        $errors[] = "Le nom est obligatoire.";
    } elseif (strlen($nom) < 3) {
        $errors[] = "Le nom doit contenir au moins 3 caractères.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $nom)) {
        $errors[] = "Le nom doit contenir uniquement des lettres et des espaces.";
    }

    if ($description === '' || strlen($description) < 5) {
        $errors[] = "La description doit contenir au moins 5 caractères.";
    }

    if ($icone === '') {
        $errors[] = "Veuillez choisir une icône.";
    }

    if (empty($errors)) {
        $cat = new Categorie($nom, $description, $icone);
        $categorieController->updateCategorie($cat, $id);
        header('Location: index.php?page=categories&updated=1');
        exit;
    }
}

// ── CHARGER LES DONNÉES ──
$categories = $categorieController->listCategoriesWithCount();

$total = count($categories);
$editData = null;
$servicesLies = [];
$categorieSelectionnee = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editData = $categorieController->getCategorie((int)$_GET['edit']);
}

if (isset($_GET['show']) && is_numeric($_GET['show'])) {
    $idShow = (int)$_GET['show'];
    $categorieSelectionnee = $categorieController->getCategorie($idShow);
    $servicesLies = $categorieController->getServicesByCategorie($idShow);
}

// Icônes disponibles
$icones = [
    '🔧' => 'Plomberie / Réparation',
    '⚡' => 'Électricité / Énergie',
    '🎨' => 'Peinture / Art / Design',
    '🌿' => 'Jardinage / Nature',
    '🧹' => 'Ménage / Nettoyage',
    '💻' => 'Informatique / Tech',
    '🏗️' => 'Construction / BTP',
    '🚗' => 'Automobile / Transport',
    '📚' => 'Éducation / Formation',
    '💊' => 'Santé / Bien-être',
    '🍽️' => 'Restauration / Traiteur',
    '📸' => 'Photo / Vidéo / Médias',
    '⚖️' => 'Juridique / Conseil',
    '💰' => 'Finance / Comptabilité',
    '🔒' => 'Sécurité / Surveillance',
    '🏠' => 'Immobilier / Déménagement',
    '✂️' => 'Beauté / Coiffure / Mode',
    '🎵' => 'Musique / Événements',
    '🐾' => 'Animaux / Vétérinaire',
    '🌍' => 'Traduction / International',
];

function catDispoBadgeStyle(string $dispo): string {
    $dispo = trim($dispo);
    if ($dispo === 'Disponible') {
        return 'background:rgba(41,180,99,0.14); color:#4cd774;';
    }
    return 'background:rgba(255,95,95,0.14); color:#ff8b8b;';
}

function catStatutBadgeStyle(string $statut): string {
    $statut = trim($statut);
    if ($statut === 'Validé') {
        return 'background:rgba(76,138,255,0.14); color:#6ea8ff;';
    }
    if ($statut === 'En attente') {
        return 'background:rgba(255,193,7,0.14); color:#ffd04d;';
    }
    return 'background:rgba(160,160,160,0.15); color:#d7d7d7;';
}
?>

<?php if (isset($_GET['added'])): ?>
<div class="gs-alert gs-alert-ok">✓ Catégorie ajoutée avec succès.</div>
<?php elseif (isset($_GET['updated'])): ?>
<div class="gs-alert gs-alert-ok">✓ Catégorie mise à jour avec succès.</div>
<?php elseif (isset($_GET['deleted'])): ?>
<div class="gs-alert gs-alert-ok">✓ Catégorie supprimée.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="gs-alert gs-alert-err">
    <?php foreach ($errors as $e): ?>
        <div>✕ <?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.gs-alert{padding:13px 18px;border-radius:12px;font-size:14px;font-weight:500;margin-bottom:18px;border-left:4px solid transparent;}
.gs-alert-ok{background:rgba(76,175,80,0.1);color:#2e7d32;border-left-color:#4CAF50;}
.gs-alert-err{background:rgba(239,68,68,0.1);color:#c62828;border-left-color:#e53935;}

.form-panel{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;}
.form-panel-title{font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--text);margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--line);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.form-row.single{grid-template-columns:1fr;}
.field{display:flex;flex-direction:column;gap:6px;}
.field label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.field input,.field textarea,.field select{padding:11px 14px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--text);font-size:14px;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.field input:focus,.field textarea:focus,.field select:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(238,88,40,0.12);}
.field input.invalid,.field textarea.invalid,.field select.invalid{border-color:#e53935;box-shadow:0 0 0 2px rgba(229,57,53,0.12);}
.field input.valid,.field textarea.valid,.field select.valid{border-color:#4CAF50;}
.field-err{display:none;font-size:12px;color:#e53935;margin-top:3px;}
.field-err::before{content:"✕ ";}
.field-err.show{display:block;}
.field-ok{display:none;font-size:12px;color:#2e7d32;margin-top:3px;}
.field-ok::before{content:"✓ ";}
.field-ok.show{display:block;}
.field textarea{resize:vertical;min-height:90px;}
.field select option{background:var(--card);}

.icone-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;}
.icone-option{display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 6px;border-radius:10px;border:1px solid var(--line);cursor:pointer;transition:all .15s;background:var(--bg);}
.icone-option:hover{border-color:var(--orange);background:rgba(238,88,40,0.05);}
.icone-option.selected{border-color:var(--orange);background:rgba(238,88,40,0.1);}
.icone-option .em{font-size:22px;}
.icone-option .lb{font-size:10px;color:var(--muted);text-align:center;line-height:1.3;}

.stats-row{
    display:grid;
    grid-template-columns:repeat(2, minmax(240px, 1fr));
    gap:22px;
    margin:26px 0 24px;
}

.stat-sm{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:6px;
    padding:30px 34px;
    min-height:116px;
    display:flex;
    align-items:center;
    gap:22px;
    box-shadow:0 12px 28px rgba(7,20,34,0.08);
}

.stat-sm-icon{
    width:62px;
    height:62px;
    border-radius:50%;
    background:rgba(238,88,40,0.10);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:26px;
}

.stat-sm-num{
    font-family:'Poppins',sans-serif;
    font-size:34px;
    line-height:1;
    font-weight:900;
    color:var(--text);
}

.stat-sm-lbl{
    margin-top:8px;
    font-size:15px;
    color:var(--muted);
}

@media(max-width:800px){
    .stats-row{
        grid-template-columns:1fr;
    }
}

.toggle-add-btn{margin-bottom:16px;}

body.admin-body.dark .btn-edit-sm{
    background: rgba(76,138,255,0.18) !important;
    color: #8ab7ff !important;
    border: 1px solid rgba(138,183,255,0.38) !important;
}
body.admin-body.dark .btn-edit-sm:hover{
    background: rgba(76,138,255,0.30) !important;
    color: #d7e8ff !important;
}
</style>

<section class="action-bar reveal" style="margin-bottom:24px;">
    <div class="search-box">
        <input
            type="text"
            id="categorySearch"
            placeholder="Rechercher une catégorie..."
            autocomplete="off"
        >

        <select id="categorySort">
            <option value="">Trier par</option>
            <option value="az">Nom A-Z</option>
            <option value="za">Nom Z-A</option>
            <option value="services_desc">Plus de services</option>
            <option value="services_asc">Moins de services</option>
        </select>
    </div>

    <div class="export-bar" style="display:flex; gap:12px; align-items:center;">
        <button class="solid-btn" type="button" onclick="toggleAddForm()">
            + Ajouter une catégorie
        </button>
    </div>
</section>

<div class="stats-row">
    <div class="stat-sm">
        <div class="stat-sm-icon">🗂️</div>
        <div>
            <div class="stat-sm-num"><?php echo $total; ?></div>
            <div class="stat-sm-lbl">Catégories</div>
        </div>
    </div>
    <div class="stat-sm">
        <div class="stat-sm-icon">⚡</div>
        <div>
            <div class="stat-sm-num"><?php echo array_sum(array_column($categories, 'nb_services')); ?></div>
            <div class="stat-sm-lbl">Services liés</div>
        </div>
    </div>
</div>



<div id="addFormSection" style="display:none;">
    <div class="form-panel">
        <div class="form-panel-title">Nouvelle catégorie</div>
        <form method="POST" id="addCatForm" novalidate>
            <input type="hidden" name="action" value="add">

            <div class="form-row">
                <div class="field">
                    <label for="add_nom">Nom de la catégorie</label>
                    <input type="text" id="add_nom" name="nom" placeholder="Ex : Intelligence Artificielle" autocomplete="off">
                    <div class="field-err" id="ae-nom"></div>
                    <div class="field-ok" id="ao-nom">Nom valide</div>
                </div>

                <div class="field">
                    <label for="add_desc">Description</label>
                    <textarea id="add_desc" name="description" placeholder="Décrivez cette catégorie..."></textarea>
                    <div class="field-err" id="ae-desc"></div>
                    <div class="field-ok" id="ao-desc">Description valide</div>
                </div>
            </div>

            <div class="field" style="margin-bottom:16px;">
                <label>Icône <span id="add_icone_preview" style="font-size:20px;margin-left:8px;"></span></label>
                <input type="hidden" id="add_icone" name="icone">
                <div class="icone-grid" id="add_icone_grid">
                    <?php foreach ($icones as $em => $lb): ?>
                    <div class="icone-option" onclick="selectIcone('add', '<?php echo htmlspecialchars($em); ?>', this)">
                        <span class="em"><?php echo $em; ?></span>
                        <span class="lb"><?php echo htmlspecialchars($lb); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="field-err" id="ae-icone"></div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="solid-btn">✓ Enregistrer</button>
                <button type="button" class="ghost-btn" onclick="toggleAddForm()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<?php if ($editData): ?>
<div class="form-panel">
    <div class="form-panel-title" style="display:flex;justify-content:space-between;align-items:center;">
        <span>Modifier la catégorie — <?php echo htmlspecialchars($editData['nom']); ?></span>
        <a href="index.php?page=categories" class="ghost-btn" style="font-size:13px;">✕ Annuler</a>
    </div>

    <form method="POST" id="editCatForm" novalidate>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?php echo $editData['id_categorie']; ?>">

        <div class="form-row">
            <div class="field">
                <label for="edit_nom">Nom de la catégorie</label>
                <input type="text" id="edit_nom" name="nom" value="<?php echo htmlspecialchars($editData['nom']); ?>" autocomplete="off">
                <div class="field-err" id="ee-nom"></div>
                <div class="field-ok" id="eo-nom">Nom valide</div>
            </div>

            <div class="field">
                <label for="edit_desc">Description</label>
                <textarea id="edit_desc" name="description"><?php echo htmlspecialchars($editData['description'] ?? ''); ?></textarea>
                <div class="field-err" id="ee-desc"></div>
                <div class="field-ok" id="eo-desc">Description valide</div>
            </div>
        </div>

        <div class="field" style="margin-bottom:16px;">
            <label>Icône <span id="edit_icone_preview" style="font-size:20px;margin-left:8px;"><?php echo htmlspecialchars($editData['icone'] ?? ''); ?></span></label>
            <input type="hidden" id="edit_icone" name="icone" value="<?php echo htmlspecialchars($editData['icone'] ?? ''); ?>">
            <div class="icone-grid" id="edit_icone_grid">
                <?php foreach ($icones as $em => $lb): ?>
                <div class="icone-option <?php echo ($editData['icone'] ?? '') === $em ? 'selected' : ''; ?>"
                     onclick="selectIcone('edit', '<?php echo htmlspecialchars($em); ?>', this)">
                    <span class="em"><?php echo $em; ?></span>
                    <span class="lb"><?php echo htmlspecialchars($lb); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="field-err" id="ee-icone"></div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="solid-btn">✓ Mettre à jour</button>
            <a href="index.php?page=categories" class="ghost-btn">Annuler</a>
        </div>
    </form>
</div>
<?php endif; ?>

<section class="admin-panel reveal" style="margin-top:22px;">
    <div class="table-container" style="border-radius:22px; overflow:hidden; box-shadow:0 18px 40px rgba(7, 20, 34, 0.18);">
        <table class="module-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02);">
                    <th style="padding:18px;">Icône</th>
                    <th style="padding:18px;">Nom</th>
                    <th style="padding:18px;">Description</th>
                    <th style="padding:18px;">Services liés</th>
                    <th style="padding:18px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr class="category-row"
    data-name="<?php echo mb_strtolower(htmlspecialchars($cat['nom'])); ?>"
    data-services="<?php echo (int)$cat['nb_services']; ?>"
    style="border-top:1px solid rgba(255,255,255,0.06);">
                        <td style="padding:18px; font-size:22px;">
                            <?php echo htmlspecialchars($cat['icone'] ?? '🗂️'); ?>
                        </td>

                        <td style="padding:18px; font-weight:700;">
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </td>

                        <td style="padding:18px;">
                            <?php echo htmlspecialchars($cat['description']); ?>
                        </td>

                        <td style="padding:18px;">
                            <span style="padding:6px 12px; border-radius:999px; background:rgba(238,88,40,0.12); color:#ff8b5a; font-weight:700;">
                                <?php echo $cat['nb_services']; ?>
                            </span>
                        </td>

                        <td style="padding:18px;">
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="index.php?page=categories&show=<?php echo $cat['id_categorie']; ?>"
                                   style="padding:8px 14px; border-radius:10px; background:rgba(238,88,40,0.14); color:#ff8b5a; font-weight:700; text-decoration:none;">
                                    Voir services liés
                                </a>

                                <a href="index.php?page=categories&edit=<?php echo $cat['id_categorie']; ?>"
                                   style="padding:8px 14px; border-radius:10px; background:rgba(76,138,255,0.14); color:#8ab7ff; font-weight:700; text-decoration:none;">
                                    Modifier
                                </a>

                                <a href="index.php?page=categories&delete=<?php echo $cat['id_categorie']; ?>"
                                   onclick="return confirm('Supprimer cette catégorie ?');"
                                   style="padding:8px 14px; border-radius:10px; background:rgba(255,95,95,0.14); color:#ff8b8b; font-weight:700; text-decoration:none;">
                                    Supprimer
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px;">
                            Aucune catégorie trouvée.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($categorieSelectionnee): ?>
<section class="admin-panel reveal" style="margin-top:24px;">
    <div style="background:var(--card); border:1px solid var(--line); border-radius:22px; overflow:hidden; box-shadow:0 18px 40px rgba(7,20,34,0.18);">
        <div style="padding:22px 24px; border-bottom:1px solid var(--line);">
            <h2 style="margin:0; font-size:24px; font-weight:800; color:var(--text);">
                Services liés à la catégorie :
                <span style="color:var(--orange);">
                    <?php echo htmlspecialchars($categorieSelectionnee['nom']); ?>
                </span>
            </h2>
            <p style="margin:8px 0 0; color:var(--muted); font-size:14px;">
                <?php echo htmlspecialchars($categorieSelectionnee['description'] ?? ''); ?>
            </p>
        </div>

        <div class="table-container">
            <table class="module-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02);">
                        <th style="padding:18px;">Image</th>
                        <th style="padding:18px;">Titre</th>
                        <th style="padding:18px;">Prix</th>
                        <th style="padding:18px;">Disponibilité</th>
                        <th style="padding:18px;">Statut</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($servicesLies)): ?>
                        <?php foreach ($servicesLies as $service): ?>
                            <?php
                            $img = !empty($service['image'])
                                ? '/GoService/' . ltrim($service['image'], '/')
                                : '/GoService/assets/images/service/default.jpg';

                            $dispo = trim((string)($service['disponibilite'] ?? ''));
                            $statut = trim((string)($service['statut'] ?? ''));
                            ?>
                            <tr style="border-top:1px solid rgba(255,255,255,0.06);">
                                <td style="padding:14px 18px;">
                                    <img
                                        src="<?php echo htmlspecialchars($img); ?>"
                                        alt="<?php echo htmlspecialchars($service['titre'] ?? 'Service'); ?>"
                                        style="width:64px; height:48px; object-fit:cover; border-radius:10px; display:block;"
                                    >
                                </td>

                                <td style="padding:18px; font-weight:700;">
                                    <?php echo htmlspecialchars($service['titre'] ?? ''); ?>
                                </td>

                                <td style="padding:18px;">
                                    <?php echo number_format((float)($service['prix'] ?? 0), 2, '.', ''); ?> €
                                </td>

                                <td style="padding:18px;">
                                    <span style="display:inline-block; padding:7px 14px; border-radius:999px; font-size:13px; font-weight:700; <?php echo catDispoBadgeStyle($dispo); ?>">
                                        <?php echo htmlspecialchars($dispo); ?>
                                    </span>
                                </td>

                                <td style="padding:18px;">
                                    <span style="display:inline-block; padding:7px 14px; border-radius:999px; font-size:13px; font-weight:700; <?php echo catStatutBadgeStyle($statut); ?>">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px;">
                                Aucun service lié à cette catégorie.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function toggleAddForm() {
    var s = document.getElementById('addFormSection');
    s.style.display = s.style.display === 'none' ? 'block' : 'none';
}

function selectIcone(prefix, em, el) {
    document.querySelectorAll('#' + prefix + '_icone_grid .icone-option')
        .forEach(o => o.classList.remove('selected'));

    el.classList.add('selected');

    document.getElementById(prefix + '_icone').value = em;
    document.getElementById(prefix + '_icone_preview').textContent = em;

    var errEl = document.getElementById(prefix === 'add' ? 'ae-icone' : 'ee-icone');
    if (errEl) errEl.classList.remove('show');
}

function makeValidator(idNom, idDesc, idIcone, errNomId, okNomId, errDescId, okDescId, errIconeId) {
    var nomEl = document.getElementById(idNom);
    var descEl = document.getElementById(idDesc);
    var iconeEl = document.getElementById(idIcone);

    function vNom() {
        var v = nomEl.value.trim();
        var regex = /^[a-zA-ZÀ-ÿ\s]+$/;

        if (v === "") {
            showError(nomEl, errNomId, okNomId, "Nom obligatoire");
            return false;
        }

        if (v.length < 3) {
            showError(nomEl, errNomId, okNomId, "Min 3 caractères");
            return false;
        }

        if (!regex.test(v)) {
            showError(nomEl, errNomId, okNomId, "Lettres seulement");
            return false;
        }

        showSuccess(nomEl, errNomId, okNomId);
        return true;
    }

    function vDesc() {
        var v = descEl.value.trim();
        if (v.length < 5) {
            showError(descEl, errDescId, okDescId, "Min 5 caractères");
            return false;
        }
        showSuccess(descEl, errDescId, okDescId);
        return true;
    }

    function vIcone() {
        if (!iconeEl.value) {
            document.getElementById(errIconeId).textContent = "Choisir icône";
            document.getElementById(errIconeId).classList.add("show");
            return false;
        }
        document.getElementById(errIconeId).classList.remove("show");
        return true;
    }

    function showError(el, errId, okId, msg) {
        el.classList.add("invalid");
        el.classList.remove("valid");
        document.getElementById(errId).textContent = msg;
        document.getElementById(errId).classList.add("show");
        document.getElementById(okId).classList.remove("show");
    }

    function showSuccess(el, errId, okId) {
        el.classList.remove("invalid");
        el.classList.add("valid");
        document.getElementById(errId).classList.remove("show");
        document.getElementById(okId).classList.add("show");
    }

    nomEl.addEventListener("input", vNom);
    descEl.addEventListener("input", vDesc);

    return { vNom, vDesc, vIcone };
}

var addForm = document.getElementById("addCatForm");
if (addForm) {
    var vAdd = makeValidator("add_nom", "add_desc", "add_icone", "ae-nom", "ao-nom", "ae-desc", "ao-desc", "ae-icone");
    addForm.addEventListener("submit", function(e) {
        if (!(vAdd.vNom() && vAdd.vDesc() && vAdd.vIcone())) {
            e.preventDefault();
        }
    });
}

var editForm = document.getElementById("editCatForm");
if (editForm) {
    var vEdit = makeValidator("edit_nom", "edit_desc", "edit_icone", "ee-nom", "eo-nom", "ee-desc", "eo-desc", "ee-icone");
    editForm.addEventListener("submit", function(e) {
        if (!(vEdit.vNom() && vEdit.vDesc() && vEdit.vIcone())) {
            e.preventDefault();
        }
    });
}

<?php if (!empty($errors) && ($_POST['action'] ?? '') === 'add'): ?>
document.getElementById('addFormSection').style.display = 'block';
<?php endif; ?>

const categorySearch = document.getElementById("categorySearch");
const categorySort = document.getElementById("categorySort");

function filterCategories() {
    const value = categorySearch.value.trim().toLowerCase();
    const rows = document.querySelectorAll(".category-row");

    rows.forEach(row => {
        const name = row.dataset.name || "";

        if (name.startsWith(value)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

function sortCategories() {
    const tbody = document.querySelector(".module-table tbody");
    const rows = Array.from(document.querySelectorAll(".category-row"));
    const sortValue = categorySort.value;

    rows.sort((a, b) => {
        const nameA = a.dataset.name || "";
        const nameB = b.dataset.name || "";
        const servicesA = parseInt(a.dataset.services || "0");
        const servicesB = parseInt(b.dataset.services || "0");

        if (sortValue === "az") return nameA.localeCompare(nameB);
        if (sortValue === "za") return nameB.localeCompare(nameA);
        if (sortValue === "services_desc") return servicesB - servicesA;
        if (sortValue === "services_asc") return servicesA - servicesB;

        return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
    filterCategories();
}

if (categorySearch) {
    categorySearch.addEventListener("input", filterCategories);
}

if (categorySort) {
    categorySort.addEventListener("change", sortCategories);
}
</script>