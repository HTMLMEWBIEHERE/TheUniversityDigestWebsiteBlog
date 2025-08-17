/**
 * University Digest Blog - Main JavaScript
 * This file contains all client-side functionality for the blog
 */

// Wait for DOM to be fully loaded
$(document).ready(function() {

    // ============================================
    // HEADER NAVIGATION & USER MENU FUNCTIONALITY
    // ============================================
    
    // Element selection
    const navbar = $('.header .flex .navbar');
    const searchForm = $('.header .flex .search-form');
    const profile = $('.header .flex .profile');
    const menuBtn = $('#menu-btn');
    const searchBtn = $('#search-btn');
    const userBtn = $('#user-btn');
    
    // Menu button click handler
    menuBtn.on('click', function() {
        navbar.toggleClass('active');
        searchForm.removeClass('active');
        profile.removeClass('active');
    });
    
    // Search button click handler
    searchBtn.on('click', function() {
        searchForm.toggleClass('active');
        navbar.removeClass('active');
        profile.removeClass('active');
    });
    
    // User button click handler
    userBtn.on('click', function(e) {
        e.stopPropagation();
        
        // Toggle dropdown visibility
        profile.toggleClass('active');
        searchForm.removeClass('active');
        navbar.removeClass('active');
        
        // Position dropdown relative to user button if visible
        if(profile.hasClass('active')) {
            const rect = userBtn[0].getBoundingClientRect();
            profile.css({
                'position': 'fixed',
                'top': (rect.bottom + 10) + 'px',
                'right': '20px',
                'z-index': 1000
            });
        }
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if(!$(e.target).closest('#user-btn').length && !$(e.target).closest('.profile').length) {
            profile.removeClass('active');
        }
    });
    
    // Hide all dropdowns when scrolling
    let lastScrollTop = 0;
    $(window).on('scroll', function() {
        const currentScroll = $(window).scrollTop();
        
        // Only trigger on significant scroll (not small movements)
        if(Math.abs(lastScrollTop - currentScroll) > 5) {
            profile.removeClass('active');
            navbar.removeClass('active');
            searchForm.removeClass('active');
        }
        
        lastScrollTop = currentScroll;
    });
    
    // ============================================
    // CONTENT FORMATTING
    // ============================================
    
    // Trim content to 150 characters
    $('.content-150').each(function() {
        const content = $(this).html();
        if(content.length > 150) {
            $(this).html(content.slice(0, 150) + '...');
        }
    });
    
    // ============================================
    // SEARCH FUNCTIONALITY 
    // ============================================
    
    $("#search").on("keyup", function() {
        const query = $(this).val();
        if (query.length > 2) {
            $.ajax({
                url: "search.php",
                method: "POST",
                data: { search: query },
                success: function(data) {
                    $("#search-results").html(data).show();
                }
            });
        } else {
            $("#search-results").hide();
        }
    });
    
    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-box').length) {
            $("#search-results").hide();
        }
    });
    
    // ============================================
    // CAROUSEL FUNCTIONALITY
    // ============================================
    
    let currentSlide = 0;
    
    function updateSlidePosition() {
        const carouselInner = $('.carousel-inner');
        const slideWidth = $('.carousel-item').first().width();
        carouselInner.css('transform', `translateX(-${currentSlide * slideWidth}px)`);
    }
    
    function moveSlide(n) {
        const slides = $('.carousel-item');
        currentSlide = (currentSlide + n + slides.length) % slides.length;
        updateSlidePosition();
        updateIndicators();
    }
    
    function goToSlide(n) {
        currentSlide = n;
        updateSlidePosition();
        updateIndicators();
    }
    
    function updateIndicators() {
        $('.indicator').each(function(index) {
            $(this).toggleClass('active', index === currentSlide);
        });
    }
    
    // Set up carousel controls
    $(document).ready(function() {
        $('.indicator').on('click', function() {
            goToSlide($(this).index());
        });
        
        $('.prev').on('click', function() { moveSlide(-1); });
        $('.next').on('click', function() { moveSlide(1); });
        
        updateSlidePosition();
        updateIndicators();
        
        // Auto-advance slides
        setInterval(function() { moveSlide(1); }, 5000);
    });
    
    // ============================================
    // AJAX DATA LOADING
    // ============================================
    
    // Load initial data for dashboard
    function initializeDashboard() {
        loadCarousel();
        loadAnnouncements();
        loadArticles();
        loadMagazines();
        loadTejidos();
    }
    
    // Try to initialize dashboard components if on dashboard page
    if($('#carousel-images').length || $('#announcements').length) {
        initializeDashboard();
    }
    
    function loadCarousel() {
        $.ajax({
            url: "./ajax/fetch_carousel.php",
            method: "GET",
            success: function(data) {
                $("#carousel-images").html(data.images);
                $("#carousel-indicators").html(data.indicators);
                updateIndicators();
            },
            dataType: "json"
        });
    }
    
    function loadAnnouncements() {
        $.ajax({
            url: "./ajax/fetch_announcements.php",
            method: "GET",
            success: function(data) {
                $("#announcements").html(data);
            }
        });
    }
    
    function loadArticles() {
        $.ajax({
            url: "./ajax/fetch_articles.php",
            method: "GET",
            success: function(data) {
                $("#articles").html(data);
            }
        });
    }
    
    function loadMagazines() {
        $.ajax({
            url: "./ajax/fetch_magazines.php",
            method: "GET",
            success: function(data) {
                $("#magazines").html(data);
            }
        });
    }
    
    function loadTejidos() {
        // Add implementation as needed
    }
    
    // ============================================
    // POST FILTERING
    // ============================================
    
    // Filter posts by category
    function filterPosts(category) {
        $('.card').each(function() {
            if (category === 'all' || $(this).data('category') === category) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Toggle category dropdown
    $('#dropdownBtn').on('click', function() {
        $('#dropdownContent').toggleClass('show');
    });
    
    // Close dropdown on outside click
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.filter-button').length) {
            $('#dropdownContent').removeClass('show');
        }
    });
    
    // Show all posts by default
    filterPosts('all');
});





