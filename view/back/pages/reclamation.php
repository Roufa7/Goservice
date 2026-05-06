<section class="admin-panel reveal" style="margin-bottom: 22px;">
    <span class="section-badge">Analytique & Distribution</span>

    <div class="admin-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 15px;">
        <!-- Column 1: Line Chart -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h3>Volume des réclamations</h3>
                    <p>Visualisation quotidienne par mois.</p>
                </div>
                <div class="search-box" style="margin: 0; display: flex; gap: 10px;">
                    <select id="stat-month" style="width: 120px; padding: 8px;">
                        <?php
                        $months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
                        $currentMonth = date('n');
                        foreach ($months as $index => $m) {
                            $val = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                            $selected = ($index + 1 == $currentMonth) ? 'selected' : '';
                            echo "<option value='$val' $selected>$m</option>";
                        }
                        ?>
                    </select>
                    <select id="stat-year" style="width: 100px; padding: 8px;">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                            echo "<option value='$y'>$y</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div style="height: 280px;">
                <canvas id="reclamationChart"></canvas>
            </div>
        </div>

        <!-- Column 2: Donut Chart -->
        <div style="border-left: 1px solid rgba(255,255,255,0.1); padding-left: 20px;">
            <h3>Répartition</h3>
            <p>Par statut actuel.</p>
            <div style="height: 250px; margin-top: 20px;">
                <canvas id="statusDonut"></canvas>
            </div>
        </div>
    </div>
</section>

<script>
    let reclamationChart;
    let statusDonut;

    document.addEventListener('DOMContentLoaded', function () {
        const chartData = <?php echo json_encode($reclamationChartData ?? []); ?>;
        const statusData = <?php echo json_encode($statusDistribution ?? []); ?>;

        // Line Chart
        const ctx = document.getElementById('reclamationChart').getContext('2d');
        reclamationChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(d => d.day),
                datasets: [{
                    label: 'Réclamations',
                    data: chartData.map(d => d.count),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#888', stepSize: 1 } },
                    x: { grid: { display: false }, ticks: { color: '#888' } }
                }
            }
        });

        // Donut Chart
        const ctxD = document.getElementById('statusDonut').getContext('2d');
        const statusColors = {
            'pending': '#d97706',
            'in_progress': '#2563eb',
            'resolved': '#16a34a',
            'rejected': '#dc2626'
        };
        const statusLabels = {
            'pending': 'Nouvelles',
            'in_progress': 'En cours',
            'resolved': 'Traitées',
            'rejected': 'Fermées'
        };

        statusDonut = new Chart(ctxD, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => statusLabels[d.status] || d.status),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: statusData.map(d => statusColors[d.status] || '#888'),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#888', boxWidth: 12 } }
                }
            }
        });

        document.getElementById('stat-month').addEventListener('change', updateReclamationStats);
        document.getElementById('stat-year').addEventListener('change', updateReclamationStats);
    });

    function updateReclamationStats() {
        const month = document.getElementById('stat-month').value;
        const year = document.getElementById('stat-year').value;

        fetch(`index.php?page=reclamation&action=get_filtered_stats&month=${month}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    reclamationChart.data.labels = data.stats.map(d => d.day);
                    reclamationChart.data.datasets[0].data = data.stats.map(d => d.count);
                    reclamationChart.update();
                }
            })
            .catch(err => console.error(err));
    }
</script>



<section class="admin-panel reveal">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
        <span class="section-badge" style="margin: 0;">Gestion des réclamations</span>

        <div class="search-box" style="margin: 0; display: flex; gap: 10px; flex-grow: 1; justify-content: flex-end;">
            <input type="text" id="admin-search-input" placeholder="Chercher client, sujet..."
                style="max-width: 250px;">
            <select id="admin-filter-status" style="width: auto;">
                <option value="all">Tous statuts</option>
                <option value="pending">Nouvelles</option>
                <option value="in_progress">En cours</option>
                <option value="resolved">Traitées</option>
                <option value="rejected">Fermées</option>
            </select>
            <select id="admin-sort-by" style="width: auto;">
                <option value="date_desc">Récent</option>
                <option value="date_asc">Ancien</option>
            </select>
            <button class="outline-btn" onclick="loadAdminData()" title="Actualiser">↻</button>
        </div>
    </div>

    <div class="table-wrap" style="max-height: 450px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #007bff transparent; margin-bottom: 10px;">
    <style>
        .table-wrap::-webkit-scrollbar { width: 6px; }
        .table-wrap::-webkit-scrollbar-track { background: transparent; }
        .table-wrap::-webkit-scrollbar-thumb { background-color: #007bff; border-radius: 20px; }
    </style>

        <table class="module-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Sujet</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-reclamations-list">
                <tr>
                    <td colspan="6">Chargement...</td>
                </tr>
            </tbody>
        </table>
    </div>

</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Réponses envoyées</span>
    <div class="table-wrap" style="max-height: 450px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #007bff transparent; margin-bottom: 10px;">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Sujet</th>
                    <th>Réponse admin</th>
                    <th>Date réponse</th>
                    <th>Statut final</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-responses-list">
                <tr>
                    <td colspan="6">Chargement...</td>
                </tr>
            </tbody>
        </table>
    </div>

</section>

<section class="admin-panel reveal" style="margin-top: 22px;" id="response-panel">
    <span class="section-badge">Réponse / Traitement rapide</span>
    <form id="admin-reclamation-form">
        <input type="hidden" id="admin-rec-id" name="id_reclamation">
        <input type="hidden" name="action" value="process_reclamation">

        <div class="form-grid">
            <input type="text" id="admin-client-name" placeholder="Nom du client" readonly
                style="opacity: 0.6; pointer-events: none;">
            <input type="text" id="admin-rec-subject" placeholder="Sujet de la réclamation" readonly
                style="opacity: 0.6; pointer-events: none;">

            <select name="status" id="admin-rec-status">
                <option value="">-- Mettre à jour le statut --</option>
                <option value="pending">En attente (Nouvelle)</option>
                <option value="in_progress">En cours</option>
                <option value="resolved">Résolue (Traitée)</option>
                <option value="rejected">Rejetée (Fermée)</option>
            </select>

            <input type="text" placeholder="Priorité" readonly style="opacity: 0.6; pointer-events: none;"
                value="Normale">

            <textarea name="content" id="admin-rec-response"
                placeholder="Rédiger une réponse à la réclamation... (Optionnel si vous majez juste le statut)"></textarea>
        </div>

        <div id="admin-form-msg" style="margin-top:10px; display:none;"></div>
        <div class="icon-actions" style="margin-top: 14px;">
            <button type="submit" class="solid-btn">Traiter / Envoyer</button>
            <button type="button" class="outline-btn" style="display:none;" id="btn-cancel-admin"
                onclick="cancelAdminEdit()">Annuler</button>
        </div>
    </form>
</section>

<script src="../../controller/back/admin_reclamation.js?v=<?= time() ?>"></script>