const themeToggle = document.getElementById('themeToggle');
const siteLogo = document.getElementById('siteLogo');

function updateLogo() {
    if (!siteLogo) return;

    const lightLogo = siteLogo.dataset.light;
    const darkLogo = siteLogo.dataset.dark;
    const isDark = document.body.classList.contains('dark');

    siteLogo.src = isDark ? darkLogo : lightLogo;
}

function applySavedTheme() {
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
        if (themeToggle) themeToggle.textContent = '☀';
    } else {
        document.body.classList.remove('dark');
        if (themeToggle) themeToggle.textContent = '☾';
    }

    updateLogo();
}

applySavedTheme();

if (themeToggle) {
    themeToggle.addEventListener('click', function () {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        themeToggle.textContent = isDark ? '☀' : '☾';
        updateLogo();
    });
}