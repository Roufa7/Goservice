<?php
require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID invalide";
    exit;
}

$u = $userModel->getUserById($id);
if (!$u) {
    echo "Utilisateur introuvable";
    exit;
}
?>
<section class="admin-panel reveal">
    <span class="section-badge">Modifier Utilisateur</span>
    
    <div style="max-width: 600px; padding: 20px;">
        <form action="../../controller/UserController.php?action=update" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($u['id_user']); ?>">
            
            <div style="display: flex; flex-direction: column;">
                <label>Nom</label>
                <input type="text" name="nom" value="<?php echo htmlspecialchars($u['nom']); ?>" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div style="display: flex; flex-direction: column;">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?php echo htmlspecialchars($u['prenom']); ?>" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="display: flex; flex-direction: column;">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="display: flex; flex-direction: column;">
                <label>Rôle</label>
                <select name="role" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                    <option value="provider" <?php echo $u['role'] === 'provider' ? 'selected' : ''; ?>>Provider</option>
                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="solid-btn">Enregistrer</button>
                <a href="index.php?page=users" class="ghost-btn">Annuler</a>
            </div>
        </form>
    </div>
</section>
