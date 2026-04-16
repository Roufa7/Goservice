<?php
require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$usersList = $userModel->getAllUsers();

$totalUsers = count($usersList);
$totalProviders = count(array_filter($usersList, fn($u) => $u['role'] === 'provider'));
$totalAdmins = count(array_filter($usersList, fn($u) => $u['role'] === 'admin'));
?>
<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un utilisateur, email ou provider...">
        <select>
            <option>Tous les rôles</option>
            <option>Utilisateur</option>
            <option>Provider</option>
            <option>Admin</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Nom</option>
            <option>Email</option>
            <option>Rôle</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong><?php echo $totalUsers; ?></strong><span>Total Inscrits</span></article>
    <article class="admin-stat"><strong><?php echo $totalUsers - $totalProviders - $totalAdmins; ?></strong><span>Clients</span></article>
    <article class="admin-stat"><strong><?php echo $totalProviders; ?></strong><span>Providers</span></article>
    <article class="admin-stat"><strong><?php echo $totalAdmins; ?></strong><span>Admins</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des utilisateurs</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usersList as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['nom']); ?></td>
                    <td><?php echo htmlspecialchars($u['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($u['role'])); ?></td>
                    <td><?php echo htmlspecialchars($u['telephone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($u['adresse'] ?? ''); ?></td>
                    <td class="admin-tools">
                        <a href="index.php?page=user_edit&id=<?php echo $u['id_user']; ?>" class="small-btn" style="text-decoration:none;">Modifier</a>
                        <?php if($u['id_user'] !== ($_SESSION['user_id'] ?? null)): ?>
                        <a href="../../controller/UserController.php?action=delete&id=<?php echo $u['id_user']; ?>" class="danger-btn" style="text-decoration:none;" onclick="return confirm('Êtes-vous sûr ?');">Supprimer</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Profils providers</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Provider</th>
                <th>Spécialité</th>
                <th>Description</th>
                <th>Disponibilité</th>
                <th>Portfolio</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sarra Jaziri</td>
                <td>Design graphique</td>
                <td>Création visuelle et identité de marque</td>
                <td>Disponible</td>
                <td>4 éléments</td>
                <td class="admin-tools">
                    <button class="small-btn">Consulter</button>
                    <button class="small-btn">Modifier</button>
                </td>
            </tr>
            <tr>
                <td>Ahmed Ben Salah</td>
                <td>Développement web</td>
                <td>Sites vitrines et solutions digitales</td>
                <td>Indisponible</td>
                <td>2 éléments</td>
                <td class="admin-tools">
                    <button class="small-btn">Consulter</button>
                    <button class="small-btn">Modifier</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Actions administratives</span>
    <div class="feature-list">
        <div class="feature-item">Gérer tous les utilisateurs</div>
        <div class="feature-item">Consulter les profils providers</div>
        <div class="feature-item">Modérer le contenu</div>
        <div class="feature-item">Suivre les comptes par rôle</div>
    </div>
</section>