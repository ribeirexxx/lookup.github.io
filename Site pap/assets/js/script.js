document.addEventListener('DOMContentLoaded', () => {
    // Mobile Navigation Toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const links = document.querySelectorAll('.nav-links li');

    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');

        // Hamburger animation
        hamburger.classList.toggle('toggle');
    });

    // Close mobile menu when clicking a link
    links.forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
        });
    });

    // Scroll Reveal Animation
    const revealElements = document.querySelectorAll('.feature-card, .about-text, .about-image, .hero-content');

    const revealOnScroll = () => {
        const windowHeight = window.innerHeight;
        const elementVisible = 150;

        revealElements.forEach((element) => {
            const elementTop = element.getBoundingClientRect().top;

            if (elementTop < windowHeight - elementVisible) {
                element.classList.add('active');
                element.style.opacity = "1";
                element.style.transform = "translateY(0)";
            }
        });
    };

    // Initial styles for reveal elements (can also be done in CSS)
    revealElements.forEach(el => {
        el.style.opacity = "0";
        el.style.transform = "translateY(30px)";
        el.style.transition = "all 0.8s ease";
    });

    window.addEventListener('scroll', revealOnScroll);
    // Trigger once on load
    revealOnScroll();

    // --- DARK MODE TOGGLE ---
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    const icon = themeToggle ? themeToggle.querySelector('i') : null;

    if (themeToggle) {
        // Initialize icon based on current theme
        if (body.classList.contains('dark-mode')) {
            icon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');

            // Save preference
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            // Update icon
            if (isDark) {
                icon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
            } else {
                icon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
            }
        });
    }
});
