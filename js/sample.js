// Open modal function
function openModal(id) {
    document.getElementById(id).style.display = 'block';
}

// Close modal function
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Handle the add category form submission
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const categoryNameInput = document.getElementById('category_name');
    const categoryName = categoryNameInput.value.trim();

    if (categoryName === '') {
        alert('Please enter a valid category name.');
        return;
    }

    const formData = new FormData();
    formData.append('category_name', categoryName);

    fetch('../superadmin_content/add_category.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Add the new category to the category dropdown dynamically
            const categorySelect = document.getElementById('category_id');
            
            // Add the new category to the dropdown
            const newOption = document.createElement('option');
            newOption.value = data.category_id;
            newOption.textContent = data.category_name;
            categorySelect.appendChild(newOption);

            // Update the categories list in the select dropdown with all categories
            categorySelect.innerHTML = data.category_options;

            // Reset input field and close the modal
            categoryNameInput.value = '';
            closeModal('addCategoryModal');

        } else {
            alert(data.message || 'Failed to add category.');
        }
    })
    .catch(err => {
        console.error('AJAX Error:', err);
        alert('Something went wrong!');
    });
});


// Handle the add member form submission
document.getElementById('addMemberForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = document.getElementById('addMemberForm');
    const formData = new FormData(form);

    const categorySelect = document.getElementById('category_id');
    const selectedCategory = categorySelect.value;

    if (selectedCategory === 'new') {
        formData.append('category', 'new');
        formData.append('category_id', '');
        formData.append('new_category', document.getElementById('new_category').value);
    } else {
        formData.append('category', 'existing');
        formData.append('category_id', selectedCategory);
    }

    fetch('../superadmin_content/add_member.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Member added!');
            closeModal('addMemberModal');
            form.reset();
            loadOrganizationList();
        } else {
            alert(data.message || 'Something went wrong.');
        }
    })
    .catch(err => {
        console.error('AJAX Error:', err);
        alert('Something went wrong!');
    });
});

// Refresh the organization list
function loadOrganizationList() {
    fetch('../superadmin_content/get_all_members.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const membersList = document.getElementById('membersList');
            membersList.innerHTML = '';
            data.members.forEach(member => {
                const memberItem = document.createElement('li');
                memberItem.textContent = `${member.name} - ${member.position}`;
                membersList.appendChild(memberItem);
            });
        } else {
            alert('Failed to load organization members.');
        }
    })
    .catch(err => {
        console.error('AJAX Error:', err);
        alert('Failed to load organization members.');
    });
}


// Load categories and attach delete listeners
function loadCategories() {
    fetch('../modals/delete_category_modal.php')
    .then(response => response.text())
    .then(html => {
        document.getElementById('category-container').innerHTML = html;
        attachDeleteHandlers();
    });
}

// Attach delete handlers to delete buttons
function attachDeleteHandlers() {
    document.querySelectorAll('.btn-delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');

            if (confirm('Are you sure you want to delete this category?')) {
                const formData = new FormData();
                formData.append('category_id', categoryId);

                fetch('../superadmin_content/delete_category.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`category-${categoryId}`).remove();
                    } else {
                        alert(data.message || 'Failed to delete category.');
                    }
                })
                .catch(err => {
                    console.error('AJAX Error:', err);
                    alert('Failed to delete category.');
                });
            }
        });
    });
}
function openDeleteCategoryModal() {
    fetch('../modals/delete_category_modal.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('delete-category-container').innerHTML = html;
            document.getElementById('deleteCategoryModal').style.display = 'block';
            bindDeleteButtons();
        })
        .catch(err => {
            console.error('Error loading modal:', err);
        });
}

// Optional: Click outside modal to close
window.onclick = function(event) {
    const modal = document.getElementById('deleteCategoryModal');
    if (event.target === modal) {
        modal.style.display = "none";
    }
};

// Function to handle delete button clicks
function bindDeleteButtons() {
    document.querySelectorAll('.btn-delete-category').forEach(button => {
        button.addEventListener('click', function () {
            const categoryId = this.dataset.id;
            if (confirm("Are you sure you want to delete this category?")) {
                fetch('../superadmin_content/delete_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'category_id=' + encodeURIComponent(categoryId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('category-' + categoryId).remove();
                    } else {
                        alert(data.message || 'Failed to delete category.');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    alert('Something went wrong.');
                });
            }
        });
    });
}

function editMember(org_id) {
    const modal = document.getElementById('editMemberModal');
    if (!modal) {
        console.error('Modal with ID editMemberModal not found!');
        return;
    }
    modal.style.display = 'block';

    // Fetch member details
    $.ajax({
        url: '../superadmin_content/get_member_details.php',
        type: 'GET',
        data: { org_id: org_id },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                // Fill in the form with member data
                $('#edit_org_id').val(data.member.org_id);
                $('#edit_name').val(data.member.name);
                $('#edit_position').val(data.member.position);
                $('#edit_date_appointed').val(data.member.date_appointed);
                $('#edit_date_ended').val(data.member.date_ended);

                // Optionally show existing image if needed
                // $('#edit_existing_image').attr('src', data.member.image); // for <img> tag
                // $('#edit_existing_image').val(data.member.image); // only if storing value

                // Now load categories
                $.ajax({
                    url: '../superadmin_content/get_categories.php',
                    type: 'GET',
                    success: function(categoriesResponse) {
                        const categories = JSON.parse(categoriesResponse);
                        const categorySelect = $('#edit_category_id');
                        categorySelect.empty(); // Clear existing options

                        categories.forEach(category => {
                            const option = $('<option>', {
                                value: category.category_id,
                                text: category.category_name
                            });

                            if (category.category_id === data.member.category_id) {
                                option.prop('selected', true); // pre-select assigned category
                            }

                            categorySelect.append(option);
                        });
                    },
                    error: function() {
                        alert('Error loading categories.');
                    }
                });
            } else {
                alert('Error fetching member details.');
            }
        },
        error: function() {
            alert('Error fetching member details.');
        }
    });
}

// Function to handle the form submission using AJAX
$('#editMemberForm').submit(function (e) {
    e.preventDefault();

    // Create FormData to handle file uploads
    var formData = new FormData(this);

    // Send AJAX request to update member details
    $.ajax({
        url: '../superadmin_content/edit_member.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                $('#editMessage').html('<div class="success">' + data.message + '</div>');
                // Close the modal after success
                setTimeout(function () {
                    closeModal('editMemberModal');
                }, 2000);

                // Optionally, update the member list dynamically (without reloading the page)
                // Example: $('#member_' + data.member.org_id).html(data.updatedMemberHTML);
            } else {
                $('#editMessage').html('<div class="error">' + data.message + '</div>');
            }
        },
        error: function () {
            $('#editMessage').html('<div class="error">There was an error updating the member.</div>');
        }
    });
});

// Function to close the modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}


// Load categories on initial page load
window.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});
