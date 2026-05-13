<?php
require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();

// Handle search and sort
$searchQuery = $_GET['search'] ?? '';
$searchRole = $_GET['role_filter'] ?? 'all';
$sortBy = $_GET['sort_by'] ?? 'id_user';

$usersList = $userModel->searchUsers($searchQuery, $searchRole, $sortBy);

if (isset($_GET['export_users'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="goservice-users.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nom', 'Prenom', 'Email', 'Role', 'Telephone']);
    foreach (($usersList ?? []) as $uRow) {
        fputcsv($out, [
            $uRow['id_user'] ?? '',
            $uRow['nom'] ?? '',
            $uRow['prenom'] ?? '',
            $uRow['email'] ?? '',
            $uRow['role'] ?? '',
            $uRow['telephone'] ?? ($uRow['phone'] ?? '')
        ]);
    }
    fclose($out);
    exit;
}
$stats = $userModel->getUserStats();

$totalUsers = $stats['total'] ?? 0;
$totalProviders = $stats['provider'] ?? 0;
$totalAdmins = $stats['admin'] ?? 0;
$totalClients = $stats['user'] ?? 0;
?>
<section class="action-bar reveal">
    <form action="index.php" method="GET" class="search-box" style="display: flex; flex: 1; gap: 10px;">
        <input type="hidden" name="page" value="users">
        <input type="text" name="search" placeholder="Rechercher un utilisateur, email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        <select name="role_filter" onchange="this.form.submit()">
            <option value="all" <?php echo $searchRole === 'all' ? 'selected' : ''; ?>>Tous les rôles</option>
            <option value="user" <?php echo $searchRole === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
            <option value="provider" <?php echo $searchRole === 'provider' ? 'selected' : ''; ?>>Provider</option>
            <option value="admin" <?php echo $searchRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
        <select name="sort_by" onchange="this.form.submit()">
            <option value="id_user" <?php echo $sortBy === 'id_user' ? 'selected' : ''; ?>>Trier par ID</option>
            <option value="nom" <?php echo $sortBy === 'nom' ? 'selected' : ''; ?>>Trier par Nom</option>
            <option value="email" <?php echo $sortBy === 'email' ? 'selected' : ''; ?>>Trier par Email</option>
            <option value="role" <?php echo $sortBy === 'role' ? 'selected' : ''; ?>>Trier par Rôle</option>
        </select>
        <button type="submit" class="small-btn">Filtrer</button>
    </form>

    <div class="export-bar" style="display: flex; gap: 10px;">
        <a href="index.php?page=user_add" class="solid-btn" style="text-decoration:none;">Ajouter un utilisateur</a>
        <a class="outline-btn" style="text-decoration:none;" href="index.php?page=users&amp;search=<?php echo urlencode($searchQuery); ?>&amp;role_filter=<?php echo urlencode($searchRole); ?>&amp;sort_by=<?php echo urlencode($sortBy); ?>&amp;export_users=csv">Exporter</a>
    </div>
</section>

<div class="admin-grid reveal" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Cercle de répartition (Doughnut) -->
    <article class="admin-panel glass-panel card-float">
        <span class="section-badge">Répartition</span>
        <h3 class="grad-text">Rôles Utilisateurs</h3>
        <div style="height: 250px; position: relative; margin-top: 20px;">
            <canvas id="userRoleCircle"></canvas>
        </div>
    </article>

    <!-- Courbe d'activité (Line) -->
    <article class="admin-panel glass-panel card-float" style="animation-delay: 0.2s">
        <span class="section-badge">Tendance</span>
        <h3 class="grad-text">Inscriptions</h3>
        <div style="height: 250px; position: relative; margin-top: 20px;">
            <canvas id="userTrendCurve"></canvas>
        </div>
    </article>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Cercle (Doughnut)
    const ctxCircle = document.getElementById('userRoleCircle').getContext('2d');
    new Chart(ctxCircle, {
        type: 'doughnut',
        data: {
            labels: ['Clients', 'Providers', 'Admins'],
            datasets: [{
                data: [<?php echo $totalClients; ?>, <?php echo $totalProviders; ?>, <?php echo $totalAdmins; ?>],
                backgroundColor: ['#EE5828', '#4CAF50', '#6c5ce7'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#888', usePointStyle: true } }
            },
            animation: {
                animateRotate: true,
                duration: 2500,
                easing: 'easeOutQuart'
            }
        }
    });

    // 2. Courbe (Line)
    const ctxCurve = document.getElementById('userTrendCurve').getContext('2d');
    const userTrends = <?php echo json_encode($userModel->getRegistrationTrends()); ?>;
    const labels = userTrends.map(t => t.date);
    const counts = userTrends.map(t => t.count);

    new Chart(ctxCurve, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Inscriptions',
                data: counts,
                borderColor: '#EE5828',
                borderWidth: 3,
                tension: 0, // Style zigzag
                pointRadius: 4,
                backgroundColor: 'rgba(238, 88, 40, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { stepSize: 1, color: '#888' } },
                x: { grid: { display: false }, ticks: { color: '#888' } }
            },
            animation: {
                y: { duration: 2000, from: 500 }
            }
        }
    });
});
</script>

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
                        <a href="index.php?page=user_read&id=<?php echo $u['id_user']; ?>" class="small-btn" style="text-decoration:none;">Consulter</a>
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
    <span class="section-badge">Profils providers réels</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Provider</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $realProviders = $userModel->getAllProviders();
            foreach ($realProviders as $p): 
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?></td>
                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                    <td><?php echo htmlspecialchars($p['telephone'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p['adresse'] ?? '-'); ?></td>
                    <td>
                        <span class="status-badge <?php echo ($p['disponibilite'] == 1) ? 'status-done' : 'status-pending'; ?>">
                            <?php echo ($p['disponibilite'] == 1) ? 'Disponible' : 'Indisponible'; ?>
                        </span>
                    </td>
                    <td class="admin-tools">
                        <a href="index.php?page=user_read&id=<?php echo $p['id_user']; ?>" class="small-btn" style="text-decoration:none;">Consulter</a>
                        <a href="index.php?page=user_edit&id=<?php echo $p['id_user']; ?>" class="small-btn" style="text-decoration:none;">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($realProviders)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Aucun provider trouvé.</td>
                </tr>
            <?php endif; ?>
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