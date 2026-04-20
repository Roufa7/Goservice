document.addEventListener('DOMContentLoaded', () => {
    const applicationForm = document.getElementById('applicationForm');
    const searchOffers = document.getElementById('searchOffers');

    function setFieldError(fieldName, message) {
        const errorNode = document.querySelector(`[data-error-for="${fieldName}"]`);
        if (errorNode) {
            errorNode.textContent = message;
        }
    }

    function clearFieldErrors() {
        document.querySelectorAll('.field-error').forEach((node) => {
            node.textContent = '';
        });
    }

    function setAriaInvalid(fieldId, isInvalid) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
        }
    }

    function formatDateLabel(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('fr-FR');
    }

    document.querySelectorAll('.postuler-btn').forEach((button) => {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const offerId = this.dataset.offerId;
            const offerTitle = this.dataset.offerTitle;
            const offerField = document.getElementById('offerIdField');
            const selectedLabel = document.getElementById('selectedOfferLabel');

            if (offerField) {
                offerField.value = offerId;
            }

            if (selectedLabel) {
                const titleNode = selectedLabel.querySelector('span');
                if (titleNode) {
                    titleNode.textContent = offerTitle;
                }
            }

            location.hash = '#postuler-offre';
        });
    });

    document.querySelectorAll('.offer-card').forEach((card) => {
        const details = card.querySelector('.offer-details');
        const toggleButton = card.querySelector('.toggle-details-btn');

        if (!details || !toggleButton) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            const isHidden = details.style.display === 'none' || details.style.display === '';
            details.style.display = isHidden ? 'block' : 'none';
            this.textContent = isHidden ? 'Masquer détails' : 'Voir détails';
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    });

    if (searchOffers) {
        searchOffers.addEventListener('input', (event) => {
            const search = event.target.value.toLowerCase();

            document.querySelectorAll('.offer-card').forEach((card) => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(search) ? 'block' : 'none';
            });
        });
    }

    if (applicationForm) {
        applicationForm.addEventListener('submit', (event) => {
            clearFieldErrors();

            const offerIdField = document.getElementById('offerIdField');
            const experience = document.getElementById('experienceField')?.value.trim() ?? '';
            const competences = document.getElementById('competencesField')?.value.trim() ?? '';
            const message = document.getElementById('messageField')?.value.trim() ?? '';
            const cvInput = document.getElementById('cvField');
            const requireCv = applicationForm.dataset.requireCv !== 'false';

            const errors = {};

            if (!offerIdField || !offerIdField.value) {
                errors.offer_id = 'Veuillez choisir une offre valide.';
            }

            if (!/^\d{1,2}$/.test(experience)) {
                errors.experience = 'Saisissez une valeur numérique valide (0 à 50).';
            } else if (parseInt(experience, 10) > 50) {
                errors.experience = 'La valeur maximale autorisée est 50 ans.';
            }

            if (competences.length < 3) {
                errors.competences = 'Ajoutez au moins 3 caractères.';
            } else if (competences.length > 255) {
                errors.competences = 'Maximum 255 caractères.';
            }

            if (message.length < 20) {
                errors.message = 'La lettre de motivation doit contenir au moins 20 caractères.';
            } else if (message.length > 2000) {
                errors.message = 'Maximum 2000 caractères.';
            }

            if (requireCv && (!cvInput || !cvInput.files || cvInput.files.length === 0)) {
                errors.cv = 'Le CV est obligatoire.';
            } else if (cvInput && cvInput.files && cvInput.files.length > 0) {
                const file = cvInput.files[0];
                const extension = (file.name.split('.').pop() || '').toLowerCase();
                const allowed = ['pdf', 'doc', 'docx'];

                if (!allowed.includes(extension)) {
                    errors.cv = 'Formats autorisés: PDF, DOC, DOCX.';
                } else if (file.size > 5 * 1024 * 1024) {
                    errors.cv = 'Le CV dépasse 5 Mo.';
                }
            }

            if (Object.keys(errors).length > 0) {
                event.preventDefault();

                Object.entries(errors).forEach(([field, error]) => {
                    setFieldError(field, error);
                });

                setAriaInvalid('offerIdField', Boolean(errors.offer_id));
                setAriaInvalid('experienceField', Boolean(errors.experience));
                setAriaInvalid('competencesField', Boolean(errors.competences));
                setAriaInvalid('cvField', Boolean(errors.cv));
                setAriaInvalid('messageField', Boolean(errors.message));
                return;
            }

            alert('Candidature envoyée avec succès !');
        });
    }
});