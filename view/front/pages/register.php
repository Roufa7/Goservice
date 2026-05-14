<?php require_once __DIR__ . '/../auth_captcha.php'; ?>

<section class="page-hero reveal">
    <span class="section-badge">Inscription</span>
    <h1 class="page-title">Créer un compte</h1>
    <p class="page-intro">Créez votre compte GoService en quelques étapes.</p>
</section>

<section class="section reveal">
    <article class="panel auth-card">
        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <form class="auth-form" action="../../controller/AuthController.php?action=register" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="field-block">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required>
                </div>

                <div class="field-block">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>

                <div class="field-block">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="field-block">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-strength-meter">
                        <div class="strength-bar" id="strength-bar"></div>
                    </div>
                    <small id="strength-text" class="strength-text">Niveau de sécurité</small>
                </div>

                <div class="field-block">
                    <label for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone">
                </div>

                <div class="field-block">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse">
                </div>

                <div class="field-block">
                    <label for="photo">Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>

                <div class="field-block">
                    <label for="role">Rôle</label>
                    <select id="role" name="role" required>
                        <option value="user">Client</option>
                        <option value="provider">Provider</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="field-block field-span-2">
                    <label for="captcha">Captcha</label>
                    <div class="auth-captcha-box"><?php echo htmlspecialchars(authCaptchaCode(), ENT_QUOTES, 'UTF-8'); ?></div>
                    <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Recopiez le code affiché">
                </div>
            </div>

            <div class="icon-actions" style="margin-top: 20px;">
                <button type="submit" class="solid-btn">S’inscrire</button>
            </div>
        </form>
    </article>
</section>

<style>
.password-strength-meter {
    height: 6px;
    background-color: rgba(100, 100, 100, 0.2);
    border-radius: 4px;
    margin-top: 8px;
    overflow: hidden;
    position: relative;
}
.strength-bar {
    height: 100%;
    width: 0%;
    border-radius: 4px;
    transition: width 0.4s ease-out, background-color 0.4s ease-out;
}
.strength-text {
    display: block;
    margin-top: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #777;
    transition: color 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', () => {
            const value = passwordInput.value;
            let score = 0;

            if (!value) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = 'transparent';
                strengthText.textContent = 'Niveau de sécurité';
                strengthText.style.color = '#777';
                return;
            }

            // Check length
            if (value.length >= 8) score++;
            // Check for both lower and upper case
            if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
            // Check for numbers
            if (/\d/.test(value)) score++;
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(value)) score++;

            let width = '0%';
            let color = 'transparent';
            let text = '';
            let textColor = '#777';

            if (score <= 1) {
                width = '33%';
                color = '#ff4757'; // Rouge - Faible
                text = 'Faible';
                textColor = '#ff4757';
            } else if (score === 2 || score === 3) {
                width = '66%';
                color = '#ffa502'; // Orange - Moyen
                text = 'Moyen';
                textColor = '#ffa502';
            } else if (score >= 4) {
                width = '100%';
                color = '#2ed573'; // Vert - Fort
                text = 'Fort';
                textColor = '#2ed573';
            }

            strengthBar.style.width = width;
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = textColor;
        });
    }
});
</script>