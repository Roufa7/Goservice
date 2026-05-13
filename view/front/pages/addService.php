<?php
require_once __DIR__ . '/../../../controller/CategorieController.php';
require_once __DIR__ . '/../../../view/i18n.php';

$categorieController = new CategorieController();
$categories = $categorieController->listCategories();
?>

<section class="page-hero reveal">
    <span class="section-badge"><?php echo htmlspecialchars(app_text('Service', 'Service', 'خدمة'), ENT_QUOTES, 'UTF-8'); ?></span>
    <h1 class="page-title"><?php echo htmlspecialchars(app_text('Ajouter un service', 'Add a service', 'إضافة خدمة'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="page-intro"><?php echo htmlspecialchars(app_text('Remplissez les informations du nouveau service.', 'Fill in the details of the new service.', 'املأ معلومات الخدمة الجديدة.'), ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<style>
.add-service-wrap {
    width: min(1080px, calc(100% - 36px));
    margin: 0 auto 42px;
}

.add-service-box {
    background: linear-gradient(180deg, rgba(19,40,61,0.97), rgba(13,29,46,0.98));
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 20px 50px rgba(6,18,31,0.24);
}

.add-service-form {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.add-service-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 20px;
}

.add-service-form .field-block {
    gap: 10px;
}

.add-service-form .field-block label {
    color: #f3f7fb;
    font-size: 0.92rem;
    letter-spacing: 0.02em;
    padding-left: 0;
}

.add-service-form input[type="text"],
.add-service-form input[type="email"],
.add-service-form input[type="number"],
.add-service-form select,
.add-service-form textarea {
    width: 100%;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 14px;
    background: rgba(255,255,255,0.05);
    color: #f8fbff;
    padding: 14px 16px;
    font-size: 15px;
    outline: none;
    transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
}

.add-service-form input::placeholder,
.add-service-form textarea::placeholder {
    color: rgba(232,239,246,0.68);
}

.add-service-form input:focus,
.add-service-form select:focus,
.add-service-form textarea:focus {
    border-color: rgba(238,88,40,0.8);
    box-shadow: 0 0 0 3px rgba(238,88,40,0.16);
    background: rgba(255,255,255,0.07);
}

.add-service-form input[disabled] {
    color: rgba(255,255,255,0.62);
    background: rgba(255,255,255,0.08);
}

.add-service-form textarea {
    resize: vertical;
    min-height: 170px;
}

.char-counter {
    margin-top: 8px;
    color: rgba(232,239,246,0.74);
    font-size: 13px;
    text-align: right;
}

.add-service-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.add-service-actions .solid-btn,
.add-service-actions .outline-btn {
    min-width: 170px;
    justify-content: center;
}

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

.field-note {
    color: #8898aa;
    font-size: 13px;
    margin-top: 6px;
}

.field-state-error,
.field-state-ok {
    display: none;
    font-size: 13px;
    margin-top: 8px;
}

.field-state-error {
    color: #ff6b6b;
}

.field-state-ok {
    color: #28a745;
}

@media (max-width: 820px) {
    .add-service-box {
        padding: 22px 18px;
    }

    .add-service-grid {
        grid-template-columns: 1fr;
    }

    .add-service-actions .solid-btn,
    .add-service-actions .outline-btn {
        width: 100%;
    }
}
</style>

<section class="add-service-wrap reveal">
    <div class="add-service-box">
        <form action="index.php?page=saveService" method="POST" enctype="multipart/form-data" class="add-service-form" id="addServiceForm" novalidate>
            <input type="hidden" name="statut" value="En attente">

            <div class="add-service-grid">
                <div class="field-block">
                    <label for="titre"><?php echo htmlspecialchars(app_text('Titre du service', 'Service title', 'عنوان الخدمة'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" id="titre" name="titre" placeholder="<?php echo htmlspecialchars(app_text('Ex : Plombier urgence', 'Ex: Emergency plumber', 'مثال: سباك للطوارئ'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                    <div id="err_titre" class="field-state-error"></div>
                    <div id="ok_titre" class="field-state-ok"></div>
                </div>

                <div class="field-block">
                    <label for="id_categorie"><?php echo htmlspecialchars(app_text('Catégorie', 'Category', 'الفئة'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="id_categorie" name="id_categorie">
                        <option value=""><?php echo htmlspecialchars(app_text('-- Choisir une catégorie --', '-- Choose a category --', '-- اختر فئة --'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['id_categorie']; ?>"><?php echo htmlspecialchars((string) $cat['nom'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="err_categorie" class="field-state-error"></div>
                    <div id="ok_categorie" class="field-state-ok"></div>
                </div>

                <div class="field-block">
                    <label for="prix"><?php echo htmlspecialchars(app_text('Prix (€)', 'Price (€)', 'السعر (€)'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" id="prix" name="prix" placeholder="<?php echo htmlspecialchars(app_text('Ex : 50', 'Ex: 50', 'مثال: 50'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                    <div id="err_prix" class="field-state-error"></div>
                    <div id="ok_prix" class="field-state-ok"></div>
                </div>

                <div class="field-block">
                    <label for="disponibilite"><?php echo htmlspecialchars(app_text('Disponibilité', 'Availability', 'التوفر'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="disponibilite" name="disponibilite">
                        <option value=""><?php echo htmlspecialchars(app_text('-- Choisir --', '-- Choose --', '-- اختر --'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Disponible"><?php echo htmlspecialchars(app_text('Disponible', 'Available', 'متاح'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Indisponible"><?php echo htmlspecialchars(app_text('Indisponible', 'Unavailable', 'غير متاح'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                    <div id="err_disponibilite" class="field-state-error"></div>
                    <div id="ok_disponibilite" class="field-state-ok"></div>
                </div>

                <div class="field-block">
                    <label><?php echo htmlspecialchars(app_text('Statut', 'Status', 'الحالة'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="text" value="<?php echo htmlspecialchars(app_text('En attente', 'Pending', 'قيد الانتظار'), ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    <div class="field-note"><?php echo htmlspecialchars(app_text('Le statut sera défini automatiquement à l’ajout.', 'The status will be assigned automatically when the service is added.', 'سيتم تحديد الحالة تلقائياً عند إضافة الخدمة.'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="field-block">
                <label for="description"><?php echo htmlspecialchars(app_text('Description', 'Description', 'الوصف'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea id="description" name="description" rows="6" placeholder="<?php echo htmlspecialchars(app_text('Décrivez le service en détail...', 'Describe the service in detail...', 'صف الخدمة بالتفصيل...'), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
                <div class="char-counter"><span id="descCount">0</span> / 500</div>
                <div id="err_description" class="field-state-error"></div>
                <div id="ok_description" class="field-state-ok"></div>
            </div>

            <div class="field-block full-width">
                <label for="image"><?php echo htmlspecialchars(app_text('Image du service', 'Service image', 'صورة الخدمة'), ENT_QUOTES, 'UTF-8'); ?></label>

                <label class="upload-box preview-mode" for="image" id="uploadBox">
                    <div class="preview-content" id="previewContent">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <div class="upload-icon">📷</div>
                            <div class="preview-file-name"><?php echo htmlspecialchars(app_text('Cliquez ou glissez une image ici', 'Click or drop an image here', 'انقر أو اسحب صورة هنا'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="field-note"><?php echo htmlspecialchars(app_text('JPG, PNG, WEBP — max 5 Mo', 'JPG, PNG, WEBP — max 5 MB', '‏JPG وPNG وWEBP — الحد الأقصى 5 ميغابايت'), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>

                        <div class="image-preview-wrapper" id="imagePreviewWrapper" style="display:none;">
                            <div class="preview-file-name" id="fileName"><?php echo htmlspecialchars(app_text('Aucun fichier sélectionné', 'No file selected', 'لم يتم اختيار ملف'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <img id="imagePreview" src="" alt="<?php echo htmlspecialchars(app_text('Aperçu image du service', 'Service image preview', 'معاينة صورة الخدمة'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif" hidden>
                </label>

                <div id="err_image" class="field-state-error"></div>
                <div id="ok_image" class="field-state-ok"></div>
            </div>

            <div class="field-block full-width">
                <label>📍 <?php echo htmlspecialchars(app_text('Adresse du prestataire', 'Provider address', 'عنوان مقدم الخدمة'), ENT_QUOTES, 'UTF-8'); ?>
                    <span style="font-size:11px;color:var(--muted);font-weight:400;"><?php echo htmlspecialchars(app_text('— optionnel, pour apparaître sur la carte', '— optional, to appear on the map', '— اختياري، للظهور على الخريطة'), ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
                <div style="display:flex;gap:10px;align-items:center;">
                    <input type="text" id="adresse" name="adresse" placeholder="<?php echo htmlspecialchars(app_text('Ex : Avenue Habib Bourguiba, Tunis, Tunisie', 'Ex: Habib Bourguiba Avenue, Tunis, Tunisia', 'مثال: شارع الحبيب بورقيبة، تونس، تونس'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" style="flex:1;">
                    <button type="button" id="btnPreviewMap" style="background:linear-gradient(135deg,#ee5828,#c94718);color:#fff;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;font-weight:700;font-size:12px;white-space:nowrap;height:42px;">
                        <?php echo htmlspecialchars(app_text('🗺️ Prévisualiser', '🗺️ Preview', '🗺️ معاينة'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
                <div id="miniMapWrap" style="display:none;margin-top:12px;border-radius:14px;overflow:hidden;height:200px;border:2px solid #ee5828;">
                    <div id="miniMap" style="width:100%;height:100%;"></div>
                </div>
            </div>

            <div class="add-service-actions">
                <button type="submit" class="solid-btn"><?php echo htmlspecialchars(app_text('✓ Enregistrer', '✓ Save', '✓ حفظ'), ENT_QUOTES, 'UTF-8'); ?></button>
                <a href="index.php?page=services" class="outline-btn"><?php echo htmlspecialchars(app_text('Annuler', 'Cancel', 'إلغاء'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const description = document.getElementById('description');
    const descCount = document.getElementById('descCount');
    const image = document.getElementById('image');
    const fileName = document.getElementById('fileName');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewWrapper = document.getElementById('imagePreviewWrapper');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    if (description && descCount) {
        const syncCount = () => {
            descCount.textContent = String(description.value.length);
        };
        description.addEventListener('input', syncCount);
        syncCount();
    }

    if (image && fileName && imagePreview && imagePreviewWrapper && uploadPlaceholder) {
        image.addEventListener('change', function () {
            const file = image.files && image.files[0];
            if (!file) {
                return;
            }

            fileName.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function (event) {
                imagePreview.src = String(event.target?.result || '');
                imagePreviewWrapper.style.display = 'flex';
                uploadPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>
