<?php
require_once __DIR__ . '/../../../controller/CategorieController.php';

$categorieController = new CategorieController();
$categories = $categorieController->listCategories();
?>

<section class="page-hero reveal">
    <span class="section-badge">Service</span>
    <h1 class="page-title">Ajouter un service</h1>
    <p class="page-intro">
        Remplissez les informations du nouveau service.
    </p>
</section>

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

<section class="add-service-wrap reveal">
    <div class="add-service-box">
        <form action="index.php?page=saveService" method="POST" enctype="multipart/form-data" class="add-service-form" id="addServiceForm" novalidate>

            <input type="hidden" name="statut" value="En attente">

            <div class="add-service-grid">
                <div class="field-block">
                    <label for="titre">TITRE DU SERVICE</label>
                    <input
                        type="text"
                        id="titre"
                        name="titre"
                        placeholder="Ex : Plombier urgence"
                        autocomplete="off"
                    >
                    <div id="err_titre" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_titre" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="id_categorie">CATÉGORIE</label>
                    <select id="id_categorie" name="id_categorie">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id_categorie']; ?>">
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
                    >
                    <div id="err_prix" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_prix" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label for="disponibilite">DISPONIBILITÉ</label>
                    <select id="disponibilite" name="disponibilite">
                        <option value="">-- Choisir --</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Indisponible">Indisponible</option>
                    </select>
                    <div id="err_disponibilite" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                    <div id="ok_disponibilite" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
                </div>

                <div class="field-block">
                    <label>STATUT</label>
                    <input type="text" value="En attente" disabled>
                    <div style="color:#8898aa; font-size:13px; margin-top:6px;">
                        Le statut sera défini automatiquement à l’ajout.
                    </div>
                </div>
            </div>

            <div class="field-block">
                <label for="description">DESCRIPTION</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Décrivez le service en détail..."
                ></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
                <div id="err_description" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                <div id="ok_description" style="display:none; color:#28a745; font-size:13px; margin-top:6px;"></div>
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
    const categorie = document.getElementById("id_categorie");
    const disponibilite = document.getElementById("disponibilite");
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
            errorBox.textContent = "✕ Veuillez choisir une image.";
            errorBox.style.display = "block";
            successBox.textContent = "";
            successBox.style.display = "none";
            resetPreview();
            return false;
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
    description.addEventListener("input", validateDescription);

    form.addEventListener("submit", function(e) {
        const okTitre = validateTitre();
        const okCategorie = validateCategorie();
        const okPrix = validatePrix();
        const okDisponibilite = validateDisponibilite();
        const okDescription = validateDescription();
        const okImage = validateImage();

        if (!(okTitre && okCategorie && okPrix && okDisponibilite && okDescription && okImage)) {
            e.preventDefault();
        }
    });
});
</script>