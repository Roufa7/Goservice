// reclamation.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-reclamation');
    const sujet = document.getElementById('sujet-reclamation');
    const desc = document.getElementById('desc-reclamation');
    const errorMsg = document.getElementById('error-message');
    const successMsg = document.getElementById('success-message');

    // Function to display user's reclamations
    function loadUserReclamations() {
        const container = document.getElementById('reclamations-list');
        if (!container) return;

        fetch('../../controller/get_reclamations.php')
            .then(response => response.json())
            .then(data => {
                container.innerHTML = ''; // Clear container
                
                if (data.success && data.reclamations.length > 0) {
                    data.reclamations.forEach(rec => {
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
                console.error('Error:', error);
                container.innerHTML = '<p class="page-intro" style="color:red;">Erreur lors du chargement des réclamations.</p>';
            });
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            let errors = [];
            
            // Reset styles
            [sujet, desc].forEach(field => {
                if (field) field.style.border = "1px solid #ddd";
            });
            
            // Validation
            if (sujet.value.trim() === '') {
                errors.push("Le sujet est obligatoire.");
                sujet.style.border = "1px solid red";
            }
            
            if (desc.value.trim() === '') {
                errors.push("La description est obligatoire.");
                desc.style.border = "1px solid red";
            }
            
            if (errors.length > 0) {
                errorMsg.innerHTML = "<strong>Erreur :</strong><br>" + errors.join("<br>");
                errorMsg.style.display = 'block';
                successMsg.style.display = 'none';
                return;
            }
            
            // Hide error message
            errorMsg.style.display = 'none';
            
            // Prepare form data
            const formData = new FormData(form);
            formData.append('submit_reclamation', '1');
            
            // Submit via AJAX
            fetch('index.php?page=reclamation', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMsg.innerHTML = "<strong>Succès :</strong><br>" + data.message;
                    successMsg.style.display = 'block';
                    errorMsg.style.display = 'none';
                    
                    // Reset form
                    form.reset();
                    
                    // Reload reclamations list
                    loadUserReclamations();
                    
                    // Clear success message after 3 seconds
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 3000);
                } else {
                    errorMsg.innerHTML = "<strong>Erreur :</strong><br>" + data.message;
                    errorMsg.style.display = 'block';
                    successMsg.style.display = 'none';
                }
            })
            .catch(error => {
                errorMsg.innerHTML = "<strong>Erreur :</strong><br>Une erreur s'est produite lors de l'envoi.";
                errorMsg.style.display = 'block';
                successMsg.style.display = 'none';
                console.error('Error:', error);
            });
        });
    }
    
    // Load existing reclamations on page load
    loadUserReclamations();
});

// Global functions for edit and delete
function editReclamation(id) {
    // Implement edit functionality
    console.log('Edit reclamation:', id);
}

function deleteReclamation(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
        fetch('delete_reclamation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}