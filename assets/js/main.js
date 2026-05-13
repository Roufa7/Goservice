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