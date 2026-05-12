// admin_reclamation.js
let globalAdminReclamations = {};
let globalAdminResponses = {};
let allFetchedReclamations = []; // Store raw data for filtering

document.addEventListener('DOMContentLoaded', function () {
    window.loadAdminData();

    // Event listeners for Search and Sort
    const searchInput = document.getElementById('admin-search-input');
    const filterStatus = document.getElementById('admin-filter-status');
    const sortBy = document.getElementById('admin-sort-by');

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (filterStatus) filterStatus.addEventListener('change', applyFilters);
    if (sortBy) sortBy.addEventListener('change', applyFilters);

    const form = document.getElementById('admin-reclamation-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const id = document.getElementById('admin-rec-id').value;
            const status = document.getElementById('admin-rec-status').value;
            const content = document.getElementById('admin-rec-response').value.trim();
            const msgBox = document.getElementById('admin-form-msg');

            if (!id) {
                msgBox.innerHTML = "Veuillez sélectionner une réclamation à traiter d'abord.";
                msgBox.style.color = "red";
                msgBox.style.display = "block";
                return;
            }
            if (!status && !content) {
                msgBox.innerHTML = "Veuillez choisir un statut ou rédiger une réponse.";
                msgBox.style.color = "red";
                msgBox.style.display = "block";
                return;
            }

            msgBox.style.display = "none";
            const formData = new FormData(form);

            fetch('index.php?page=reclamation', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        msgBox.innerHTML = data.message;
                        msgBox.style.color = "green";
                        msgBox.style.display = "block";
                        window.cancelAdminEdit();
                        window.loadAdminData();
                        setTimeout(() => msgBox.style.display = 'none', 3000);
                    } else {
                        msgBox.innerHTML = data.message;
                        msgBox.style.color = "red";
                        msgBox.style.display = "block";
                    }
                })
                .catch(err => console.error(err));
        });
    }
});

window.loadAdminData = function () {
    loadReclamations();
    loadResponses();
}

function getStatusLabel(status) {
    if (status === 'pending') return '<span style="color:#d97706;">Nouvelle</span>';
    if (status === 'in_progress') return '<span style="color:#2563eb;">En cours</span>';
    if (status === 'resolved') return '<span style="color:#16a34a;">Traitée</span>';
    if (status === 'rejected') return '<span style="color:#dc2626;">Fermée</span>';
    return status;
}

function loadReclamations() {
    fetch('index.php?page=reclamation&action=get_all_reclamations')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allFetchedReclamations = data.reclamations;
                applyFilters(); // Initial render
            }
        })
        .catch(err => console.error(err));
}

function applyFilters() {
    const searchTerm = document.getElementById('admin-search-input').value.toLowerCase();
    const statusValue = document.getElementById('admin-filter-status').value;
    const sortValue = document.getElementById('admin-sort-by').value;

    let filtered = allFetchedReclamations.filter(rec => {
        const clientName = `${rec.user_nom || ''} ${rec.user_prenom || ''}`.toLowerCase();
        const matchesSearch = rec.subject.toLowerCase().includes(searchTerm) ||
            rec.description.toLowerCase().includes(searchTerm) ||
            clientName.includes(searchTerm);

        const matchesStatus = (statusValue === 'all') || (rec.status === statusValue);

        return matchesSearch && matchesStatus;
    });

    // Sorting
    filtered.sort((a, b) => {
        if (sortValue === 'date_desc') return new Date(b.created_at) - new Date(a.created_at);
        if (sortValue === 'date_asc') return new Date(a.created_at) - new Date(b.created_at);
        if (sortValue === 'subject') return a.subject.localeCompare(b.subject);
        return 0;
    });

    renderReclamations(filtered);
}

function renderReclamations(reclamations) {
    const list = document.getElementById('admin-reclamations-list');
    list.innerHTML = '';
    globalAdminReclamations = {};

    if (reclamations.length > 0) {
        reclamations.forEach(rec => {
            globalAdminReclamations[rec.id_reclamation] = rec;
            const clientName = `${rec.user_nom || ''} ${rec.user_prenom || ''}`.trim() || 'Client inconnu';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(clientName)}</td>
                <td>${escapeHtml(rec.subject)}</td>
                <td>${escapeHtml(rec.description).substring(0, 50)}...</td>
                <td>${new Date(rec.created_at).toLocaleDateString()}</td>
                <td>${getStatusLabel(rec.status)}</td>
                <td class="admin-tools">
                    <button class="small-btn" onclick="initTraitement(${rec.id_reclamation}, '${rec.status}')">Gérer</button>
                </td>
            `;
            list.appendChild(tr);
        });
    } else {
        list.innerHTML = '<tr><td colspan="6">Aucune réclamation trouvée.</td></tr>';
    }
}

function loadResponses() {
    const list = document.getElementById('admin-responses-list');
    fetch('index.php?page=reclamation&action=get_all_responses')
        .then(res => res.json())
        .then(data => {
            list.innerHTML = '';
            globalAdminResponses = {};

            if (data.success && data.reponses.length > 0) {
                data.reponses.forEach(rep => {
                    globalAdminResponses[rep.id_reponse] = rep;
                    const clientName = `${rep.user_nom || ''} ${rep.user_prenom || ''}`.trim() || 'Client inconnu';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(clientName)}</td>
                        <td>${escapeHtml(rep.rec_subject)}</td>
                        <td>${escapeHtml(rep.content).substring(0, 50)}...</td>
                        <td>${new Date(rep.created_at).toLocaleDateString()}</td>
                        <td>${getStatusLabel(rep.rec_status)}</td>
                        <td class="admin-tools">
                            <button class="danger-btn" onclick="deleteResponse(${rep.id_reponse})">Supprimer</button>
                        </td>
                    `;
                    list.appendChild(tr);
                });
            } else {
                list.innerHTML = '<tr><td colspan="6">Aucune réponse envoyée pour le moment.</td></tr>';
            }
        })
        .catch(err => console.error(err));
}

window.deleteResponse = function (id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?')) {
        fetch('index.php?page=reclamation&action=delete_response', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.loadAdminData();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(err => console.error(err));
    }
}

window.initTraitement = function (id_reclamation, currentStatus) {
    const rec = globalAdminReclamations[id_reclamation];
    if (rec) {
        document.getElementById('admin-rec-id').value = rec.id_reclamation;
        document.getElementById('admin-client-name').value = `${rec.user_nom || ''} ${rec.user_prenom || ''}`.trim();
        document.getElementById('admin-rec-subject').value = rec.subject;
        document.getElementById('admin-rec-status').value = currentStatus;
        document.getElementById('btn-cancel-admin').style.display = 'inline-block';

        document.getElementById('response-panel').scrollIntoView({ behavior: 'smooth' });
    }
}

window.cancelAdminEdit = function () {
    document.getElementById('admin-reclamation-form').reset();
    document.getElementById('admin-rec-id').value = '';
    document.getElementById('btn-cancel-admin').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
