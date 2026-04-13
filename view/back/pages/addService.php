<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/CategorieController.php';
require_once __DIR__ . '/../../../model/Service.php';

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

    if ($titre === '' || strlen($titre) < 3) {
        $errors[] = "Le titre doit contenir au moins 3 caractères.";
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
        $service = new Service(
            $titre,
            $description,
            $prix,
            $disponibilite,
            $statut,
            $image_path,
            1,
            $id_categorie
        );

        $serviceController->addService($service);
        header('Location: index.php?page=services&added=1');
        exit;
    }
}
?>

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
                    >
                    <div id="err_titre" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
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
                </div>

                <div class="field-block">
                    <label for="prix">PRIX (€)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="prix"
                        name="prix"
                        placeholder="Ex : 50"
                        value="<?php echo htmlspecialchars($_POST['prix'] ?? ''); ?>"
                    >
                    <div id="err_prix" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
                </div>

                <div class="field-block">
                    <label for="disponibilite">DISPONIBILITÉ</label>
                    <select id="disponibilite" name="disponibilite">
                        <option value="">-- Choisir --</option>
                        <option value="Disponible" <?php echo (($_POST['disponibilite'] ?? '') === 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Indisponible" <?php echo (($_POST['disponibilite'] ?? '') === 'Indisponible') ? 'selected' : ''; ?>>Indisponible</option>
                    </select>
                    <div id="err_disponibilite" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
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
                </div>
            </div>

            <div class="field-block">
                <label for="description">DESCRIPTION</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="500"
                    placeholder="Décrivez le service en détail..."
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
                <div id="err_description" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
            </div>

            <div class="field-block">
                <label for="image">IMAGE DU SERVICE</label>

                <label class="upload-box" for="image">
                    <div class="upload-icon">📷</div>
                    <div class="upload-title">Cliquez ou glissez une image ici</div>
                    <div class="upload-subtitle">JPG, PNG, WEBP — max 5 Mo</div>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" hidden>
                </label>

                <div class="file-name" id="fileName">Aucun fichier sélectionné</div>
                <div id="err_image" style="display:none; color:#ff6b6b; font-size:13px; margin-top:8px;"></div>
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
    const descCount = document.getElementById("descCount");

    const goRules = {
    titre: {
        validate: v => /^[A-Za-zÀ-ÿ\s]{3,}$/.test(v.trim()),
        msg: "✕ Le titre doit contenir uniquement des lettres et au moins 3 caractères."
    },

    description: {
        validate: v => /^[A-Za-zÀ-ÿ\s]{5,}$/.test(v.trim()),
        msg: "✕ La description doit contenir uniquement des lettres et au moins 5 caractères."
    },

    categorie: {
        validate: v => v !== "",
        msg: "✕ Veuillez choisir une catégorie."
    },

    prix: {
        validate: v => v.trim() !== "" && !isNaN(v) && Number(v) > 0,
        msg: "✕ Le prix doit être un nombre positif."
    },

    disponibilite: {
        validate: v => v !== "",
        msg: "✕ Veuillez choisir la disponibilité."
    },

    statut: {
        validate: v => v !== "",
        msg: "✕ Veuillez choisir le statut."
    }
};

    function setInvalid(field, errorId, message) {
        field.style.border = "1px solid #ff6b6b";
        field.style.boxShadow = "0 0 0 1px rgba(255,107,107,0.15)";
        const errorBox = document.getElementById(errorId);
        errorBox.textContent = message;
        errorBox.style.display = "block";
    }

    function setValid(field, errorId) {
        field.style.border = "";
        field.style.boxShadow = "";
        const errorBox = document.getElementById(errorId);
        errorBox.textContent = "";
        errorBox.style.display = "none";
    }

    function validateTitre() {
        if (!goRules.titre.validate(titre.value)) {
            setInvalid(titre, "err_titre", goRules.titre.msg);
            return false;
        }
        setValid(titre, "err_titre");
        return true;
    }

    function validateCategorie() {
        if (!goRules.categorie.validate(categorie.value)) {
            setInvalid(categorie, "err_categorie", goRules.categorie.msg);
            return false;
        }
        setValid(categorie, "err_categorie");
        return true;
    }

    function validatePrix() {
        if (!goRules.prix.validate(prix.value)) {
            setInvalid(prix, "err_prix", goRules.prix.msg);
            return false;
        }
        setValid(prix, "err_prix");
        return true;
    }

    function validateDisponibilite() {
        if (!goRules.disponibilite.validate(disponibilite.value)) {
            setInvalid(disponibilite, "err_disponibilite", goRules.disponibilite.msg);
            return false;
        }
        setValid(disponibilite, "err_disponibilite");
        return true;
    }

    function validateStatut() {
        if (!goRules.statut.validate(statut.value)) {
            setInvalid(statut, "err_statut", goRules.statut.msg);
            return false;
        }
        setValid(statut, "err_statut");
        return true;
    }

    function validateDescription() {
        if (!goRules.description.validate(description.value)) {
            setInvalid(description, "err_description", goRules.description.msg);
            return false;
        }
        setValid(description, "err_description");
        return true;
    }

    function validateImage() {
        const errorBox = document.getElementById("err_image");

        if (image.files.length === 0) {
            errorBox.textContent = "";
            errorBox.style.display = "none";
            return true;
        }

        const allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        const file = image.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowed.includes(ext)) {
            errorBox.textContent = "✕ Format non autorisé. Utilisez JPG, PNG, WEBP ou GIF.";
            errorBox.style.display = "block";
            return false;
        }

        if (file.size > 5 * 1024 * 1024) {
            errorBox.textContent = "✕ L'image ne doit pas dépasser 5 Mo.";
            errorBox.style.display = "block";
            return false;
        }

        errorBox.textContent = "";
        errorBox.style.display = "none";
        return true;
    }

    if (description && descCount) {
        const updateCount = () => {
            descCount.textContent = description.value.length;
        };
        description.addEventListener('input', updateCount);
        updateCount();
    }

    if (image && fileName) {
        image.addEventListener('change', function () {
            fileName.textContent = this.files.length ? this.files[0].name : 'Aucun fichier sélectionné';
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