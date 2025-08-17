// Add this to your script.js file
document.addEventListener('DOMContentLoaded', function() {
    // Create a form around the filters if it doesn't exist
    const filters = document.querySelector('.post-filters');
    if(filters && !filters.closest('form')) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = window.location.pathname;
        filters.parentNode.insertBefore(form, filters);
        form.appendChild(filters);
    }
    
    // Add event listeners to all filter selects
    const filterSelects = document.querySelectorAll('.post-filters select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
});