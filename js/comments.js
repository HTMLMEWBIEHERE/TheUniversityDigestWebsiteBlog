// Comment handling functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if we've already initialized comment handling to prevent duplicate execution
    if (window.commentHandlingInitialized) {
        return;
    }
    window.commentHandlingInitialized = true;
    
    // Get post ID from the hidden input in the comment form
    const postIdInput = document.querySelector('input[name="post_id"]');
    if (!postIdInput) return;
    
    const postId = postIdInput.value;
    
    // Function to fetch and display comment count
    function updateCommentCount() {
        const formData = new FormData();
        formData.append('action', 'get_comment_count');
        formData.append('post_id', postId);
        
        fetch('../ajax_handlers/comment_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const limitMsg = document.getElementById('comment-limit-message');
                if (limitMsg) {
                    const remaining = data.comments_remaining;
                    
                    if (remaining <= 0) {
                        limitMsg.innerHTML = '<span class="comment-limit-warning">You have reached the maximum comment limit for this post.</span>';
                    } else {
                        limitMsg.textContent = `You have ${remaining} comment${remaining !== 1 ? 's' : ''} remaining for this post.`;
                    }
                }
            }
        })
        .catch(error => console.error('Error fetching comment count:', error));
    }
    
    // Update the comment count initially
    updateCommentCount();
    
    // Function to show notification message
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = 'message';
        notification.innerHTML = `<span>${message}</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i>`;
        document.body.insertBefore(notification, document.body.firstChild);
        setTimeout(() => notification.remove(), 5000);
    }
    
    // Function to check if comments container is empty and show message
    function checkEmptyComments() {
        const commentsContainer = document.querySelector('.user-comments-container');
        if (commentsContainer && commentsContainer.querySelectorAll('.show-comments').length === 0) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'empty';
            emptyMessage.textContent = 'No comments added yet!';
            commentsContainer.appendChild(emptyMessage);
        }
    }
    
    // Convert delete buttons to use AJAX
    document.querySelectorAll('button[name="delete_comment"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Confirm deletion
            if(!confirm('Delete this comment?')) {
                return;
            }
            
            const commentId = this.closest('form').querySelector('input[name="comment_id"]').value;
            
            const formData = new FormData();
            formData.append('action', 'delete_comment');
            formData.append('comment_id', commentId);
            
            fetch('../ajax_handlers/comment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove the comment from the DOM
                    this.closest('.show-comments').remove();
                    
                    // Check if comments container is now empty
                    checkEmptyComments();
                    
                    // Update the comment limit display directly with the returned data
                    const limitMsg = document.getElementById('comment-limit-message');
                    const remaining = data.comments_remaining;
                    
                    if (remaining <= 0) {
                        limitMsg.innerHTML = '<span class="comment-limit-warning">You have reached the maximum comment limit for this post.</span>';
                    } else {
                        limitMsg.textContent = `You have ${remaining} comment${remaining !== 1 ? 's' : ''} remaining for this post.`;
                    }
                    
                    // Update comment count in post info
                    const commentCountElem = document.querySelector('.icons div:first-child span');
                    if (commentCountElem) {
                        let currentCount = parseInt(commentCountElem.textContent.match(/\d+/)[0] || '0');
                        commentCountElem.textContent = `(${currentCount - 1})`;
                    }
                    
                    showNotification('Comment deleted successfully!');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while deleting the comment.', 'error');
            });
        });
    });
    
    // Convert edit buttons to use AJAX
    document.querySelectorAll('button[name="open_edit_box"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const commentId = this.closest('form').querySelector('input[name="comment_id"]').value;
            const commentContainer = this.closest('.show-comments');
            const commentTextElement = commentContainer.querySelector('.comment-box');
            const commentText = commentTextElement.textContent.trim();
            
            // Create edit form inline
            const originalContent = commentContainer.innerHTML;
            commentContainer.innerHTML = `
                <div class="edit-comment-form">
                    <p>Edit Your Comment</p>
                    <textarea class="edit-comment-textarea" maxlength="1000" rows="6">${commentText}</textarea>
                    <div class="edit-buttons">
                        <button class="inline-btn save-edit">Save Changes</button>
                        <button class="inline-option-btn cancel-edit">Cancel</button>
                    </div>
                </div>
            `;
            
            // Handle save edit button
            commentContainer.querySelector('.save-edit').addEventListener('click', function() {
                const editedText = commentContainer.querySelector('.edit-comment-textarea').value.trim();
                
                if (!editedText) {
                    showNotification('Comment cannot be empty', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'edit_comment');
                formData.append('comment_id', commentId);
                formData.append('comment', editedText);
                
                fetch('../ajax_handlers/comment_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update the comment text
                        commentContainer.innerHTML = originalContent;
                        commentContainer.querySelector('.comment-box').textContent = editedText;
                        showNotification('Comment updated successfully!');
                        
                        // Re-attach event listeners to the restored buttons - THIS WAS MISSING
                        attachEventListenersToCommentButtons(commentContainer);
                    } else {
                        showNotification(data.message, 'error');
                        commentContainer.innerHTML = originalContent;
                        
                        // Re-attach event listeners here too - THIS WAS MISSING
                        attachEventListenersToCommentButtons(commentContainer);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while updating the comment.', 'error');
                    commentContainer.innerHTML = originalContent;
                    
                    // And here as well - THIS WAS MISSING
                    attachEventListenersToCommentButtons(commentContainer);
                });
            });
            
            // Handle cancel button
            commentContainer.querySelector('.cancel-edit').addEventListener('click', function() {
                commentContainer.innerHTML = originalContent;
                
                // Re-attach event listeners to the restored buttons
                attachEventListenersToCommentButtons(commentContainer);
            });
        });
    });
    
    // Function to attach event listeners to comment buttons
    function attachEventListenersToCommentButtons(container) {
        const editBtn = container.querySelector('button[name="open_edit_box"]');
        const deleteBtn = container.querySelector('button[name="delete_comment"]');
        
        if (editBtn) {
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Same edit logic as above
                const commentId = this.closest('form').querySelector('input[name="comment_id"]').value;
                const commentContainer = this.closest('.show-comments');
                const commentTextElement = commentContainer.querySelector('.comment-box');
                const commentText = commentTextElement.textContent.trim();
                
                // Create edit form inline
                const originalContent = commentContainer.innerHTML;
                commentContainer.innerHTML = `
                    <div class="edit-comment-form">
                        <p>Edit Your Comment</p>
                        <textarea class="edit-comment-textarea" maxlength="1000" rows="6">${commentText}</textarea>
                        <div class="edit-buttons">
                            <button class="inline-btn save-edit">Save Changes</button>
                            <button class="inline-option-btn cancel-edit">Cancel</button>
                        </div>
                    </div>
                `;
                
                // Add save and cancel handlers
                handleEditCommentButtons(commentContainer, originalContent, commentId);
            });
        }
        
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Delete this comment?')) {
                    deleteComment(this);
                }
            });
        }
    }
    
    // Helper function for handling edit comment buttons
    function handleEditCommentButtons(container, originalContent, commentId) {
        container.querySelector('.save-edit').addEventListener('click', function() {
            saveEditedComment(container, originalContent, commentId);
        });
        
        container.querySelector('.cancel-edit').addEventListener('click', function() {
            container.innerHTML = originalContent;
            attachEventListenersToCommentButtons(container);
        });
    }
    
    // Helper function for saving edited comment
    function saveEditedComment(container, originalContent, commentId) {
        const editedText = container.querySelector('.edit-comment-textarea').value.trim();
        
        if (!editedText) {
            showNotification('Comment cannot be empty', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'edit_comment');
        formData.append('comment_id', commentId);
        formData.append('comment', editedText);
        
        fetch('../ajax_handlers/comment_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                container.innerHTML = originalContent;
                container.querySelector('.comment-box').textContent = editedText;
                showNotification('Comment updated successfully!');
                attachEventListenersToCommentButtons(container);
            } else {
                showNotification(data.message, 'error');
                container.innerHTML = originalContent;
                attachEventListenersToCommentButtons(container);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating the comment.', 'error');
            container.innerHTML = originalContent;
            attachEventListenersToCommentButtons(container);
        });
    }
    
    // Helper function for deleting comment
    function deleteComment(button) {
        const commentId = button.closest('form').querySelector('input[name="comment_id"]').value;
        const formData = new FormData();
        formData.append('action', 'delete_comment');
        formData.append('comment_id', commentId);
        
        fetch('../ajax_handlers/comment_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                button.closest('.show-comments').remove();
                checkEmptyComments();
                updateCommentCount();
                showNotification('Comment deleted successfully!');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while deleting the comment.', 'error');
        });
    }
    
    // Add comment form handling
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        // Remove any existing event listeners that might be causing conflicts
        const newCommentForm = commentForm.cloneNode(true);
        commentForm.parentNode.replaceChild(newCommentForm, commentForm);
        
        // Add our clean event listener to the fresh form
        newCommentForm.addEventListener('submit', function(e) {
            // This must be the first line to prevent default form submission
            e.preventDefault();
            
            const commentText = this.querySelector('textarea[name="comment"]').value;
            if (!commentText.trim()) {
                showNotification('Please enter a comment', 'error');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'add_comment');
            
            fetch('../ajax_handlers/comment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Clear form
                    this.reset();
                    
                    // Update comment count display
                    updateCommentCount();
                    
                    // Remove "no comments" message if present
                    const emptyMessage = document.querySelector('.user-comments-container .empty');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                    
                    // Add the new comment to the DOM without refresh
                    const commentsContainer = document.querySelector('.user-comments-container');
                    const newComment = document.createElement('div');
                    newComment.className = 'show-comments';
                    newComment.style.order = '-1'; // Show own comments first
                    
                    // Get current date and time formatted nicely
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    const timeStr = now.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    newComment.innerHTML = `
                        <div class="comment-user">
                            <i class="fas fa-user"></i>
                            <div>
                                <span>${data.user_name}</span>
                                <div>${dateStr} at ${timeStr}</div>
                            </div>
                        </div>
                        <div class="comment-box" style="color:var(--white); background:var(--black);">
                            ${commentText}
                        </div>
                        <form action="" method="POST">
                            <input type="hidden" name="comment_id" value="${data.comment_id}">
                            <button type="submit" class="inline-option-btn" name="open_edit_box">Edit Comment</button>
                            <button type="submit" class="inline-delete-btn" name="delete_comment">Delete Comment</button>
                        </form>
                    `;
                    
                    // Add to the beginning of the container
                    commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
                    
                    // Update comment count in post info
                    const commentCountElem = document.querySelector('.icons div:first-child span');
                    if (commentCountElem) {
                        let currentCount = parseInt(commentCountElem.textContent.match(/\d+/)[0] || '0');
                        commentCountElem.textContent = `(${currentCount + 1})`;
                    }
                    
                    // Attach event listeners to the new comment buttons
                    attachEventListenersToCommentButtons(newComment);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while submitting your comment.', 'error');
            });
            
            // Return false to be extra sure form doesn't submit normally
            return false;
        });
    } else {
        console.error('Comment form not found! Make sure it has id="comment-form"');
    }
});