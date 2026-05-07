<?php
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: index.php?page=login&error=' . urlencode('Jeton manquant.'));
    exit;
}
?>

<section class="page-hero reveal">
    <span class="section-badge">Sécurité</span>
    <h1 class="page-title">Nouveau mot de passe</h1>
    <p class="page-intro">Définissez votre nouveau mot de passe sécurisé.</p>
</section>

<section class="section reveal">
    <article class="panel auth-card">
        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" action="../../controller/AuthController.php?action=reset_password" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-grid">
                <div class="field-block">
                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>

                <div class="field-block">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
            </div>

            <div class="icon-actions" style="margin-top: 20px;">
                <button type="submit" class="solid-btn">Changer mon mot de passe</button>
            </div>
        </form>
    </article>
</section>
