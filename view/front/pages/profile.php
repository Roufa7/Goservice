<?php
if (!isset($_SESSION['user_id'])) {
    echo "<section class='page-hero reveal'><h2>" . htmlspecialchars(app_text('Veuillez vous connecter pour voir votre profil.', 'Please log in to view your profile.', 'يرجى تسجيل الدخول لعرض ملفك الشخصي.'), ENT_QUOTES, 'UTF-8') . "</h2></section>";
    return;
}

require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$userData = $userModel->getUserById($_SESSION['user_id']);

if (!$userData) {
    echo "<section class='page-hero reveal'><h2>" . htmlspecialchars(app_text('Profil introuvable.', 'Profile not found.', 'الملف الشخصي غير موجود.'), ENT_QUOTES, 'UTF-8') . "</h2></section>";
    return;
}

$fullName = trim((string) (($userData['prenom'] ?? '') . ' ' . ($userData['nom'] ?? '')));
$profilePhoto = trim((string) ($userData['photo'] ?? ''));
$profilePhotoUrl = '';
if ($profilePhoto !== '') {
    $profilePhotoUrl = preg_match('~^https?://~i', $profilePhoto) ? $profilePhoto : '../../' . ltrim($profilePhoto, './');
}
$profileStats = $userModel->getProfileStats($_SESSION['user_id']);
$placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"><rect width="300" height="300" rx="150" fill="#edf0f3"/><circle cx="150" cy="108" r="60" fill="#b2b8bf"/><path d="M58 266c11-46 49-78 92-78s81 32 92 78" fill="#b2b8bf"/></svg>';
$placeholderAvatar = 'data:image/svg+xml;utf8,' . rawurlencode($placeholderSvg);
?>

<section class="page-hero reveal">
    <span class="section-badge"><?php echo htmlspecialchars(app_text('Profil', 'Profile', 'الملف الشخصي'), ENT_QUOTES, 'UTF-8'); ?></span>
    <h1 class="page-title"><?php echo htmlspecialchars(app_text('Votre espace personnel', 'Your personal space', 'مساحتك الشخصية'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="page-intro"><?php echo htmlspecialchars(app_text('Gérez vos informations, votre photo et vos préférences de compte depuis un seul endroit.', 'Manage your information, photo and account preferences from one place.', 'أدر معلوماتك وصورتك وتفضيلات حسابك من مكان واحد.'), ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<section class="profile-wrap reveal">
    <div class="profile-top">
        <article class="profile-card main">
            <?php if (isset($_GET['success'])): ?>
                <div class="app-flash app-flash-success"><?php echo htmlspecialchars(app_text('Profil mis à jour avec succès.', 'Profile updated successfully.', 'تم تحديث الملف الشخصي بنجاح.'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-box"><?php echo htmlspecialchars(app_text('Une erreur est survenue pendant la mise à jour.', 'An error occurred while updating the profile.', 'حدث خطأ أثناء تحديث الملف الشخصي.'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form profile-edit-form" action="../../controller/ProfileController.php?action=update" method="POST" enctype="multipart/form-data">
                <div class="profile-photo-card">
                    <div class="profile-photo-shell" id="profilePhotoShell">
                        <img
                            src="<?php echo htmlspecialchars($profilePhotoUrl !== '' ? $profilePhotoUrl : $placeholderAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars(app_text('Photo de profil', 'Profile photo', 'الصورة الشخصية'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="profile-photo-image <?php echo $profilePhotoUrl === '' ? 'is-placeholder' : ''; ?>"
                            id="profilePhotoPreview"
                        >
                        <label for="profile_photo" class="profile-photo-trigger" title="<?php echo htmlspecialchars(app_text('Changer la photo', 'Change photo', 'تغيير الصورة'), ENT_QUOTES, 'UTF-8'); ?>">
                            <span aria-hidden="true">📷</span>
                        </label>
                    </div>
                    <div class="profile-photo-copy">
                        <h2><?php echo htmlspecialchars($fullName !== '' ? $fullName : app_text('Utilisateur', 'User', 'مستخدم'), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars(app_text('Ajoutez une photo claire pour personnaliser votre profil. Survolez l’avatar pour faire apparaître l’appareil photo.', 'Add a clear photo to personalize your profile. Hover over the avatar to reveal the camera control.', 'أضف صورة واضحة لتخصيص ملفك الشخصي. مرّر فوق الصورة لإظهار زر الكاميرا.'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <span class="profile-role-chip"><?php echo htmlspecialchars(app_text('Rôle', 'Role', 'الدور') . ' : ' . ucfirst((string) ($userData['role'] ?? 'user')), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <input type="file" id="profile_photo" name="photo" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                </div>

                <div class="form-grid">
                    <div class="field-block">
                        <label><?php echo htmlspecialchars(app_text('Nom', 'Last name', 'اللقب'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars((string) ($userData['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="field-block">
                        <label><?php echo htmlspecialchars(app_text('Prénom', 'First name', 'الاسم'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars((string) ($userData['prenom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="field-block">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars((string) ($userData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="field-block">
                        <label><?php echo htmlspecialchars(app_text('Téléphone', 'Phone', 'الهاتف'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="telephone" value="<?php echo htmlspecialchars((string) ($userData['telephone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field-block" style="grid-column: 1 / -1;">
                        <label><?php echo htmlspecialchars(app_text('Adresse', 'Address', 'العنوان'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" name="adresse" value="<?php echo htmlspecialchars((string) ($userData['adresse'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="icon-actions" style="margin-top:20px;">
                    <button type="submit" class="solid-btn"><?php echo htmlspecialchars(app_text('Mettre à jour le profil', 'Update profile', 'تحديث الملف الشخصي'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
            </form>

            <form action="../../controller/ProfileController.php?action=delete" method="POST" onsubmit="return confirm('<?php echo htmlspecialchars(app_text('Attention ! Voulez-vous vraiment supprimer définitivement votre compte ?', 'Warning! Do you really want to permanently delete your account?', 'تحذير! هل تريد حقاً حذف حسابك نهائياً؟'), ENT_QUOTES, 'UTF-8'); ?>');" style="margin-top:15px;">
                <button type="submit" class="danger-btn"><?php echo htmlspecialchars(app_text('Supprimer mon compte', 'Delete my account', 'حذف حسابي'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </article>

        <div>
            <div class="profile-stats">
                <article class="stat-card"><strong class="stat-animate" data-target="<?php echo (int) ($profileStats['services_count'] ?? 0); ?>">0</strong><span><?php echo htmlspecialchars(app_text('Services publiés', 'Published services', 'الخدمات المنشورة'), ENT_QUOTES, 'UTF-8'); ?></span></article>
                <article class="stat-card"><strong class="stat-animate" data-target="<?php echo (int) ($profileStats['offers_count'] ?? 0); ?>">0</strong><span><?php echo htmlspecialchars(app_text('Candidatures', 'Applications', 'الترشحات'), ENT_QUOTES, 'UTF-8'); ?></span></article>
                <article class="stat-card"><strong class="stat-animate" data-target="0">0</strong><span><?php echo htmlspecialchars(app_text('Demandes actives', 'Active requests', 'الطلبات النشطة'), ENT_QUOTES, 'UTF-8'); ?></span></article>
                <article class="stat-card"><strong class="stat-animate" data-target="5">0</strong><span><?php echo htmlspecialchars(app_text('Note moyenne', 'Average rating', 'متوسط التقييم'), ENT_QUOTES, 'UTF-8'); ?></span></article>
            </div>

            <div class="profile-grid" style="margin-top:20px;">
                <article class="profile-card" style="border:1px solid var(--orange);">
                    <span class="section-badge"><?php echo htmlspecialchars(app_text('Biométrie', 'Biometrics', 'القياسات الحيوية'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3>Face ID</h3>
                    <p><?php echo htmlspecialchars(app_text('Sécurisez votre accès avec la reconnaissance faciale.', 'Secure your access with facial recognition.', 'أمّن وصولك باستخدام التعرف على الوجه.'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <button id="btnEnrollFace" type="button" class="solid-btn"><?php echo htmlspecialchars(app_text('Enregistrer mon visage', 'Register my face', 'تسجيل وجهي'), ENT_QUOTES, 'UTF-8'); ?></button>
                    <div id="enroll-feedback" style="margin-top:10px;font-weight:bold;"></div><div id="faceEnrollHost"></div>
                </article>

                <article class="profile-card">
                    <span class="section-badge"><?php echo htmlspecialchars(app_text('À propos', 'About', 'نبذة'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><?php echo htmlspecialchars(app_text('Présentation', 'Presentation', 'تقديم'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars(app_text('Complétez votre identité visuelle et vos informations pour inspirer davantage confiance.', 'Complete your visual identity and information to build more trust.', 'أكمل هويتك البصرية ومعلوماتك لبناء مزيد من الثقة.'), ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            </div>
        </div>
    </div>
</section>

<style>
.profile-photo-card{display:grid;grid-template-columns:180px 1fr;gap:22px;align-items:center;margin-bottom:26px;padding:20px;border-radius:24px;background:rgba(255,255,255,0.05);border:1px solid var(--line);} 
.profile-photo-shell{position:relative;width:180px;height:180px;border-radius:50%;overflow:hidden;box-shadow:0 18px 38px rgba(20,39,56,0.18);background:#e9edf2;} 
.profile-photo-image{width:100%;height:100%;display:block;object-fit:cover;} 
.profile-photo-image.is-placeholder{background:#eef1f4;} 
.profile-photo-trigger{position:absolute;right:12px;bottom:12px;width:46px;height:46px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid rgba(20,39,56,0.14);box-shadow:0 12px 24px rgba(20,39,56,0.18);cursor:pointer;font-size:1.1rem;opacity:0;transform:translateY(8px);transition:.2s ease;} 
.profile-photo-shell:hover .profile-photo-trigger,.profile-photo-shell:focus-within .profile-photo-trigger{opacity:1;transform:translateY(0);} 
.profile-photo-copy h2{margin-bottom:8px;} 
.profile-photo-copy p{margin:0 0 12px;} 
.profile-role-chip{display:inline-flex;align-items:center;min-height:38px;padding:0 14px;border-radius:999px;background:rgba(238,88,40,0.10);color:var(--orange);font-weight:800;border:1px solid rgba(238,88,40,0.16);} 
.error-box{margin-bottom:16px;padding:14px 16px;border-radius:18px;background:rgba(238,88,40,0.12);border:1px solid rgba(238,88,40,0.18);color:#c9471d;font-weight:700;} 
body.dark .profile-photo-trigger{background:#10202f;color:#fff;border-color:rgba(255,255,255,0.12);} 
@media (max-width:900px){.profile-photo-card{grid-template-columns:1fr;text-align:center;justify-items:center;}.profile-photo-copy{text-align:center;}} 
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('profilePhotoPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (event) {
            const src = event.target && event.target.result ? String(event.target.result) : '';
            if (!src) return;
            preview.src = src;
            preview.classList.remove('is-placeholder');
        };
        reader.readAsDataURL(file);
    });
});
</script>
