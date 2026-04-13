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

<section class="add-service-wrap reveal">
    <div class="add-service-box">
        <form action="index.php?page=saveService" method="POST" enctype="multipart/form-data" class="add-service-form" id="addServiceForm">

            <div class="add-service-grid">
                <div class="field-block">
                    <label for="titre">TITRE DU SERVICE</label>
                    <input type="text" id="titre" name="titre" placeholder="Ex : Plombier urgence" required>
                </div>

                <div class="field-block">
                    <label for="id_categorie">CATÉGORIE</label>
                    <select id="id_categorie" name="id_categorie" required>
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id_categorie']; ?>">
                                <?php echo htmlspecialchars($cat['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-block">
                    <label for="prix">PRIX (€)</label>
                    <input type="number" step="0.01" min="0" id="prix" name="prix" placeholder="Ex : 50" required>
                </div>

                <div class="field-block">
                    <label for="disponibilite">DISPONIBILITÉ</label>
                    <select id="disponibilite" name="disponibilite" required>
                        <option value="">-- Choisir --</option>
                        <option value="1">Disponible</option>
                        <option value="0">Indisponible</option>
                    </select>
                </div>

                <div class="field-block">
                    <label for="statut">STATUT</label>
                    <select id="statut" name="statut" required>
                        <option value="">-- Choisir --</option>
                        <option value="Actif">Actif</option>
                        <option value="En attente">En attente</option>
                        <option value="Suspendu">Suspendu</option>
                    </select>
                </div>
            </div>

            <div class="field-block">
                <label for="description">DESCRIPTION</label>
                <textarea id="description" name="description" rows="6" maxlength="500" placeholder="Décrivez le service en détail..." required></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
            </div>

            <div class="field-block">
                <label for="image">IMAGE DU SERVICE</label>
                <label class="upload-box" for="image">
                    <div class="upload-icon">📷</div>
                    <div class="upload-title">Cliquez ou glissez une image ici</div>
                    <div class="upload-subtitle">JPG, PNG, WEBP — max 5 Mo</div>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" hidden required>
                </label>
                <div class="file-name" id="fileName">Aucun fichier sélectionné</div>
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
    const desc = document.getElementById('description');
    const descCount = document.getElementById('descCount');
    const imageInput = document.getElementById('image');
    const fileName = document.getElementById('fileName');

    if (desc && descCount) {
        const updateCount = () => {
            descCount.textContent = desc.value.length;
        };
        desc.addEventListener('input', updateCount);
        updateCount();
    }

    if (imageInput && fileName) {
        imageInput.addEventListener('change', function () {
            fileName.textContent = this.files.length ? this.files[0].name : 'Aucun fichier sélectionné';
        });
    }
});
</script>