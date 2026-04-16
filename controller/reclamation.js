// reclamation.js
let globalReclamations = {};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-reclamation');
    const sujet = document.getElementById('sujet-reclamation');
    const desc = document.getElementById('desc-reclamation');
    const errorMsg = document.getElementById('error-message');
    const successMsg = document.getElementById('success-message');

    window.loadUserReclamations = function() {
        const container = document.getElementById('reclamations-list');
        if (!container) return;

        fetch('index.php?page=reclamation&action=get_all')
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';
                globalReclamations = {};
                
                if (data.success && data.reclamations.length > 0) {
                    data.reclamations.forEach(rec => {
                        globalReclamations[rec.id_reclamation] = rec;
                        const statusClass = rec.status === 'pending' ? 'status-pending' : 
                                          (rec.status === 'resolved' ? 'status-resolved' : 'status-rejected');
                        const statusText = rec.status === 'pending' ? 'En attente' :
                                         (rec.status === 'resolved' ? 'Résolue' : 'Rejetée');
                        
                        const newCard = document.createElement('article');
                        newCard.className = 'post-card';
                        newCard.style.marginTop = '18px';
                        newCard.innerHTML = `
                            <span class="section-badge">Ma réclamation</span>
                            <h3>${escapeHtml(rec.subject)}</h3>
                            <p>${escapeHtml(rec.description)}</p>
                            <div class="meta-row">
                                <span>${new Date(rec.created_at).toLocaleDateString()}</span>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div class="icon-actions" style="margin-top:14px;">
                                <button class="small-btn" onclick="editReclamation(${rec.id_reclamation})">Modifier</button>
                                <button class="danger-btn" onclick="deleteReclamation(${rec.id_reclamation})">Supprimer</button>
                            </div>
                        `;
                        container.appendChild(newCard);
                    });
                } else {
                    container.innerHTML = '<p class="page-intro">Vous n\'avez pas encore déposé de réclamation.</p>';
                }
            })
            .catch(error => {
                container.innerHTML = '<p class="page-intro" style="color:red;">Erreur lors du chargement des réclamations.</p>';
            });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let errors = [];
            
            [sujet, desc].forEach(field => { if (field) field.style.border = "1px solid #ddd"; });
            if (sujet.value.trim() === '') { errors.push("Le sujet est obligatoire."); sujet.style.border = "1px solid red"; }
            if (desc.value.trim() === '') { errors.push("La description est obligatoire."); desc.style.border = "1px solid red"; }
            
            if (errors.length > 0) {
                errorMsg.innerHTML = "<strong>Erreur :</strong><br>" + errors.join("<br>");
                errorMsg.style.display = 'block';
                successMsg.style.display = 'none';
                return;
            }
            
            errorMsg.style.display = 'none';
            const formData = new FormData(form);
            formData.append('submit_reclamation', '1');
            
            fetch('index.php?page=reclamation', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    successMsg.innerHTML = "<strong>Succès :</strong><br>" + data.message;
                    successMsg.style.display = 'block';
                    errorMsg.style.display = 'none';
                    form.reset();
                    document.getElementById('id-reclamation').value = '';
                    document.getElementById('btn-cancel-edit').style.display = 'none';
                    loadUserReclamations();
                    setTimeout(() => { successMsg.style.display = 'none'; }, 3000);
                } else {
                    errorMsg.innerHTML = "<strong>Erreur :</strong><br>" + data.message;
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => console.error(error));
        });
    }
    loadUserReclamations();
});

window.editReclamation = function(id) {
    const rec = globalReclamations[id];
    if (rec) {
        document.getElementById('id-reclamation').value = rec.id_reclamation;
        document.getElementById('sujet-reclamation').value = rec.subject;
        document.getElementById('desc-reclamation').value = rec.description;
        document.getElementById('btn-cancel-edit').style.display = 'inline-block';
        document.getElementById('form-reclamation').scrollIntoView({ behavior: 'smooth' });
    }
}

window.cancelEdit = function() {
    document.getElementById('form-reclamation').reset();
    document.getElementById('id-reclamation').value = '';
    document.getElementById('btn-cancel-edit').style.display = 'none';
}

window.deleteReclamation = function(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
        fetch('index.php?page=reclamation&action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({id: id})
        })
        .then(r => r.json())
        .then(data => { if (data.success) { loadUserReclamations(); } else { alert('Erreur: ' + data.message); } });
    }
}