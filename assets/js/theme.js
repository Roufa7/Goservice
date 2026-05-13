const themeToggle = document.getElementById('themeToggle');
const siteLogo = document.getElementById('siteLogo');

function updateLogo() {
    if (!siteLogo) return;
    const lightLogo = siteLogo.dataset.light;
    const darkLogo = siteLogo.dataset.dark;
    const isDark = document.body.classList.contains('dark');
    siteLogo.src = isDark ? darkLogo : lightLogo;
}

function updateThemeToggle() {
    if (!themeToggle) return;
    const isDark = document.body.classList.contains('dark');
    themeToggle.textContent = isDark ? '☀' : '☾';
    themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    themeToggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
}

function applySavedTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
    } else {
        document.body.classList.remove('dark');
    }
    updateThemeToggle();
    updateLogo();
}

applySavedTheme();

if (themeToggle) {
    themeToggle.addEventListener('click', function () {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeToggle();
        updateLogo();
    });
}