document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-grid');

    function setAdminFieldError(fieldName, message) {
        const node = document.querySelector(`[data-error-for="${fieldName}"]`);
        if (node) {
            node.textContent = message;
        }
    }

    function clearAdminFieldErrors() {
        document.querySelectorAll('.field-error').forEach((node) => {
            node.textContent = '';
        });
    }

    function setFieldInvalid(fieldId, isInvalid) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
        }
    }

    function updateActionButtons(row, selectedStatus) {
        row.querySelectorAll('.js-status-form').forEach((statusForm) => {
            const button = statusForm.querySelector('button[type="submit"]');
            if (!button) {
                return;
            }

            const targetStatus = statusForm.dataset.targetStatus || '';
            const isSelected = targetStatus === selectedStatus;

            button.disabled = isSelected;
            button.setAttribute('aria-disabled', isSelected ? 'true' : 'false');
            button.classList.toggle('is-selected', isSelected);

            if (targetStatus === 'acceptee') {
                button.textContent = isSelected ? 'Acceptée' : 'Accepter';
            } else if (targetStatus === 'refusee') {
                button.textContent = isSelected ? 'Refusée' : 'Refuser';
            }
        });
    }

    function setStatusChip(row, status) {
        const chip = row.querySelector('[data-status-chip]');
        if (!chip) {
            return;
        }

        chip.classList.remove('status-acceptee', 'status-refusee', 'status-en_attente');
        const normalized = ['acceptee', 'refusee'].includes(status) ? status : 'en_attente';
        chip.classList.add(`status-${normalized}`);

        if (normalized === 'acceptee') {
            chip.textContent = 'Acceptée';
        } else if (normalized === 'refusee') {
            chip.textContent = 'Refusée';
        } else {
            chip.textContent = 'En attente';
        }
    }

    const priceField = document.getElementById('prixField');
    if (priceField) {
        priceField.addEventListener('input', () => {
            // Keep only digits and one decimal separator (dot or comma).
            let value = priceField.value.replace(/[^0-9.,]/g, '');
            const firstSeparatorIndex = value.search(/[.,]/);
            if (firstSeparatorIndex !== -1) {
                const integerPart = value.slice(0, firstSeparatorIndex + 1);
                const decimalPart = value
                    .slice(firstSeparatorIndex + 1)
                    .replace(/[.,]/g, '')
                    .slice(0, 2);
                value = integerPart + decimalPart;
            }
            priceField.value = value;
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            clearAdminFieldErrors();

            const titre = document.getElementById('titreField')?.value.trim() ?? '';
            const typeService = document.getElementById('typeServiceField')?.value.trim() ?? '';
            const localisation = document.getElementById('localisationField')?.value.trim() ?? '';
            const dateExpiration = document.getElementById('date_expiration')?.value.trim() ?? '';
            const prix = document.getElementById('prixField')?.value.trim() ?? '';
            const description = document.getElementById('descriptionField')?.value.trim() ?? '';
            const allowedServicesInput = form.dataset.allowedServices || '[]';
            let allowedServices = [];

            try {
                allowedServices = JSON.parse(allowedServicesInput);
            } catch (_) {
                allowedServices = [];
            }

            const errors = {};
            const textRegex = /^[a-zA-ZÀ-ÿ\s]+$/;
            const priceRegex = /^\d+(?:[\.,]\d{1,2})?$/;

            if (titre.length < 3 || titre.length > 150) {
                errors.titre = 'Le titre doit contenir entre 3 et 150 caracteres.';
            } else if (!textRegex.test(titre)) {
                errors.titre = 'Le titre doit contenir uniquement des lettres.';
            }

            if (!allowedServices.includes(typeService)) {
                errors.type_service = 'Le type de service est obligatoire.';
            }

            if (localisation.trim() === '') {
                errors.localisation = 'La localisation est obligatoire.';
            } else {
                // Accept either address text or coordinates (lat,lng format)
                const coordinateRegex = /^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/;
                const isCoordinates = coordinateRegex.test(localisation);
                const isAddress = textRegex.test(localisation);
                
                if (!isCoordinates && !isAddress) {
                    errors.localisation = 'La localisation doit être une adresse ou des coordonnées valides.';
                } else if (localisation.length > 150) {
                    errors.localisation = 'La localisation ne doit pas depasser 150 caracteres.';
                }
            }

            if (dateExpiration) {
                const selected = new Date(`${dateExpiration}T00:00:00`);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selected < today) {
                    errors.date_expiration = 'La date d expiration doit etre aujourd hui ou dans le futur.';
                }
            }

            if (prix.trim() === '') {
                errors.prix = 'Le prix est obligatoire.';
            } else if (!priceRegex.test(prix)) {
                errors.prix = 'Le prix doit contenir uniquement des chiffres (ex: 120 ou 120.50).';
            } else {
                const numericPrice = Number(prix.replace(',', '.'));
                if (Number.isNaN(numericPrice) || numericPrice <= 0 || numericPrice > 1000000) {
                    errors.prix = 'Le prix doit etre superieur a 0 et inferieur a 1 000 000.';
                }
            }

            if (description.trim() === '') {
                errors.description = 'La description est obligatoire.';
            } else if (!textRegex.test(description)) {
                errors.description = 'La description doit contenir uniquement des lettres.';
            } else if (description.length > 2000) {
                errors.description = 'La description ne doit pas depasser 2000 caracteres.';
            }

            if (Object.keys(errors).length > 0) {
                event.preventDefault();
                Object.entries(errors).forEach(([field, error]) => {
                    setAdminFieldError(field, error);
                });
                setFieldInvalid('titreField', Boolean(errors.titre));
                setFieldInvalid('typeServiceField', Boolean(errors.type_service));
                setFieldInvalid('localisationField', Boolean(errors.localisation));
                setFieldInvalid('date_expiration', Boolean(errors.date_expiration));
                setFieldInvalid('prixField', Boolean(errors.prix));
                setFieldInvalid('descriptionField', Boolean(errors.description));
                return;
            }

            alert('Offre enregistrée avec succès !');
        });
    }

    document.querySelectorAll('.js-status-form').forEach((statusForm) => {
        statusForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const row = this.closest('tr');
            if (!row) {
                this.submit();
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            if (!submitButton || submitButton.disabled) {
                return;
            }

            const originalLabel = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = '...';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const rawText = await response.text();
                let data;

                try {
                    data = JSON.parse(rawText);
                } catch (_) {
                    const start = rawText.lastIndexOf('{');
                    const end = rawText.lastIndexOf('}');
                    if (start !== -1 && end !== -1 && end > start) {
                        data = JSON.parse(rawText.slice(start, end + 1));
                    } else {
                        throw new Error('Reponse serveur invalide.');
                    }
                }

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'Mise à jour impossible.');
                }

                const statusValue = String(data.status || '').toLowerCase().trim();
                const normalized = (statusValue === 'acceptee' || statusValue === 'accepted')
                    ? 'acceptee'
                    : (statusValue === 'refusee' || statusValue === 'rejetee' || statusValue === 'rejected'
                        ? 'refusee'
                        : 'en_attente');

                setStatusChip(row, normalized);
                updateActionButtons(row, normalized);
            } catch (error) {
                submitButton.disabled = false;
                submitButton.textContent = originalLabel;
                alert(error.message || 'Une erreur est survenue.');
            }
        });
    });

});