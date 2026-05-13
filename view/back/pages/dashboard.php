<style>
.admin-stats .admin-stat strong{color:#ffffff!important;text-shadow:none!important;mix-blend-mode:normal!important;}
.admin-stats .admin-stat span,.admin-panel p,.module-table td,.module-table th{color:#c9d7e4!important;}
.admin-panel h3,.module-table tbody td:first-child{color:#ffffff!important;}
body.light .admin-stat,body.light .admin-panel,body.light .table-panel{background:#ffffff!important;color:#142738!important;border-color:rgba(20,39,56,.08)!important;}
body.light .admin-stats .admin-stat strong,body.light .admin-panel h3,body.light .module-table tbody td:first-child{color:#142738!important;} .dashboard-stat-value{display:block;color:#ffffff!important;text-shadow:none!important;mix-blend-mode:normal!important;} body.light .dashboard-stat-value{color:#142738!important;}
body.light .admin-stats .admin-stat span,body.light .admin-panel p,body.light .module-table td,body.light .module-table th{color:#5d7085!important;}
</style>
<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/User.php';

$userModel = new User();
$stats = $userModel->getUserStats();
$trends = $userModel->getRegistrationTrends();
$db = config::getConnexion();

$labels = [];
$data = [];
foreach ($trends as $trend) {
    $labels[] = date('d/m', strtotime((string) ($trend['date'] ?? 'now')));
    $data[] = (int) ($trend['count'] ?? 0);
}

$serviceCount = 0;
$postCount = 0;
$reclamationCount = 0;

try {
    $serviceCount = (int) $db->query("SELECT COUNT(*) FROM service")->fetchColumn();
} catch (Throwable $e) {
    $serviceCount = 0;
}

try {
    $postCount = (int) $db->query("SELECT COUNT(*) FROM post")->fetchColumn();
} catch (Throwable $e) {
    $postCount = 0;
}

try {
    $reclamationCount = (int) $db->query("SELECT COUNT(*) FROM reclamation")->fetchColumn();
} catch (Throwable $e) {
    $reclamationCount = 0;
}

$roleLabels = ['Clients', 'Providers', 'Admins'];
$roleData = [
    (int) ($stats['user'] ?? 0),
    (int) ($stats['provider'] ?? 0),
    (int) ($stats['admin'] ?? 0),
];
?>
<section class="admin-stats reveal">
    <article class="admin-stat glass-panel card-float">
        <strong class="dashboard-stat-value" data-target="<?php echo (int) ($stats['total'] ?? 0); ?>">0</strong>
        <span>Utilisateurs</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay:0.1s">
        <strong class="dashboard-stat-value" data-target="<?php echo $serviceCount; ?>">0</strong>
        <span>Services</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay:0.2s">
        <strong class="dashboard-stat-value" data-target="<?php echo $postCount; ?>">0</strong>
        <span>Forum posts</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay:0.3s">
        <strong class="dashboard-stat-value" data-target="<?php echo $reclamationCount; ?>">0</strong>
        <span>Réclamations</span>
    </article>
</section>

<div class="admin-grid reveal" style="grid-template-columns:2fr 1fr;gap:20px;">
    <article class="admin-panel glass-panel">
        <span class="section-badge"><?php echo app_text('Croissance', 'Growth', 'النمو'); ?></span>
        <h3 style="color:var(--text);margin:8px 0 6px;">Évolution des inscriptions</h3>
        <p>Analyse des 30 derniers jours.</p>
        <div style="height:350px;margin-top:20px;position:relative;">
            <canvas id="registrationChart"></canvas>
        </div>
    </article>

    <article class="admin-panel glass-panel">
        <span class="section-badge"><?php echo app_text('Distribution', 'Distribution', 'التوزيع'); ?></span>
        <h3 style="color:var(--text);margin:8px 0 6px;">Rôles</h3>
        <p>Distribution par profil.</p>
        <div style="height:300px;margin-top:20px;">
            <canvas id="roleChart"></canvas>
        </div>
    </article>
</div>

<section class="admin-grid reveal">
    <article class="admin-panel">
        <span class="section-badge"><?php echo app_text('Actions rapides', 'Quick actions', 'إجراءات سريعة'); ?></span>
        <div class="feature-list">
            <div class="feature-item">Vérifier les nouveaux services publiés</div>
            <div class="feature-item">Consulter les réclamations urgentes</div>
            <div class="feature-item">Modérer les derniers posts du forum</div>
            <div class="feature-item">Suivre les événements à venir</div>
        </div>
    </article>

    <article class="admin-panel">
        <span class="section-badge">Contrôles</span>
        <div class="feature-list">
            <div class="feature-item">La validation des services reste lisible en mode sombre et clair</div>
            <div class="feature-item">La modération du forum reste reliée au flux en direct</div>
            <div class="feature-item">Les participations aux événements envoient encore les confirmations si le mail est configuré</div>
            <div class="feature-item">Les réclamations et réservations restent accessibles depuis le menu gauche</div>
        </div>
    </article>
</section>

<section class="table-panel reveal">
    <span class="section-badge">Activité récente</span>
    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Module</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>New account registered</td>
                    <td>Users</td>
                    <td>Pending review</td>
                </tr>
                <tr>
                    <td>Service submitted for approval</td>
                    <td>Services</td>
                    <td>Needs validation</td>
                </tr>
                <tr>
                    <td>Forum post flagged for moderation</td>
                    <td>Forum</td>
                    <td>Review</td>
                </tr>
                <tr>
                    <td>Upcoming event ready to publish</td>
                    <td>Events</td>
                    <td>Ready</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const regCanvas = document.getElementById('registrationChart');
    const roleCanvas = document.getElementById('roleChart');
    if (!regCanvas || !roleCanvas || typeof Chart === 'undefined') return;

    const regCtx = regCanvas.getContext('2d');
    const regGradient = regCtx.createLinearGradient(0, 0, 0, 400);
    regGradient.addColorStop(0, 'rgba(238,88,40,0.35)');
    regGradient.addColorStop(1, 'rgba(238,88,40,0.02)');

    new Chart(regCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Registrations',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#EE5828',
                backgroundColor: regGradient,
                borderWidth: 3,
                tension: 0.32,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#EE5828',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9fb2c7', stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: '#9fb2c7' } }
            }
        }
    });

    new Chart(roleCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($roleLabels, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                data: <?php echo json_encode($roleData); ?>,
                backgroundColor: ['#EE5828', '#35b86b', '#5f75ff'],
                borderWidth: 0,
                hoverOffset: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#9fb2c7', usePointStyle: true, padding: 18 }
                }
            }
        }
    });
});
</script>
