// Enhanced tejidos.js for Read More functionality

document.addEventListener('DOMContentLoaded', function() {
    // Toggle description expand/collapse
    const toggleButtons = document.querySelectorAll('.toggle-desc-btn');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the description element that's a sibling of this button
            const descWrapper = this.closest('.description-wrapper');
            const description = descWrapper.querySelector('.tejido-description');
            
            // Toggle collapsed class
            description.classList.toggle('collapsed');
            
            // Update button text based on state
            if (description.classList.contains('collapsed')) {
                this.textContent = 'Show more';
            } else {
                this.textContent = 'Show less';
            }
        });
    });
    
    // Auto-submit form on category change
    const categorySelect = document.querySelector('select[name="category"]');
    if (categorySelect) {
      categorySelect.addEventListener('change', function () {
        this.form.submit();
      });
    }
  
    // Auto-submit on search (after user stops typing for 500ms)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
      let debounceTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          this.form.submit();
        }, 500);
      });
    }

    // Prevent event bubbling for like buttons
    document.querySelectorAll('.tejido-actions button, .tejido-actions a').forEach(element => {
        element.addEventListener('click', function(e) {
            e.stopPropagation(); // Stop the click from triggering the card click
        });
    });
    
    // Image modal functionality
    window.openModal = function(imgElement) {
        const modal = document.createElement('div');
        modal.classList.add('image-modal');
        
        const modalImg = document.createElement('img');
        modalImg.src = imgElement.src;
        
        const closeBtn = document.createElement('span');
        closeBtn.classList.add('modal-close');
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = function() {
            document.body.removeChild(modal);
            document.querySelector('.modal-overlay').classList.remove('active');
        };
        
        modal.appendChild(closeBtn);
        modal.appendChild(modalImg);
        document.body.appendChild(modal);
        
        document.querySelector('.modal-overlay').classList.add('active');
    };
    
    window.closeModal = function() {
        const modal = document.querySelector('.image-modal');
        if (modal) {
            document.body.removeChild(modal);
        }
        document.querySelector('.modal-overlay').classList.remove('active');
    };
});

// Full tejido details display
function showTejidoDetails(card) {
    // Get the data from the card
    const img = card.querySelector('.tejido-image img');
    const title = card.querySelector('h3').textContent;
    const author = card.querySelector('.author-name').textContent;
    
    // Get the full description from the data attribute
    const fullDescription = card.querySelector('.tejido-description').getAttribute('data-full-description') || 
                           card.querySelector('.tejido-description').textContent;
    
    const metaInfo = card.querySelector('.tejido-meta').innerHTML;
    
    // Create the modal overlay if it doesn't exist
    let overlay = document.querySelector('.modal-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        document.body.appendChild(overlay);
    }
    
    // Create the tejido detail modal
    let detailModal = document.querySelector('.tejido-detail-modal');
    if (!detailModal) {
        detailModal = document.createElement('div');
        detailModal.className = 'tejido-detail-modal';
        document.body.appendChild(detailModal);
    }
    
    // Create the modal content
    detailModal.innerHTML = `
        <div class="detail-modal-content">
            <span class="close-modal" onclick="closeTejidoDetails()">&times;</span>
            <h2>${title}</h2>
            <div class="detail-modal-body">
                <div class="detail-modal-image">
                    <img src="${img.src}" alt="${title}">
                </div>
                <div class="detail-modal-info">
                    <div class="detail-modal-author">
                        <i class="fas fa-user"></i> ${author}
                    </div>
                    <div class="detail-modal-description">${fullDescription}</div>
                    <div class="detail-modal-meta">${metaInfo}</div>
                </div>
            </div>
        </div>
    `;
    
    // Show the modal
    overlay.style.display = 'block';
    detailModal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling
    
    // Close modal when clicking the overlay
    overlay.onclick = closeTejidoDetails;
}

function closeTejidoDetails() {
    const overlay = document.querySelector('.modal-overlay');
    const detailModal = document.querySelector('.tejido-detail-modal');
    
    if (overlay) overlay.style.display = 'none';
    if (detailModal) detailModal.style.display = 'none';
    
    document.body.style.overflow = ''; // Re-enable scrolling
}

// Close modal when pressing ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTejidoDetails();
    }
});
