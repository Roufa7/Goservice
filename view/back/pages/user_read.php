<?php
require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$id = $_GET['id'] ?? null;
$user = $id ? $userModel->getUserById($id) : null;

if (!$user) {
    echo "<p>Utilisateur non trouvé.</p>";
    exit;
}
?>
<section class="page-hero reveal">
    <span class="section-badge">Profil Utilisateur</span>
    <h1 class="page-title">Détails de <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h1>
    <p class="page-intro">Consultation des informations du compte en lecture seule.</p>
</section>

<section class="section reveal">
    <article class="panel">
        <div style="display: flex; flex-direction: column; gap: 15px; font-size: 1.1rem;">
            <p><strong>ID :</strong> <?php echo htmlspecialchars($user['id_user']); ?></p>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($user['prenom']); ?></p>
            <p><strong>Email :</strong> <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></a></p>
            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($user['telephone'] ?? 'Non renseigné'); ?></p>
            <p><strong>Adresse :</strong> <?php echo htmlspecialchars($user['adresse'] ?? 'Non renseignée'); ?></p>
            <p><strong>Rôle :</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
        </div>

        <div class="icon-actions" style="margin-top: 30px;">
            <a href="index.php?page=user_edit&id=<?php echo $user['id_user']; ?>" class="solid-btn" style="text-decoration:none;">Modifier</a>
            <a href="index.php?page=users" class="outline-btn" style="text-decoration:none;">Retour</a>
        </div>
    </article>
</section>
