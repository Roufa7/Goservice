document.querySelectorAll(".reveal").forEach((el) => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) entry.target.classList.add("show");
        });
    }, { threshold: 0.15 });
    observer.observe(el);
});

document.querySelectorAll(".auto-slider").forEach((slider) => {
    const track = slider.querySelector(".auto-slider-track");
    const slides = slider.querySelectorAll(".auto-slide");
    let index = 0;

    if (!track || slides.length === 0) return;

    setInterval(() => {
        index = (index + 1) % slides.length;
        track.style.transform = `translateX(-${index * 100}%)`;
    }, 2500);
});

// Contrôle de saisie
document.addEventListener("DOMContentLoaded", function() {
    const errorSaisie = (input, message) => {
        let errorElem = input.parentElement.querySelector(".error-msg");
        if (!errorElem) {
            errorElem = document.createElement("small");
            errorElem.className = "error-msg";
            errorElem.style.color = "#d9534f";
            errorElem.style.display = "block";
            errorElem.style.marginTop = "6px";
            errorElem.style.fontWeight = "bold";
            input.parentElement.appendChild(errorElem);
        }
        errorElem.innerText = message;
        input.style.borderColor = "#d9534f";
    };

    const clearError = (input) => {
        let errorElem = input.parentElement.querySelector(".error-msg");
        if (errorElem) errorElem.remove();
        input.style.borderColor = ""; 
    };

    const authForms = document.querySelectorAll(".auth-form");
    authForms.forEach(form => {
        form.addEventListener("submit", function(e) {
            let isValid = true;

            const emailInput = form.querySelector("input[name='email']");
            const pwInput = form.querySelector("input[name='password']");
            const nomInput = form.querySelector("input[name='nom']");
            const prenomInput = form.querySelector("input[name='prenom']");
            const telInput = form.querySelector("input[name='telephone']");

            // Email validation
            if (emailInput && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                errorSaisie(emailInput, "Veuillez entrer une adresse email valide.");
                isValid = false;
            }

            // Password strict check ONLY for register forms
            if (pwInput && form.action.includes('register')) {
                if (pwInput.value.length < 8) {
                    errorSaisie(pwInput, "Le mot de passe doit contenir au moins 8 caractères.");
                    isValid = false;
                }
            } else if (pwInput && pwInput.value.trim() === "") {
                errorSaisie(pwInput, "Le mot de passe est obligatoire.");
                isValid = false;
            }

            // Full names check
            if (nomInput && !/^[A-Za-zÀ-ÿ\s]{2,}$/.test(nomInput.value.trim())) {
                errorSaisie(nomInput, "Le nom doit contenir que des lettres (min 2).");
                isValid = false;
            }
            if (prenomInput && !/^[A-Za-zÀ-ÿ\s]{2,}$/.test(prenomInput.value.trim())) {
                errorSaisie(prenomInput, "Le prénom doit contenir que des lettres (min 2).");
                isValid = false;
            }

            // Phone number (tunisian rule: 8 digits)
            if (telInput && telInput.value.trim() !== "") {
                if (!/^[0-9]{8}$/.test(telInput.value.trim())) {
                    errorSaisie(telInput, "Le numéro doit comporter exactement 8 chiffres.");
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Dynamic clean up on type
        form.querySelectorAll("input").forEach(input => {
            input.addEventListener("input", () => clearError(input));
        });
    });

    // Enhanced Number Counting Animation
    const counters = document.querySelectorAll('.stat-animate');
    if (counters.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = +el.getAttribute('data-target');
                    el.innerText = '0';
                    el.classList.add('animating');
                    
                    const animate = () => {
                        const current = +el.innerText;
                        const step = Math.ceil(target / 40) || 1;
                        if (current < target) {
                            el.innerText = Math.min(current + step, target);
                            requestAnimationFrame(animate);
                        } else {
                            el.classList.remove('animating');
                            el.classList.add('finished');
                        }
                    };
                    // Delay slightly for visual impact
                    setTimeout(animate, 200);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.1 });
        counters.forEach(c => observer.observe(c));
    }
});