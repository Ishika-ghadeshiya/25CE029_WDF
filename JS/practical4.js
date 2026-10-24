
document.addEventListener("DOMContentLoaded", function () {

    // 1. Collapsible FAQ
    document.querySelectorAll(".faq-question").forEach(function (button) {
        button.addEventListener("click", function () {
            const answer = button.nextElementSibling;
            const isOpen = answer.classList.toggle("show");
            button.setAttribute("aria-expanded", isOpen);
        });
    });

    // 2. Modal popup
    const modal = document.getElementById("helpModal");
    const openModal = document.getElementById("openModal");
    const closeModal = document.getElementById("closeModal");

    if (modal && openModal && closeModal) {
        openModal.addEventListener("click", () => modal.classList.add("show"));
        closeModal.addEventListener("click", () => modal.classList.remove("show"));

        modal.addEventListener("click", function (event) {
            if (event.target === modal) modal.classList.remove("show");
        });
    }

    // 3. Content slider
    const slides = document.querySelectorAll(".slide");
    let currentSlide = 0;

    function showSlide(index) {
        if (!slides.length) return;
        currentSlide = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle("active", i === currentSlide);
        });
    }

    document.getElementById("prevSlide")?.addEventListener("click", () => {
        showSlide(currentSlide - 1);
    });

    document.getElementById("nextSlide")?.addEventListener("click", () => {
        showSlide(currentSlide + 1);
    });

    showSlide(0);

    // 4. Dismissible notification
    document.getElementById("closeNotice")?.addEventListener("click", () => {
        document.getElementById("notice")?.remove();
    });

    // 5. Hamburger menu
    const menuButton = document.getElementById("menuToggle");
    const navMenu = document.getElementById("navMenu");

    if (menuButton && navMenu) {
        menuButton.addEventListener("click", () => {
            const isOpen = navMenu.classList.toggle("menu-open");
            menuButton.setAttribute("aria-expanded", isOpen);
        });
    }

    // 6. Light/Dark theme
    const themeButton = document.getElementById("themeToggle");

    if (themeButton) {
        themeButton.addEventListener("click", () => {
            const isDark = document.body.classList.toggle("dark-theme");
            themeButton.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
        });
    }

});
