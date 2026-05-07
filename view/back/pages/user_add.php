<section class="page-hero reveal">
    <span class="section-badge">Gestion des utilisateurs</span>
    <h1 class="page-title">Ajouter un utilisateur</h1>
    <p class="page-intro">Créez un nouveau compte depuis le panneau d'administration.</p>
</section>

<section class="section reveal">
    <article class="panel auth-card">
        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; margin-bottom: 15px;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="../../controller/UserController.php?action=add" method="POST">
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
                    <label for="role">Rôle</label>
                    <select id="role" name="role" required>
                        <option value="user">Client</option>
                        <option value="provider">Provider</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="icon-actions" style="margin-top: 20px; display: flex; gap: 15px; justify-content: flex-start;">
                <button type="submit" class="solid-btn">Créer l'utilisateur</button>
                <a href="index.php?page=users" class="outline-btn" style="text-decoration:none;">Annuler</a>
            </div>
        </form>
    </article>
</section>
