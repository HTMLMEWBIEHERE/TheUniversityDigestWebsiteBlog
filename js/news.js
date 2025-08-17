document.addEventListener('DOMContentLoaded', function() {
    // Get all toggle buttons
    const toggleButtons = document.querySelectorAll('.toggle-content');
    
    // Add click event to each button
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get the article card
            const articleCard = this.closest('.card-article');
            
            // Get the short and full content elements
            const shortContent = articleCard.querySelector('.short-content');
            const fullContent = articleCard.querySelector('.card-article-full-content');
            
            // Toggle visibility
            if (fullContent.style.display === 'none') {
                // Show full content
                shortContent.style.display = 'none';
                fullContent.style.display = 'block';
                this.textContent = 'Show Less';
            } else {
                // Show short content
                shortContent.style.display = 'block';
                fullContent.style.display = 'none';
                this.textContent = 'Read More';
            }
        });
    });
});