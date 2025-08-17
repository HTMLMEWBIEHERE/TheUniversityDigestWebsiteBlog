/**
 * University Digest - Header JavaScript
 * Enhanced with better error handling and debugging
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Header JS loaded"); // Debug
    
    // Flexible element selection - tries multiple possible selectors
    const userBtn = document.getElementById('user-btn') || document.getElementById('ud-user-btn');
    const profileDropdown = document.querySelector('.profile') || document.querySelector('.ud-profile');
    const header = document.querySelector('header') || document.querySelector('.ud-header');
    const headerRight = document.querySelector('.header-right') || document.querySelector('.ud-header-right');
    
    // Debug what we found
    console.log("User button found:", !!userBtn);
    console.log("Profile dropdown found:", !!profileDropdown);
    console.log("Header found:", !!header);
    console.log("Header right found:", !!headerRight);
    
    // Only proceed if we found the required elements
    if (!header || !headerRight) {
        console.error("Critical header elements not found");
        return;
    }
    
    // Find existing mobile toggle or create one
    let mobileMenuToggle = document.querySelector('.mobile-menu-toggle') || 
                          document.querySelector('.ud-mobile-menu-toggle');
                          
    if (!mobileMenuToggle && header) {
        mobileMenuToggle = document.createElement('div');
        mobileMenuToggle.className = 'mobile-menu-toggle ud-mobile-menu-toggle'; // Use both classes
        mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        header.insertBefore(mobileMenuToggle, headerRight);
    }
    
    // Mobile menu toggle functionality (if elements exist)
    if (mobileMenuToggle && headerRight) {
        mobileMenuToggle.addEventListener('click', function() {
            headerRight.classList.toggle('active');
            // Change icon based on state
            const icon = this.querySelector('i');
            if (icon) {
                if (headerRight.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
    
    // User profile dropdown functionality
    if (userBtn && profileDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
            console.log("Profile toggled:", profileDropdown.classList.contains('active')); // Debug
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (profileDropdown.classList.contains('active')) {
                if (!e.target.closest('#user-btn') && 
                    !e.target.closest('#ud-user-btn') && 
                    !e.target.closest('.profile') && 
                    !e.target.closest('.ud-profile')) {
                    
                    profileDropdown.classList.remove('active');
                }
            }
        });
    } else {
        console.error("User button or profile dropdown not found");
    }
    
    // Simplified scroll handling
    if (profileDropdown || headerRight) {
        window.addEventListener('scroll', function() {
            if (profileDropdown && profileDropdown.classList.contains('active')) {
                profileDropdown.classList.remove('active');
            }
            
            if (headerRight && headerRight.classList.contains('active') && mobileMenuToggle) {
                headerRight.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }, { passive: true });
    }
});