(function () {
    const loader = document.getElementById("sharedLoader");
    if (!loader) {
        return;
    }

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", () => {
            loader.classList.add("shared-loader-visible");
        });
    });

    window.addEventListener("beforeunload", () => {
        loader.classList.add("shared-loader-visible");
    });

    window.addEventListener("pageshow", () => {
        loader.classList.remove("shared-loader-visible");
    });
})();
