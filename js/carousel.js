    let currentSlide = 0;
    let slideInterval;

    const slides = document.querySelectorAll('.hero-carousel-inner img');
    const indicators = document.querySelectorAll('.hero-indicator');

    function showSlide(index) {
        if (slides.length === 0) return;

        currentSlide = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.style.display = (i === currentSlide) ? 'block' : 'none';
        });

        indicators.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentSlide);
        });
    }

    function moveSlide(direction) {
        showSlide(currentSlide + direction);
        resetAutoSlide();
    }

    function goToSlide(index) {
        showSlide(index);
        resetAutoSlide();
    }

    function startAutoSlide() {
        slideInterval = setInterval(() => {
            moveSlide(1);
        }, 5000); // change slide every 5 seconds
    }

    function resetAutoSlide() {
        clearInterval(slideInterval);
        startAutoSlide();
    }

    document.addEventListener('DOMContentLoaded', () => {
        showSlide(currentSlide);
        startAutoSlide();
    });
