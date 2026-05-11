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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre         = trim($_POST['titre'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $prix          = trim($_POST['prix'] ?? '');
    $disponibilite = trim($_POST['disponibilite'] ?? '');
    $statut        = trim($_POST['statut'] ?? '');
    $id_categorie  = trim($_POST['categorie'] ?? '');
    $adresse       = trim($_POST['adresse'] ?? '');

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

    $image_path = null;

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
        // Géocodage de l'adresse via Nominatim (OpenStreetMap) — gratuit
        $lat = null;
        $lng = null;
        if (!empty($adresse)) {
            $coords = GeocoderService::geocode($adresse);
            if ($coords) {
                $lat = $coords['lat'];
                $lng = $coords['lng'];
            }
        }

        $service = new Service(
            $titre,
            $description,
            $prix,
            $disponibilite,
            $statut,
            $image_path,
            1,
            $id_categorie,
            $adresse ?: null,
            $lat,
            $lng
        );

        $serviceController->addService($service);
        header('Location: index.php?page=services&added=1');
        exit;
    }
}
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
        <form action="" method="POST" enctype="multipart/form-data" class="add-service-form" id="addServiceForm" novalidate>

            <div class="add-service-grid">
                <div class="field-block">
                    <label for="titre">TITRE DU SERVICE</label>
                    <input
                        type="text"
                        id="titre"
                        name="titre"
                        placeholder="Ex : Plombier urgence"
                        value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>"
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
                                <?php echo (($_POST['categorie'] ?? '') == $cat['id_categorie']) ? 'selected' : ''; ?>
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
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($_POST['prix'] ?? ''); ?>"
                    >
                    <div id="err_prix" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_prix" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="disponibilite">DISPONIBILITÉ</label>
                    <select id="disponibilite" name="disponibilite">
                        <option value="">-- Choisir --</option>
                        <option value="Disponible" <?php echo (($_POST['disponibilite'] ?? '') === 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Indisponible" <?php echo (($_POST['disponibilite'] ?? '') === 'Indisponible') ? 'selected' : ''; ?>>Indisponible</option>
                    </select>
                    <div id="err_disponibilite" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_disponibilite" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="statut">STATUT</label>
                    <select id="statut" name="statut">
                        <option value="">-- Choisir --</option>
                        <option value="Validé" <?php echo (($_POST['statut'] ?? '') === 'Validé') ? 'selected' : ''; ?>>Validé</option>
                        <option value="En attente" <?php echo (($_POST['statut'] ?? '') === 'En attente') ? 'selected' : ''; ?>>En attente</option>
                        <option value="Désactivé" <?php echo (($_POST['statut'] ?? '') === 'Désactivé') ? 'selected' : ''; ?>>Désactivé</option>
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
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
                <div id="err_description" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                <div id="ok_description" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
            </div>

            <!-- ── Champ Adresse + Mini-carte ── -->
            <div class="field-block full-width">
                <label for="adresse">📍 ADRESSE DU PRESTATAIRE <span style="font-size:11px;color:var(--muted);font-weight:500;">(optionnel — pour affichage sur carte)</span></label>
                <div style="display:flex;gap:10px;align-items:center;">
                    <input
                        type="text"
                        id="adresse"
                        name="adresse"
                        placeholder="Ex : Avenue Habib Bourguiba, Tunis, Tunisie"
                        value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>"
                        autocomplete="off"
                        style="flex:1;"
                    >
                    <button type="button" id="btnPreviewMap"
                        style="background:linear-gradient(135deg,#ee5828,#c94718);color:#fff;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;font-weight:700;font-size:13px;white-space:nowrap;">
                        🗺️ Prévisualiser
                    </button>
                </div>
                <div id="miniMapWrap" style="display:none;margin-top:12px;border-radius:14px;overflow:hidden;height:220px;border:2px solid #ee5828;">
                    <div id="miniMap" style="width:100%;height:100%;"></div>
                </div>
            </div>

            <div class="field-block full-width">
                <label for="image">IMAGE DU SERVICE</label>

                <label class="upload-box preview-mode" for="image" id="uploadBox">
                    <div class="preview-content" id="previewContent">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <div class="upload-icon">📷</div>
                            <div class="upload-title">Cliquez ou glissez une image ici</div>
                            <div class="upload-subtitle">JPG, PNG, WEBP — max 5 Mo</div>
                        </div>

                        <div class="image-preview-wrapper" id="imagePreviewWrapper" style="display:none;">
                            <div class="preview-file-name" id="fileName">Aucun fichier sélectionné</div>
                            <img id="imagePreview" src="" alt="Aperçu image du service">
                        </div>
                    </div>

                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" hidden>
                </label>

                <div id="err_image" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                <div id="ok_image" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
            </div>

            <div class="add-service-actions">
                <button type="submit" class="solid-btn">✓ Enregistrer</button>
                <a href="index.php?page=services" class="outline-btn">Annuler</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById("addServiceForm");
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

    const goRules = {
        titre: {
            validate: v => /^[A-Za-zÀ-ÿ\s]{3,}$/.test(v.trim()),
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
        if (!goRules.titre.validate(titre.value)) {
            setInvalid(titre, "err_titre", "ok_titre", goRules.titre.errorMsg);
            return false;
        }
        setValid(titre, "err_titre", "ok_titre", goRules.titre.successMsg);
        return true;
    }

    function validateCategorie() {
        if (!goRules.categorie.validate(categorie.value)) {
            setInvalid(categorie, "err_categorie", "ok_categorie", goRules.categorie.errorMsg);
            return false;
        }
        setValid(categorie, "err_categorie", "ok_categorie", goRules.categorie.successMsg);
        return true;
    }

    function validatePrix() {
        if (!goRules.prix.validate(prix.value)) {
            setInvalid(prix, "err_prix", "ok_prix", goRules.prix.errorMsg);
            return false;
        }
        setValid(prix, "err_prix", "ok_prix", goRules.prix.successMsg);
        return true;
    }

    function validateDisponibilite() {
        if (!goRules.disponibilite.validate(disponibilite.value)) {
            setInvalid(disponibilite, "err_disponibilite", "ok_disponibilite", goRules.disponibilite.errorMsg);
            return false;
        }
        setValid(disponibilite, "err_disponibilite", "ok_disponibilite", goRules.disponibilite.successMsg);
        return true;
    }

    function validateStatut() {
        if (!goRules.statut.validate(statut.value)) {
            setInvalid(statut, "err_statut", "ok_statut", goRules.statut.errorMsg);
            return false;
        }
        setValid(statut, "err_statut", "ok_statut", goRules.statut.successMsg);
        return true;
    }

    function validateDescription() {
        if (!goRules.description.validate(description.value)) {
            setInvalid(description, "err_description", "ok_description", goRules.description.errorMsg);
            return false;
        }
        setValid(description, "err_description", "ok_description", goRules.description.successMsg);
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

    if (description && descCount) {
        const updateCount = () => {
            descCount.textContent = description.value.length;
        };
        description.addEventListener('input', updateCount);
        updateCount();
    }

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
        }
    });
});
</script>

<!-- ── Leaflet.js — carte de prévisualisation adresse ── -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    let miniMap = null;
    let miniMarker = null;

    document.getElementById('btnPreviewMap').addEventListener('click', function(){
        const adresse = document.getElementById('adresse').value.trim();
        if (!adresse) {
            alert('Veuillez saisir une adresse d\'abord.');
            return;
        }

        const wrap = document.getElementById('miniMapWrap');
        wrap.style.display = 'block';

        // Initialiser la carte une seule fois
        if (!miniMap) {
            miniMap = L.map('miniMap').setView([34.0, 9.0], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(miniMap);
        }

        // Géocodage via Nominatim
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(adresse);
        fetch(url, { headers: { 'Accept-Language': 'fr' } })
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    alert('Adresse introuvable. Essayez d\'être plus précis (ex: Tunis, Tunisie).');
                    return;
                }
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);

                miniMap.setView([lat, lng], 14);

                const icon = L.divIcon({
                    html: '<div style="background:#ee5828;width:22px;height:22px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 3px 10px rgba(0,0,0,0.3);"></div>',
                    iconSize: [22, 22],
                    iconAnchor: [11, 22],
                    className: ''
                });

                if (miniMarker) miniMap.removeLayer(miniMarker);
                miniMarker = L.marker([lat, lng], { icon })
                    .addTo(miniMap)
                    .bindPopup('<b>📍 ' + adresse + '</b>')
                    .openPopup();

                // Forcer le recalcul de taille après affichage
                setTimeout(() => miniMap.invalidateSize(), 100);
            })
            .catch(() => alert('Erreur de connexion au service de géocodage.'));
    });
})();
</script>