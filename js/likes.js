document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded');
    
    // Use event delegation to handle clicks on like buttons
    document.addEventListener('click', function(event) {
        // Check if clicked element or its parent has the like-btn class
        const likeBtn = event.target.closest('.like-btn');
        
        if (likeBtn) {
            event.preventDefault();
            const postId = likeBtn.dataset.postId;
            
            if (!postId) {
                console.error('No post ID found');
                return;
            }
            
            // Find elements within this specific like button
            const heartIcon = likeBtn.querySelector('.fa-heart');
            const likesCountElement = likeBtn.querySelector('span');
            
            if (!heartIcon || !likesCountElement) {
                console.error('Missing required elements:', { heartIcon, likesCountElement });
                return;
            }
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('post_id', postId);
            
            fetch('../ajax_handlers/like_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update the heart color based on like status
                    if (data.action === 'liked') {
                        heartIcon.style.color = 'var(--red)';
                    } else {
                        heartIcon.style.color = ''; // reset to default
                    }
                    
                    // Update like count - preserve parentheses format
                    likesCountElement.textContent = '(' + data.likes + ')';
                } else {
                    alert(data.message || 'Error processing like');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    });
    
    // Helper function to show messages (keep this for error messages)
    function showMessage(msg) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.innerHTML = `<span>${msg}</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i>`;
        
        document.body.prepend(messageDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
});