/**
 * Footer AJAX JavaScript
 * Handles AJAX operations for footer management
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize message container
    createMessageContainer();
    
    // Check for existing messages from PHP session
    checkSessionMessages();
    
    // Set up tab switching
    setupTabSwitching();
    
    // Set up icon dropdowns
    setupIconDropdowns();
    
    // Set up form submissions
    setupFormSubmissions();
    
    // Set up modal functionality
    setupModalFunctionality();
});

/**
 * Create a message container if it doesn't exist
 */
function createMessageContainer() {
    if (!document.querySelector('.message-container')) {
        const container = document.createElement('div');
        container.className = 'message-container';
        const footerManagement = document.querySelector('.footer-management');
        if (footerManagement) {
            footerManagement.prepend(container);
        }
    }
}

/**
 * Check for messages set in PHP session
 */
function checkSessionMessages() {
    const messageElements = document.querySelectorAll('.message');
    if (messageElements.length > 0) {
        messageElements.forEach(element => {
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (element.parentNode) {
                    element.remove();
                }
            }, 5000);
            
            // Add close button functionality
            const closeBtn = element.querySelector('i.fa-times');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    element.remove();
                });
            }
        });
    }
}

/**
 * Set up tab switching functionality
 */
function setupTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Extract tab ID from onclick attribute if data-tab is not present
            let tabId = this.getAttribute('data-tab');
            if (!tabId && this.getAttribute('onclick')) {
                const match = this.getAttribute('onclick').match(/showTab\('([^']+)'/);
                if (match && match[1]) {
                    tabId = match[1];
                }
            }
            
            if (tabId) {
                showTab(tabId, this);
            }
        });
    });
}

/**
 * Show the selected tab
 */
function showTab(tabId, button) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show the selected tab and mark the button active
    const tabContent = document.getElementById(tabId);
    if (tabContent) {
        tabContent.classList.add('active');
    }
    
    if (button) {
        button.classList.add('active');
    }
}

/**
 * Set up icon dropdowns for both add and edit forms
 */
function setupIconDropdowns() {
    // Add form dropdown
    const addDropdown = document.querySelector('#icon-dropdown');
    if (addDropdown) {
        const selected = addDropdown.querySelector('.selected');
        const options = addDropdown.querySelector('.options');
        
        if (selected && options) {
            selected.addEventListener('click', function() {
                options.classList.toggle('d-none');
            });
            
            options.querySelectorAll('div').forEach(option => {
                option.addEventListener('click', function() {
                    const iconClass = this.getAttribute('data-value');
                    selected.innerHTML = `<i class="${iconClass}"></i> ${this.textContent.trim()}`;
                    const iconInput = document.getElementById('icon_class_input');
                    if (iconInput) {
                        iconInput.value = iconClass;
                    }
                    options.classList.add('d-none');
                });
            });
        }
    }
    
    // Edit form dropdown
    const editDropdown = document.querySelector('#edit_icon_dropdown');
    if (editDropdown) {
        const selected = editDropdown.querySelector('.selected');
        const options = editDropdown.querySelector('.options');
        
        if (selected && options) {
            selected.addEventListener('click', function() {
                options.classList.toggle('d-none');
            });
            
            options.querySelectorAll('div').forEach(option => {
                option.addEventListener('click', function() {
                    const iconClass = this.getAttribute('data-value');
                    selected.innerHTML = `<i class="${iconClass}"></i> ${this.textContent.trim()}`;
                    const iconInput = document.getElementById('edit_icon_class_input');
                    if (iconInput) {
                        iconInput.value = iconClass;
                    }
                    options.classList.add('d-none');
                });
            });
        }
    }
}

/**
 * Set up form submissions with AJAX
 */
function setupFormSubmissions() {
    // Footer info form
    const footerInfoForm = document.querySelector('#footerInfoTab form');
    if (footerInfoForm) {
        footerInfoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateFooterInfo(this);
        });
    }
    
    // Add social form
    const addSocialForm = document.querySelector('#socialMediaTab form');
    if (addSocialForm) {
        addSocialForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addSocialMedia(this);
        });
    }
    
    // Edit social form
    const editSocialForm = document.querySelector('#edit-social-modal form');
    if (editSocialForm) {
        editSocialForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateSocialMedia(this);
        });
    }
    
    // Delete social buttons
    setupDeleteButtons();
}

/**
 * Set up delete buttons for social media links
 */
function setupDeleteButtons() {
    document.querySelectorAll('button[name="delete_social"]').forEach(button => {
        const form = button.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this social media link?')) {
                    const socialId = this.querySelector('input[name="social_id"]').value;
                    deleteSocialMedia(socialId);
                }
            });
        }
    });
}

/**
 * Update footer information via AJAX
 */
function updateFooterInfo(form) {
    const formData = new FormData(form);
    formData.append('action', 'update_footer');
    
    // Show loading indicator
    showMessage('Updating footer information...', 'info');
    
    fetch('../ajax_handlers/footer_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error: ' + error.message, 'error');
        console.error('Error:', error);
    });
}

/**
 * Add a new social media link via AJAX
 */
function addSocialMedia(form) {
    const formData = new FormData(form);
    formData.append('action', 'add_social');
    
    // Show loading indicator
    showMessage('Adding social media link...', 'info');
    
    fetch('../ajax_handlers/footer_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Reset form
            form.reset();
            // Reset icon dropdown
            const selected = form.querySelector('.custom-select .selected');
            if (selected) {
                selected.innerHTML = '<i class="fab fa-facebook-f"></i> Facebook';
            }
            const iconInput = form.querySelector('#icon_class_input');
            if (iconInput) {
                iconInput.value = 'fab fa-facebook-f';
            }
            // Refresh social media table
            refreshSocialMediaTable();
        } else {
            showMessage(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error: ' + error.message, 'error');
        console.error('Error:', error);
    });
}

/**
 * Update a social media link via AJAX
 */
function updateSocialMedia(form) {
    const formData = new FormData(form);
    formData.append('action', 'update_social');
    
    // Show loading indicator
    showMessage('Updating social media link...', 'info');
    
    fetch('../ajax_handlers/footer_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Close modal
            closeModal('edit-social-modal');
            // Refresh social media table
            refreshSocialMediaTable();
        } else {
            showMessage(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error: ' + error.message, 'error');
        console.error('Error:', error);
    });
}

/**
 * Delete a social media link via AJAX
 */
function deleteSocialMedia(socialId) {
    const formData = new FormData();
    formData.append('action', 'delete_social');
    formData.append('social_id', socialId);
    
    // Show loading indicator
    showMessage('Deleting social media link...', 'info');
    
    fetch('../ajax_handlers/footer_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Refresh social media table
            refreshSocialMediaTable();
        } else {
            showMessage(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error: ' + error.message, 'error');
        console.error('Error:', error);
    });
}

/**
 * Refresh the social media table with latest data
 */
function refreshSocialMediaTable() {
    fetch('../ajax_handlers/footer_ajax.php?action=get_socials')
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            const tableBody = document.querySelector('#socialMediaTab table tbody');
            if (tableBody) {
                // Clear existing rows
                tableBody.innerHTML = '';
                
                if (data.socials && data.socials.length > 0) {
                    // Add new rows
                    data.socials.forEach(social => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${escapeHTML(social.platform)}</td>
                            <td>${escapeHTML(social.url)}</td>
                            <td><i class="${escapeHTML(social.icon_class)}"></i> ${escapeHTML(social.platform)}</td>
                            <td class="flex-btn">
                                <button class="option-btn" onclick="openEditSocialModal(${social.social_id}, '${escapeHTML(social.platform)}', '${escapeHTML(social.url)}', '${escapeHTML(social.icon_class)}')">Edit</button>
                                
                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this social media link?');">
                                    <input type="hidden" name="social_id" value="${social.social_id}">
                                    <button type="submit" name="delete_social" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                    
                    // Re-setup delete buttons
                    setupDeleteButtons();
                } else {
                    // No social links found
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="4" class="empty">No social media links found</td>
                    `;
                    tableBody.appendChild(row);
                }
            }
        } else {
            showMessage('Error refreshing social media links', 'error');
        }
    })
    .catch(error => {
        showMessage('Network error: ' + error.message, 'error');
        console.error('Error:', error);
    });
}

/**
 * Set up modal functionality
 */
function setupModalFunctionality() {
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Close button in modal
    const closeButtons = document.querySelectorAll('.modal .close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
}

/**
 * Open the edit social media modal
 */
function openEditSocialModal(socialId, platform, url, iconClass) {
    // Set form values
    const idInput = document.getElementById('edit_social_id');
    const platformInput = document.getElementById('edit_social_platform');
    const urlInput = document.getElementById('edit_social_url');
    const iconInput = document.getElementById('edit_icon_class_input');
    const selected = document.getElementById('edit_icon_selected');
    
    if (idInput) idInput.value = socialId;
    if (platformInput) platformInput.value = platform;
    if (urlInput) urlInput.value = url;
    if (iconInput) iconInput.value = iconClass;
    
    // Set icon dropdown
    if (selected) {
        selected.innerHTML = `<i class="${iconClass}"></i> ${platform}`;
    }
    
    // Show modal
    const modal = document.getElementById('edit-social-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

/**
 * Close a modal by ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Show a message to the user
 */
function showMessage(message, type = 'success') {
    // Get or create message container
    let container = document.querySelector('.message-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'message-container';
        const footerManagement = document.querySelector('.footer-management');
        if (footerManagement) {
            footerManagement.prepend(container);
        } else {
            document.body.prepend(container);
        }
    }
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.className = `message ${type}`;
    messageElement.innerHTML = `
        <span>${message}</span>
        <i class="fas fa-times" onclick="this.parentElement.remove()"></i>
    `;
    
    // Add to container
    container.appendChild(messageElement);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (messageElement.parentNode) {
            messageElement.remove();
        }
    }, 5000);
}

/**
 * Helper function to escape HTML to prevent XSS
 */
function escapeHTML(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}