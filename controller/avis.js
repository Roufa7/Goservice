// avis.js
let globalAvis = {};

document.addEventListener('DOMContentLoaded', function() {
    window.loadGlobalAvis();
    
    const formAvis = document.getElementById('form-avis');
    if (formAvis) {
        formAvis.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const commentaire = document.getElementById('avis-commentaire').value.trim();
            const reclamationId = document.getElementById('avis-reclamation-id').value;
            const errorMsg = document.getElementById('avis-error-message');
            const successMsg = document.getElementById('avis-success-message');
            
            if (!reclamationId) {
                errorMsg.innerHTML = "Veuillez sélectionner une réclamation.";
                errorMsg.style.display = 'block';
                return;
            }
            if (!commentaire) {
                errorMsg.innerHTML = "Veuillez écrire un commentaire.";
                errorMsg.style.display = 'block';
                return;
            }

            errorMsg.style.display = 'none';

            const formData = new FormData(formAvis);
            formData.append('submit_avis', '1');

            fetch('index.php?page=avis', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    successMsg.innerHTML = "<strong>Succès :</strong><br>" + data.message;
                    successMsg.style.display = 'block';
                    errorMsg.style.display = 'none';
                    cancelAvisEdit();
                    window.loadGlobalAvis();
                    setTimeout(() => successMsg.style.display = 'none', 3000);
                } else {
                    errorMsg.innerHTML = "<strong>Erreur :</strong><br>" + data.message;
                    errorMsg.style.display = 'block';
                    successMsg.style.display = 'none';
                }
            })
            .catch(error => console.error("Error:", error));
        });
    }

    const stars = document.querySelectorAll('.star-input-group .star');
    const ratingInput = document.getElementById('avis-rating');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const val = this.getAttribute('data-value');
            ratingInput.value = val;
            stars.forEach(s => s.style.color = '#ccc');
            for(let i=0; i<val; i++) {
                stars[i].style.color = '#ffd700';
            }
        });
    });
});

window.loadGlobalAvis = function() {
    const container = document.getElementById('avis-list');
    if (!container) return;

    fetch('index.php?page=avis&action=get_all_global')
        .then(res => res.json())
        .then(data => {
            container.innerHTML = '';
            globalAvis = {};

            if(data.success && data.avis.length > 0) {
                data.avis.forEach(avis => {
                    globalAvis[avis.id_avis] = avis;
                    let starsHtml = '';
                    for(let i=0; i<5; i++) {
                        starsHtml += `<span class="star" style="color: ${i < avis.rating ? '#ffd700' : '#ccc'}">★</span>`;
                    }
                    
                    const name = (avis.nom && avis.prenom) ? `${avis.nom} ${avis.prenom.charAt(0)}.` : 'Utilisateur';
                    
                    let actionHtml = '';
                    actionHtml = `
                        <div class="icon-actions" style="margin-top:10px;">
                            <button class="small-btn" onclick="editAvis(${avis.id_avis})">Modifier</button>
                            <button class="danger-btn" onclick="deleteAvis(${avis.id_avis})">Supprimer</button>
                        </div>
                    `;

                    const item = document.createElement('div');
                    item.className = 'comment-item';
                    item.innerHTML = `
                        <strong>${name}</strong>
                        <div class="review-stars" style="margin:6px 0 10px;">
                            ${starsHtml}
                        </div>
                        <p>${escapeHtml(avis.commentaire)}</p>
                        <span class="page-intro">Publié le ${new Date(avis.created_at).toLocaleDateString()}</span>
                        ${actionHtml}
                    `;
                    container.appendChild(item);
                });
            } else {
                container.innerHTML = '<p class="page-intro">Aucun avis pour le moment.</p>';
            }
        })
        .catch(err => console.error(err));
}

window.editAvis = function(id) {
    const a = globalAvis[id];
    if (a) {
        document.getElementById('id-avis').value = a.id_avis;
        document.getElementById('avis-reclamation-id').value = a.id_reclamation;
        document.getElementById('avis-commentaire').value = a.commentaire;
        document.getElementById('avis-rating').value = a.rating;
        document.getElementById('btn-cancel-avis').style.display = 'inline-block';
        
        const stars = document.querySelectorAll('.star-input-group .star');
        stars.forEach(s => s.style.color = '#ccc');
        for(let i=0; i < a.rating; i++) {
            if(stars[i]) stars[i].style.color = '#ffd700';
        }

        document.getElementById('form-avis').scrollIntoView({ behavior: 'smooth' });
    }
}

window.cancelAvisEdit = function() {
    document.getElementById('form-avis').reset();
    document.getElementById('id-avis').value = '';
    document.getElementById('btn-cancel-avis').style.display = 'none';
    document.getElementById('avis-rating').value = 5;
    
    const stars = document.querySelectorAll('.star-input-group .star');
    stars.forEach(s => s.style.color = '#ffd700');
}

window.deleteAvis = function(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')) {
        fetch('index.php?page=avis&action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({id: id})
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.loadGlobalAvis();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(err => console.error(err));
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
