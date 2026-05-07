<section class="page-hero reveal">
    <span class="section-badge">Sécurité</span>
    <h1 class="page-title">Mot de passe oublié</h1>
    <p class="page-intro">Entrez votre email pour recevoir un lien de réinitialisation.</p>
</section>

<section class="section reveal">
    <article class="panel auth-card">
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
            <div style="color: green; margin-bottom: 20px; padding: 15px; background: rgba(0, 255, 0, 0.1); border-radius: 5px;">
                Si un compte existe pour cet email, un lien de réinitialisation vous sera envoyé d'ici quelques instants.
            </div>

        <?php else: ?>
            <form class="auth-form" action="../../controller/AuthController.php?action=forgot_password" method="POST">
                <div class="form-grid">
                    <div class="field-block">
                        <label for="email">Email de votre compte</label>
                        <input type="email" id="email" name="email" required placeholder="votre-email@exemple.com">
                    </div>
                </div>

                <div class="icon-actions" style="margin-top: 20px;">
                    <button type="submit" class="solid-btn">Envoyer le lien</button>
                    <a href="index.php?page=login" class="outline-btn">Retour</a>
                </div>
            </form>
        <?php endif; ?>
    </article>
</section>
