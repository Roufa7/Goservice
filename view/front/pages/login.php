<?php require_once __DIR__ . '/../auth_captcha.php'; ?>

<section class="page-hero reveal">
    <span class="section-badge">Connexion</span>
    <h1 class="page-title">Se connecter</h1>
    <p class="page-intro">Accédez à votre compte GoService.</p>
</section>

<section class="section reveal">
    <article class="panel auth-card">
        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div style="color: green; margin-bottom: 15px;">
                <?php 
                    if ($_GET['success'] === 'password_reset') echo "Votre mot de passe a été mis à jour avec succès.";
                    else if ($_GET['success'] === 'registered') echo "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    else echo "Opération réussie.";
                ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" action="../../controller/AuthController.php?action=login" method="POST">
            <div class="form-grid">
                <div class="field-block">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="field-block">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="field-block">
                    <label for="captcha">Captcha</label>
                    <div class="auth-captcha-box"><?php echo htmlspecialchars(authCaptchaCode(), ENT_QUOTES, 'UTF-8'); ?></div>
                    <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Recopiez le code">
                </div>

            <div class="icon-actions" style="margin-top: 20px;">
                <button type="submit" class="solid-btn">Se connecter</button>
            </div>

            <div style="margin-top: 15px; text-align: center;">
                <p>Pas encore de compte ? <a href="index.php?page=register">S'inscrire</a></p>
                <p><a href="index.php?page=forgot_password" style="font-size: 0.9em; opacity: 0.8;">Mot de passe oublié ?</a></p>
            </div>
        </form>
    </article>
</section>