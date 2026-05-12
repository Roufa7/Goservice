const bindDateRange = (startSelector, endSelector) => {
    document.querySelectorAll(startSelector).forEach((startInput) => {
        const scope = startInput.form || document;
        const endInput = scope.querySelector(endSelector);
        if (!endInput) {
            return;
        }

        const syncEndConstraint = () => {
            if (startInput.value) {
                endInput.min = startInput.value;
            } else {
                endInput.removeAttribute("min");
            }

            if (endInput.value && startInput.value && endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
        };

        startInput.addEventListener("change", syncEndConstraint);
        syncEndConstraint();
    });
};

bindDateRange("[id='date_debut']", "[id='date_fin']");
bindDateRange("input[type='date'][name='date_from']", "input[type='date'][name='date_to']");
bindDateRange("input[type='date'][name='participant_date_from']", "input[type='date'][name='participant_date_to']");

document.querySelectorAll("input[type='file'][name='image']").forEach((input) => {
    input.addEventListener("change", () => {
        const preview = input.closest(".event-admin-form-panel")?.querySelector(".event-inline-image img");
        const file = input.files?.[0];

        if (!preview || !file) {
            return;
        }

        preview.src = URL.createObjectURL(file);
    });
});

const targetHash = window.location.hash;
if (targetHash) {
    const targetElement = document.querySelector(targetHash);
    if (targetElement) {
        setTimeout(() => {
            targetElement.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 120);
    }
}

const firstErrorField = document.querySelector(".field-block-error input, .field-block-error select, .field-block-error textarea");
if (firstErrorField) {
    setTimeout(() => {
        firstErrorField.focus({ preventScroll: true });
    }, 180);
}

const pageLoader = document.getElementById("eventPageLoader");
const pageLoaderTitle = pageLoader?.querySelector(".event-page-loader-card strong");
const pageLoaderMessage = pageLoader?.querySelector(".event-page-loader-card span:last-of-type");
const defaultLoaderCopy = {
    title: pageLoaderTitle?.textContent?.trim() || "Chargement",
    message: pageLoaderMessage?.textContent?.trim() || "Preparation en cours.",
};

const loaderMessages = {
    create_event: {
        title: "Enregistrement de l'evenement",
        message: "Creation de la fiche evenement et mise a jour des indicateurs.",
    },
    update_event: {
        title: "Mise a jour de l'evenement",
        message: "Enregistrement des modifications et actualisation du tableau.",
    },
    delete_event: {
        title: "Suppression de l'evenement",
        message: "Suppression de la fiche et nettoyage des donnees liees.",
    },
    create_participation: {
        title: "Envoi de l'inscription",
        message: "Verification de la demande et preparation du recu participant.",
    },
    update_participation: {
        title: "Mise a jour du participant",
        message: "Enregistrement des changements sur la participation en cours.",
    },
    delete_participation: {
        title: "Suppression de la participation",
        message: "Retrait de l'inscription et actualisation de la liste.",
    },
    set_participation_status: {
        title: "Mise a jour du statut",
        message: "Application du nouveau statut de participation.",
    },
    bulk_participation_status: {
        title: "Traitement en lot",
        message: "Mise a jour des participations de cet evenement en cours.",
    },
    send_participation_email: {
        title: "Envoi de l'email",
        message: "Preparation et envoi du message de confirmation.",
    },
    ai_improve_description: {
        title: "Assistant IA local",
        message: "Reformulation de la description en cours avec Ollama.",
    },
    ai_generate_promo: {
        title: "Assistant IA local",
        message: "Generation de la version promotionnelle avec Ollama.",
    },
    ai_suggest_title: {
        title: "Assistant IA local",
        message: "Recherche d'un meilleur titre avec Ollama.",
    },
    ai_analyze_event: {
        title: "Assistant IA local",
        message: "Analyse de la fiche evenement en cours avec Ollama.",
    },
};

const setLoaderCopy = (copy) => {
    if (pageLoaderTitle) {
        pageLoaderTitle.textContent = copy.title || defaultLoaderCopy.title;
    }
    if (pageLoaderMessage) {
        pageLoaderMessage.textContent = copy.message || defaultLoaderCopy.message;
    }
};

const showLoader = (copy = defaultLoaderCopy) => {
    if (pageLoader) {
        setLoaderCopy(copy);
        pageLoader.hidden = false;
        pageLoader.classList.add("event-page-loader-visible");
        pageLoader.setAttribute("aria-hidden", "false");
    }
};

const hideLoader = () => {
    if (pageLoader) {
        pageLoader.classList.remove("event-page-loader-visible");
        pageLoader.setAttribute("aria-hidden", "true");
        window.setTimeout(() => {
            pageLoader.hidden = true;
            setLoaderCopy(defaultLoaderCopy);
        }, 220);
    }
};

const getLoaderAction = (form, submitter) => {
    if (submitter?.dataset.eventAction) {
        return submitter.dataset.eventAction;
    }

    const hiddenAction = form.querySelector("input[name='event_action']");
    return hiddenAction?.value || "";
};

if (pageLoader) {
    window.addEventListener("pageshow", hideLoader);

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (event) => {
            const method = (form.getAttribute("method") || "get").toLowerCase();
            if (method !== "post") {
                return;
            }

            const action = getLoaderAction(form, event.submitter);
            showLoader(loaderMessages[action] || defaultLoaderCopy);
        });
    });
}
const liveRules = {
    titre: (value) => value.trim().length >= 3 && value.trim().length <= 150
        ? { valid: true, message: "Titre correct." }
        : { valid: false, message: "Le titre doit contenir entre 3 et 150 caracteres." },
    lieu: (value) => value.trim().length >= 2 && value.trim().length <= 150
        ? { valid: true, message: "Lieu valide." }
        : { valid: false, message: "Le lieu doit contenir entre 2 et 150 caracteres." },
    description: (value) => value.trim().length >= 12
        ? { valid: true, message: "Description suffisante." }
        : { valid: false, message: "Ajoutez une description plus detaillee." },
    nb_places: (value) => /^\d+$/.test(value) && Number(value) > 0
        ? { valid: true, message: "Nombre de places valide." }
        : { valid: false, message: "Le nombre de places doit etre un entier positif." },
    nom_participant: (value) => value.trim().length >= 3
        ? { valid: true, message: "Nom correct." }
        : { valid: false, message: "Le nom complet doit contenir au moins 3 caracteres." },
    email_participant: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
        ? { valid: true, message: "Adresse email valide." }
        : { valid: false, message: "Entrez une adresse email valide." },
    telephone: (value) => /^[0-9+\s()-]{8,20}$/.test(value.trim())
        ? { valid: true, message: "Telephone valide." }
        : { valid: false, message: "Entrez un numero entre 8 et 20 caracteres." },
    type_evenement: (value) => value.trim() !== ""
        ? { valid: true, message: "Type selectionne." }
        : { valid: false, message: "Choisissez un type d'evenement." },
    statut: (value) => value.trim() !== ""
        ? { valid: true, message: "Statut selectionne." }
        : { valid: false, message: "Choisissez un statut." },
    statut_participation: (value) => value.trim() !== ""
        ? { valid: true, message: "Statut selectionne." }
        : { valid: false, message: "Choisissez un statut de participation." },
    date_debut: (value) => value.trim() !== ""
        ? { valid: true, message: "Date de debut valide." }
        : { valid: false, message: "Renseignez une date de debut." },
    date_fin: (value, field) => {
        const startInput = field.form?.querySelector("[name='date_debut']");
        const startValue = startInput?.value || "";
        if (value.trim() === "") {
            return { valid: false, message: "Renseignez une date de fin." };
        }
        if (startValue && value < startValue) {
            return { valid: false, message: "La date de fin doit etre apres la date de debut." };
        }
        return { valid: true, message: "Date de fin valide." };
    },
};

const setLiveState = (fieldBlock, state) => {
    if (!fieldBlock) {
        return;
    }

    let hint = fieldBlock.querySelector(".event-live-hint");
    if (!hint) {
        hint = document.createElement("small");
        hint.className = "event-live-hint";
        fieldBlock.appendChild(hint);
    }

    fieldBlock.classList.remove("field-block-live-valid", "field-block-live-invalid");
    if (!state) {
        hint.textContent = "";
        return;
    }

    hint.textContent = state.message;
    fieldBlock.classList.add(state.valid ? "field-block-live-valid" : "field-block-live-invalid");
};

document.querySelectorAll(".event-form-grid input, .event-form-grid select, .event-admin-form-grid input, .event-admin-form-grid select, .event-admin-form-grid textarea").forEach((field) => {
    const fieldName = field.getAttribute("name");
    const rule = fieldName ? liveRules[fieldName] : null;
    if (!rule) {
        return;
    }

    const fieldBlock = field.closest(".field-block");
    const validate = () => {
        const value = field.value || "";
        if (value.trim() === "") {
            setLiveState(fieldBlock, null);
            return;
        }

        setLiveState(fieldBlock, rule(value, field));
    };

    field.addEventListener("input", validate);
    field.addEventListener("change", validate);
});

const eventActionField = document.getElementById("event_action_field");
if (eventActionField) {
    document.querySelectorAll("[data-event-action]").forEach((button) => {
        button.addEventListener("click", () => {
            eventActionField.value = button.getAttribute("data-event-action") || eventActionField.value;
        });
    });
}

