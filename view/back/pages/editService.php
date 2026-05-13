<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/CategorieController.php';
require_once __DIR__ . '/../../../model/Service.php';
require_once __DIR__ . '/../../../service/GeocoderService.php';

$serviceController   = new ServiceController();
$categorieController = new CategorieController();
$categories          = $categorieController->listCategories();
$errors              = [];

// Récupérer l'ID depuis GET ou POST
$id = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int)$_GET['id']
    : (isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null);

if (!$id) {
    header('Location: index.php?page=services');
    exit;
}

$serviceData = $serviceController->getService($id);

if (!$serviceData) {
    header('Location: index.php?page=services');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre         = trim($_POST['titre'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $prix          = trim($_POST['prix'] ?? '');
    $disponibilite = trim($_POST['disponibilite'] ?? '');
    $statut        = trim($_POST['statut'] ?? '');
    $id_categorie  = trim($_POST['categorie'] ?? '');

    if ($titre === '' || !preg_match('/^[A-Za-zÀ-ÿ\s]{3,}$/u', $titre)) {
        $errors[] = "Le titre doit contenir uniquement des lettres et des espaces, avec au moins 3 caractères.";
    }

    if ($description === '' || strlen($description) < 5) {
        $errors[] = "La description doit contenir au moins 5 caractères.";
    }

    if ($prix === '' || !is_numeric($prix) || $prix <= 0) {
        $errors[] = "Le prix doit être un nombre positif.";
    }

    if ($disponibilite === '') {
        $errors[] = "Veuillez choisir la disponibilité.";
    }

    if ($statut === '') {
        $errors[] = "Veuillez choisir le statut.";
    }

    if ($id_categorie === '' || !is_numeric($id_categorie)) {
        $errors[] = "Veuillez choisir une catégorie valide.";
    }

    $image_path = $serviceData['image'];

    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format non autorisé. Utilisez JPG, PNG, WEBP ou GIF.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/../../../assets/images/services/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = uniqid('service_') . '.' . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/services/' . $filename;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image.";
            }
        }
    }

    if (empty($errors)) {
        $service = new Service(
            $titre, $description, $prix, $disponibilite, $statut,
            $image_path, $serviceData['id_provider'] ?? 1, $id_categorie,
            $serviceData['adresse'] ?? null,
            $serviceData['latitude'] ?? null,
            $serviceData['longitude'] ?? null
        );
        $serviceController->updateService($service, $id);
        header('Location: index.php?page=services&updated=1');
        exit;
    }
}

// Valeurs à afficher dans le formulaire
$titre         = $_POST['titre'] ?? $serviceData['titre'];
$description   = $_POST['description'] ?? $serviceData['description'];
$prix          = $_POST['prix'] ?? $serviceData['prix'];
$disponibilite = $_POST['disponibilite'] ?? $serviceData['disponibilite'];
$statut        = $_POST['statut'] ?? $serviceData['statut'];
$id_categorie  = $_POST['categorie'] ?? $serviceData['id_categorie'];
?>

<style>
.full-width {
    width: 100%;
}

.upload-box.preview-mode {
    display: block;
    width: 100%;
    min-height: 230px;
    border: 1px dashed rgba(255,255,255,0.12);
    border-radius: 18px;
    background: rgba(3, 20, 38, 0.55);
    cursor: pointer;
    padding: 24px;
    box-sizing: border-box;
    transition: 0.25s ease;
}

.upload-box.preview-mode:hover {
    border-color: rgba(255,255,255,0.22);
}

.preview-content {
    width: 100%;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.image-preview-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
}

.preview-file-name {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    text-align: center;
    word-break: break-word;
}

#imagePreview {
    display: block;
    max-width: 220px;
    max-height: 150px;
    width: auto;
    height: auto;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 8px 24px rgba(0,0,0,0.22);
}

.current-image-box {
    margin-bottom: 20px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.04);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 14px;
}

.current-image-box img {
    width: 72px;
    height: 54px;
    object-fit: cover;
    border-radius: 8px;
}
</style>

<?php if (!empty($errors)): ?>
    <div class="error-box">
        <?php foreach ($errors as $error): ?>
            <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="add-service-wrap reveal">
    <div class="add-service-box">

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <h2 style="color:#fff; font-size:20px; font-weight:700;">
                Modifier le service <span style="color:var(--orange);">#<?php echo $id; ?></span>
            </h2>
            <a href="index.php?page=services" class="outline-btn" style="text-decoration:none;">
                ← Retour à la liste
            </a>
        </div>

        <?php if (!empty($serviceData['image'])): ?>
        <div class="current-image-box">
            <img
                src="/GoService_v3/<?php echo htmlspecialchars(ltrim($serviceData['image'], '/')); ?>"
                alt="Image actuelle"
                onerror="this.style.display='none'"
            >
            <div>
                <div style="color:#fff; font-size:13px; font-weight:600; margin-bottom:3px;">Image actuelle</div>
                <div style="color:rgba(255,255,255,0.5); font-size:12px;">
                    Choisissez une nouvelle image pour la remplacer, ou laissez vide pour conserver.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="add-service-form" id="editServiceForm" novalidate>
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="add-service-grid">
                <div class="field-block">
                    <label for="titre">TITRE DU SERVICE</label>
                    <input
                        type="text"
                        id="titre"
                        name="titre"
                        placeholder="Ex : Plombier urgence"
                        value="<?php echo htmlspecialchars($titre); ?>"
                        autocomplete="off"
                    >
                    <div id="err_titre" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_titre" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="categorie">CATÉGORIE</label>
                    <select id="categorie" name="categorie">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option
                                value="<?php echo (int)$cat['id_categorie']; ?>"
                                <?php echo ($id_categorie == $cat['id_categorie']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($cat['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="err_categorie" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_categorie" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="prix">PRIX (€)</label>
                    <input
                        type="text"
                        id="prix"
                        name="prix"
                        placeholder="Ex : 50"
                        value="<?php echo htmlspecialchars($prix); ?>"
                        autocomplete="off"
                    >
                    <div id="err_prix" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_prix" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="disponibilite">DISPONIBILITÉ</label>
                    <select id="disponibilite" name="disponibilite">
                        <option value="">-- Choisir --</option>
                        <option value="Disponible" <?php echo ($disponibilite === 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Indisponible" <?php echo ($disponibilite === 'Indisponible') ? 'selected' : ''; ?>>Indisponible</option>
                    </select>
                    <div id="err_disponibilite" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_disponibilite" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="statut">STATUT</label>
                    <select id="statut" name="statut">
                        <option value="">-- Choisir --</option>
                        <option value="Validé" <?php echo ($statut === 'Validé') ? 'selected' : ''; ?>>Validé</option>
                        <option value="En attente" <?php echo ($statut === 'En attente') ? 'selected' : ''; ?>>En attente</option>
                        <option value="Désactivé" <?php echo ($statut === 'Désactivé') ? 'selected' : ''; ?>>Désactivé</option>
                    </select>
                    <div id="err_statut" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_statut" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>
            </div>

            <div class="field-block">
                <label for="description">DESCRIPTION</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Décrivez le service en détail..."
                ><?php echo htmlspecialchars($description); ?></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
                <div id="err_description" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                <div id="ok_description" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
            </div>

            <div class="field-block full-width">
                <label for="image">IMAGE DU SERVICE (optionnel — laisser vide pour conserver l'actuelle)</label>

                <label class="upload-box preview-mode" for="image" id="uploadBox">
                    <div class="preview-content" id="previewContent">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <div class="upload-icon">📷</div>
                            <div class="upload-title">Cliquez ou glissez une image ici</div>
                            <div class="upload-subtitle">JPG, PNG, WEBP — max 5 Mo</div>
                        </div>

                        <div class="image-preview-wrapper" id="imagePreviewWrapper" style="display:none;">
                            <div class="preview-file-name" id="fileName">Aucun fichier sélectionné</div>
                            <img id="imagePreview" src="" alt="Nouvelle image sélectionnée">
                        </div>
                    </div>

                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" hidden>
                </label>

                <div id="err_image" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                <div id="ok_image" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
            </div>

            <div class="add-service-actions">
                <button type="submit" class="solid-btn">✓ Mettre à jour</button>
                <a href="index.php?page=services" class="outline-btn">Annuler</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById("editServiceForm");
    const titre = document.getElementById("titre");
    const prix = document.getElementById("prix");
    const description = document.getElementById("description");
    const categorie = document.getElementById("categorie");
    const disponibilite = document.getElementById("disponibilite");
    const statut = document.getElementById("statut");
    const image = document.getElementById("image");
    const fileName = document.getElementById("fileName");
    const imagePreview = document.getElementById("imagePreview");
    const imagePreviewWrapper = document.getElementById("imagePreviewWrapper");
    const uploadPlaceholder = document.getElementById("uploadPlaceholder");
    const descCount = document.getElementById("descCount");

    const rules = {
        titre: {
            validate: v => /^[A-Za-zÀ-ÿ\s]{3,}$/u.test(v.trim()),
            errorMsg: "✕ Le titre doit contenir uniquement des lettres et des espaces, avec au moins 3 caractères.",
            successMsg: "✓ Titre valide."
        },
        description: {
            validate: v => v.trim().length >= 5,
            errorMsg: "✕ La description doit contenir au moins 5 caractères.",
            successMsg: "✓ Description valide."
        },
        categorie: {
            validate: v => v !== "",
            errorMsg: "✕ Veuillez choisir une catégorie.",
            successMsg: "✓ Catégorie sélectionnée."
        },
        prix: {
            validate: v => v.trim() !== "" && !isNaN(v) && Number(v) > 0,
            errorMsg: "✕ Le prix doit être un nombre positif.",
            successMsg: "✓ Prix valide."
        },
        disponibilite: {
            validate: v => v !== "",
            errorMsg: "✕ Veuillez choisir la disponibilité.",
            successMsg: "✓ Disponibilité sélectionnée."
        },
        statut: {
            validate: v => v !== "",
            errorMsg: "✕ Veuillez choisir le statut.",
            successMsg: "✓ Statut sélectionné."
        }
    };

    function setInvalid(field, errorId, successId, message) {
        field.style.border = "1px solid #ff6b6b";
        field.style.boxShadow = "0 0 0 1px rgba(255,107,107,0.15)";

        const errorBox = document.getElementById(errorId);
        const successBox = document.getElementById(successId);

        errorBox.textContent = message;
        errorBox.style.display = "block";

        successBox.textContent = "";
        successBox.style.display = "none";
    }

    function setValid(field, errorId, successId, message) {
        field.style.border = "1px solid #28a745";
        field.style.boxShadow = "0 0 0 1px rgba(40,167,69,0.15)";

        const errorBox = document.getElementById(errorId);
        const successBox = document.getElementById(successId);

        errorBox.textContent = "";
        errorBox.style.display = "none";

        successBox.textContent = message;
        successBox.style.display = "block";
    }

    function validateTitre() {
        if (!rules.titre.validate(titre.value)) {
            setInvalid(titre, "err_titre", "ok_titre", rules.titre.errorMsg);
            return false;
        }
        setValid(titre, "err_titre", "ok_titre", rules.titre.successMsg);
        return true;
    }

    function validateCategorie() {
        if (!rules.categorie.validate(categorie.value)) {
            setInvalid(categorie, "err_categorie", "ok_categorie", rules.categorie.errorMsg);
            return false;
        }
        setValid(categorie, "err_categorie", "ok_categorie", rules.categorie.successMsg);
        return true;
    }

    function validatePrix() {
        if (!rules.prix.validate(prix.value)) {
            setInvalid(prix, "err_prix", "ok_prix", rules.prix.errorMsg);
            return false;
        }
        setValid(prix, "err_prix", "ok_prix", rules.prix.successMsg);
        return true;
    }

    function validateDisponibilite() {
        if (!rules.disponibilite.validate(disponibilite.value)) {
            setInvalid(disponibilite, "err_disponibilite", "ok_disponibilite", rules.disponibilite.errorMsg);
            return false;
        }
        setValid(disponibilite, "err_disponibilite", "ok_disponibilite", rules.disponibilite.successMsg);
        return true;
    }

    function validateStatut() {
        if (!rules.statut.validate(statut.value)) {
            setInvalid(statut, "err_statut", "ok_statut", rules.statut.errorMsg);
            return false;
        }
        setValid(statut, "err_statut", "ok_statut", rules.statut.successMsg);
        return true;
    }

    function validateDescription() {
        if (!rules.description.validate(description.value)) {
            setInvalid(description, "err_description", "ok_description", rules.description.errorMsg);
            return false;
        }
        setValid(description, "err_description", "ok_description", rules.description.successMsg);
        return true;
    }

    function resetPreview() {
        fileName.textContent = "Aucun fichier sélectionné";
        imagePreview.src = "";
        imagePreviewWrapper.style.display = "none";
        uploadPlaceholder.style.display = "flex";
    }

    function validateImage() {
        const errorBox = document.getElementById("err_image");
        const successBox = document.getElementById("ok_image");

        if (image.files.length === 0) {
            errorBox.textContent = "";
            errorBox.style.display = "none";
            successBox.textContent = "";
            successBox.style.display = "none";
            resetPreview();
            return true;
        }

        const allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const file = image.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowed.includes(ext)) {
            errorBox.textContent = "✕ Format non autorisé. Utilisez JPG, PNG, WEBP ou GIF.";
            errorBox.style.display = "block";
            successBox.textContent = "";
            successBox.style.display = "none";
            resetPreview();
            return false;
        }

        if (file.size > 5 * 1024 * 1024) {
            errorBox.textContent = "✕ L'image ne doit pas dépasser 5 Mo.";
            errorBox.style.display = "block";
            successBox.textContent = "";
            successBox.style.display = "none";
            resetPreview();
            return false;
        }

        errorBox.textContent = "";
        errorBox.style.display = "none";
        successBox.textContent = "✓ Image valide.";
        successBox.style.display = "block";
        return true;
    }

    function updateDescCount() {
        descCount.textContent = description.value.length;
        if (description.value.length > 450) {
            descCount.style.color = "#ff6b6b";
        } else {
            descCount.style.color = "";
        }
    }

    description.addEventListener('input', updateDescCount);
    updateDescCount();

    if (image) {
        image.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowed.includes(ext) || file.size > 5 * 1024 * 1024) {
                    validateImage();
                    return;
                }

                fileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewWrapper.style.display = "flex";
                    uploadPlaceholder.style.display = "none";
                };
                reader.readAsDataURL(file);
            } else {
                resetPreview();
            }

            validateImage();
        });
    }

    titre.addEventListener("input", validateTitre);
    categorie.addEventListener("change", validateCategorie);
    prix.addEventListener("input", validatePrix);
    disponibilite.addEventListener("change", validateDisponibilite);
    statut.addEventListener("change", validateStatut);
    description.addEventListener("input", validateDescription);

    form.addEventListener("submit", function(e) {
        const okTitre = validateTitre();
        const okCategorie = validateCategorie();
        const okPrix = validatePrix();
        const okDisponibilite = validateDisponibilite();
        const okStatut = validateStatut();
        const okDescription = validateDescription();
        const okImage = validateImage();

        if (!(okTitre && okCategorie && okPrix && okDisponibilite && okStatut && okDescription && okImage)) {
            e.preventDefault();

            const firstInvalid = form.querySelector('#titre[style*="ff6b6b"], #categorie[style*="ff6b6b"], #prix[style*="ff6b6b"], #disponibilite[style*="ff6b6b"], #statut[style*="ff6b6b"], #description[style*="ff6b6b"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
</script>

<!-- ══ Carte localisation (lecture seule pour l'admin) ══ -->
<?php if (!empty($serviceData['latitude']) && !empty($serviceData['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
.admin-map-card{background:var(--panel);border:1px solid var(--border);border-radius:18px;overflow:hidden;margin:24px 0;box-shadow:0 8px 24px rgba(7,20,34,.08);}
.admin-map-header{padding:16px 22px;display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--border);}
.admin-map-icon{width:40px;height:40px;background:rgba(238,88,40,.12);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.admin-map-title{font-size:14px;font-weight:800;color:var(--text);}
.admin-map-addr{font-size:12px;color:var(--muted);margin-top:2px;}
.admin-map-note{font-size:11px;color:#4cd774;margin-top:2px;}
#adminServiceMap{height:280px;width:100%;}
</style>
<div class="admin-map-card">
    <div class="admin-map-header">
        <div class="admin-map-icon">📍</div>
        <div>
            <div class="admin-map-title">Localisation du prestataire <span style="font-size:11px;font-weight:400;color:var(--muted);">(lecture seule — saisie par le provider)</span></div>
            <div class="admin-map-addr"><?= htmlspecialchars($serviceData['adresse'] ?? 'Non renseignée') ?></div>
            <div class="admin-map-note">✓ Coordonnées : <?= round((float)$serviceData['latitude'],4) ?>, <?= round((float)$serviceData['longitude'],4) ?></div>
        </div>
    </div>
    <div id="adminServiceMap"></div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    const lat = <?= (float)$serviceData['latitude'] ?>;
    const lng = <?= (float)$serviceData['longitude'] ?>;
    const adresse = <?= json_encode($serviceData['adresse'] ?? '') ?>;
    const titre   = <?= json_encode($serviceData['titre'] ?? '') ?>;

    const map = L.map('adminServiceMap', {scrollWheelZoom:false}).setView([lat,lng],14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© <a href="https://openstreetmap.org">OpenStreetMap</a>',maxZoom:19
    }).addTo(map);

    const icon = L.divIcon({
        html:'<div style="background:linear-gradient(135deg,#ff7b39,#f15a24);width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 4px 12px rgba(238,88,40,.4);"></div>',
        iconSize:[28,28],iconAnchor:[14,28],className:''
    });
    L.marker([lat,lng],{icon}).addTo(map)
     .bindPopup('<b>'+titre+'</b><br><small>'+adresse+'</small>')
     .openPopup();
    L.circle([lat,lng],{color:'#ee5828',fillColor:'#ee5828',fillOpacity:.05,weight:1.5,radius:1200}).addTo(map);
})();
</script>
<?php else: ?>
<div style="background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin:20px 0;display:flex;align-items:center;gap:12px;">
    <span style="font-size:20px;">📍</span>
    <div>
        <div style="font-size:13px;font-weight:700;color:var(--text);">Pas de localisation</div>
        <div style="font-size:12px;color:var(--muted);">Ce prestataire n'a pas encore saisi son adresse depuis le front office.</div>
    </div>
</div>
<?php endif; ?>