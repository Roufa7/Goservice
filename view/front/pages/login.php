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
        
        <form class="auth-form" action="../../../controller/AuthController.php?action=login" method="POST">
            <div class="form-grid">
                <div class="field-block">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="field-block">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div class="icon-actions" style="margin-top: 20px;">
                <button type="submit" class="solid-btn">Se connecter</button>
            </div>
            
            <div style="margin-top: 15px; text-align: center;">
                <p>Pas encore de compte ? <a href="index.php?page=register">S'inscrire</a></p>
            </div>
        </form>
    </article>
</section>
