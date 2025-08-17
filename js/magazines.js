// Magazine Carousel Script
let currentSlide = 0;
const magazineTrack = document.querySelector('.magazine-track');
const magazineCards = document.querySelectorAll('.magazine-card');
const totalSlides = magazineCards.length;

// Function to move the carousel
function moveMagazineSlide(direction) {
    currentSlide += direction;

    // If the current slide exceeds the total, reset to the first slide
    if (currentSlide >= totalSlides) {
        currentSlide = 0;
    }

    // If the current slide is less than 0, go to the last slide
    if (currentSlide < 0) {
        currentSlide = totalSlides - 1;
    }

    // Move the magazine track
    const slideWidth = magazineCards[0].offsetWidth;
    magazineTrack.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
}

// Optional: Auto-slide every 5 seconds
setInterval(() => {
    moveMagazineSlide(1);
}, 5000);
