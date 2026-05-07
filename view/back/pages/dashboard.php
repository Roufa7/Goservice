<?php
require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$stats = $userModel->getUserStats();
$trends = $userModel->getRegistrationTrends();

// Prepare Line Chart Data
$labels = [];
$data = [];
foreach ($trends as $t) {
    $labels[] = date('d/m', strtotime($t['date']));
    $data[] = $t['count'];
}

// Prepare Doughnut Chart Data
$roleLabels = ['Clients', 'Providers', 'Admins'];
$roleData = [$stats['user'], $stats['provider'], $stats['admin']];
?>
<section class="admin-stats reveal">
    <article class="admin-stat glass-panel card-float">
        <strong class="stat-animate grad-text" data-target="<?php echo $stats['total']; ?>">0</strong>
        <span>Utilisateurs</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay: 0.2s">
        <strong class="stat-animate grad-text" data-target="324">0</strong>
        <span>Services</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay: 0.4s">
        <strong class="stat-animate grad-text" data-target="86">0</strong>
        <span>Posts forum</span>
    </article>
    <article class="admin-stat glass-panel card-float" style="animation-delay: 0.6s">
        <strong class="stat-animate grad-text" data-target="41">0</strong>
        <span>Réclamations</span>
    </article>
</section>

<div class="admin-grid reveal" style="grid-template-columns: 2fr 1fr; gap: 20px;">
    <article class="admin-panel glass-panel">
        <span class="section-badge">Croissance</span>
        <h3 class="grad-text">Évolution des inscriptions</h3>
        <p>Analyse des 30 derniers jours.</p>

        <div style="height: 350px; margin-top: 20px; position: relative;">
            <canvas id="registrationChart"></canvas>
        </div>
    </article>

    <article class="admin-panel glass-panel">
        <span class="section-badge">Répartition</span>
        <h3 class="grad-text">Rôles</h3>
        <p>Distribution par profil.</p>
        <div style="height: 300px; margin-top: 20px;">
            <canvas id="roleChart"></canvas>
        </div>
    </article>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Line Chart with Gradient
    const regCtx = document.getElementById('registrationChart').getContext('2d');
    const regGradient = regCtx.createLinearGradient(0, 0, 0, 400);
    regGradient.addColorStop(0, 'rgba(108, 92, 231, 0.4)');
    regGradient.addColorStop(1, 'rgba(108, 92, 231, 0)');

    new Chart(regCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Inscriptions',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#6c5ce7',
                backgroundColor: regGradient,
                borderWidth: 4,
                tension: 0, // Zigzag look
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6c5ce7',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#888', stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#888' }
                }
            },
            animation: {
                y: {
                    duration: 2000,
                    from: 500
                },
                x: {
                    duration: 2000,
                    from: 0
                }
            }
        }
    });

    // Doughnut Chart - Drawing effect
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($roleLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($roleData); ?>,
                backgroundColor: ['#EE5828', '#4CAF50', '#6c5ce7'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            rotation: -90,
            circumference: 360,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#888', usePointStyle: true, padding: 20 }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 3000,
                easing: 'easeInOutQuart'
            }
        }
    });
});
</script>

<section class="admin-grid reveal">
    <article class="admin-panel">
        <span class="section-badge">Accès rapide</span>
        <div class="feature-list">
            <div class="feature-item">Valider les nouveaux services</div>
            <div class="feature-item">Contrôler les réclamations urgentes</div>
            <div class="feature-item">Modérer les publications forum</div>
            <div class="feature-item">Gérer les événements à venir</div>
        </div>
    </article>

    <article class="admin-panel">
        <span class="section-badge">KPI visuels</span>
        <div class="feature-list">
            <div class="feature-item">Taux de validation services : 84%</div>
            <div class="feature-item">Engagement forum : 72%</div>
            <div class="feature-item">Participation événements : 67%</div>
            <div class="feature-item">Réclamations résolues : 78%</div>
        </div>
    </article>
</section>

<section class="table-panel reveal">
    <span class="section-badge">Activité récente</span>
    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Activité</th>
                    <th>Module</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nouveau provider inscrit</td>
                    <td>Utilisateurs</td>
                    <td>En attente</td>
                </tr>
                <tr>
                    <td>Service signalé pour vérification</td>
                    <td>Services</td>
                    <td>À valider</td>
                </tr>
                <tr>
                    <td>Publication forum signalée</td>
                    <td>Forum</td>
                    <td>Modération</td>
                </tr>
                <tr>
                    <td>Nouvel événement prêt à publier</td>
                    <td>Événements</td>
                    <td>Prêt</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>