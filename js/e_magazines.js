/**
 * E-Magazine Login Popup Functionality
 * Handles the login requirement popup for non-logged-in users
 */
document.addEventListener('DOMContentLoaded', function() {
    const loginRequired = document.querySelectorAll('.login-required');
    const overlay = document.getElementById('overlay');
    const loginMessage = document.getElementById('login-message');
    const closeLogin = document.getElementById('close-login');
    
    // Show login message when clicking on restricted links
    if (loginRequired.length > 0) {
        loginRequired.forEach(function(link) {
            link.addEventListener('click', function() {
                overlay.style.display = 'block';
                loginMessage.style.display = 'block';
            });
        });
    }
    
    // Close login message when clicking X button
    if (closeLogin) {
        closeLogin.addEventListener('click', function() {
            overlay.style.display = 'none';
            loginMessage.style.display = 'none';
        });
    }
    
    // Also close when clicking on overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            overlay.style.display = 'none';
            loginMessage.style.display = 'none';
        });
    }
});