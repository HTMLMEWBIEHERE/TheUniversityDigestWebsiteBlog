// Hero Carousel JavaScript with enhanced animations
let slideIndex = 0;
const slides = document.querySelectorAll('.hero-carousel-item');
const indicators = document.querySelectorAll('.hero-indicator');
let slideshowPaused = false;

// Initialize carousel
function showSlides() {
    if (slideshowPaused) return;
    
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        slides[i].classList.remove("active");
        indicators[i].classList.remove("active");
    }
    
    // Show current slide
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}
    
    slides[slideIndex-1].style.display = "block";
    
    // Small delay to trigger animation
    setTimeout(() => {
        slides[slideIndex-1].classList.add("active");
        indicators[slideIndex-1].classList.add("active");
    }, 50);
    
    // Change slide every 5 seconds
    setTimeout(showSlides, 5000);
}

// Manual navigation with animation handling
function moveSlide(n) {
    // Temporarily pause the automatic slideshow
    slideshowPaused = true;
    
    showSlide(slideIndex += n);
    
    // Resume the slideshow after a delay
    setTimeout(() => {
        slideshowPaused = false;
    }, 5000);
}

function goToSlide(n) {
    // Temporarily pause the automatic slideshow
    slideshowPaused = true;
    
    showSlide(slideIndex = n);
    
    // Resume the slideshow after a delay
    setTimeout(() => {
        slideshowPaused = false;
    }, 5000);
}

function showSlide(n) {
    if (n >= slides.length) {slideIndex = 0}
    if (n < 0) {slideIndex = slides.length - 1}
    
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        slides[i].classList.remove("active");
        indicators[i].classList.remove("active");
    }
    
    // Show current slide with animation
    slides[slideIndex].style.display = "block";
    
    // Small delay to trigger animation
    setTimeout(() => {
        slides[slideIndex].classList.add("active");
        indicators[slideIndex].classList.add("active");
    }, 50);
}

// Add intersection observer for animation on scroll
document.addEventListener('DOMContentLoaded', () => {
    // Start the carousel
    showSlides();
    
    // Set up Intersection Observer for scroll animations
    const animatedElements = document.querySelectorAll('.purpose-card, .about-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Add animation class when element is in view
            if (entry.isIntersecting) {
                entry.target.style.visibility = 'visible';
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                
                // Unobserve after animation is triggered
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Set initial state and observe elements
    animatedElements.forEach(element => {
        element.style.visibility = 'hidden';
        element.style.opacity = '0';
        element.style.transform = 'translateY(50px)';
        element.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        observer.observe(element);
    });
});

// For your existing functions
// USER DASHBOARD 
if (typeof loadCarousel === 'function') {
    loadCarousel();
}
if (typeof loadAnnouncements === 'function') {
    loadAnnouncements();
}
if (typeof loadArticles === 'function') {
    loadArticles();
}
if (typeof loadMagazines === 'function') {
    loadMagazines();
}
if (typeof loadTejidos === 'function') {
    loadTejidos();
}

// Load carousel images
function loadCarousel() {
    $.ajax({
        url: "./ajax/fetch_carousel.php",
        method: "GET",
        success: function (data) {
            $("#carousel-images").html(data.images);
            $("#carousel-indicators").html(data.indicators);
            // Reinitialize carousel indicators after loading new content
            updateIndicators();
        },
        dataType: "json"
    });
}

