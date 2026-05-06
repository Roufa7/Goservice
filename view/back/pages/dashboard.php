<section class="admin-stats reveal">
    <article class="admin-stat">
        <strong><?php echo $adminStats['users'] ?? 0; ?></strong>
        <span>Utilisateurs</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $adminStats['services'] ?? 0; ?></strong>
        <span>Services</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $adminStats['posts'] ?? 0; ?></strong>
        <span>Posts forum</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $adminStats['reclamations'] ?? 0; ?></strong>
        <span>Réclamations</span>
    </article>
</section>

<section class="admin-grid reveal">
    <article class="admin-panel" style="flex: 1;">
        <span class="section-badge">Vue globale</span>
        <h3>Activité du site</h3>
        <p>Aperçu rapide des modules principaux.</p>
        <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
             <canvas id="moduleDonut"></canvas>
        </div>
    </article>

    <article class="admin-panel" style="flex: 1;">
        <span class="section-badge">Accès rapide</span>
        <div class="feature-list">
            <div class="feature-item">Valider les nouveaux services</div>
            <div class="feature-item">Contrôler les réclamations urgentes</div>
            <div class="feature-item">Modérer les publications forum</div>
            <div class="feature-item">Gérer les événements à venir</div>
        </div>
    </article>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxDonut = document.getElementById('moduleDonut').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: ['Users', 'Services', 'Forum', 'Réclamations'],
            datasets: [{
                data: [
                    <?php echo $adminStats['users'] ?? 0; ?>, 
                    <?php echo $adminStats['services'] ?? 0; ?>, 
                    <?php echo $adminStats['posts'] ?? 0; ?>, 
                    <?php echo $adminStats['reclamations'] ?? 0; ?>
                ],
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#888' } }
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