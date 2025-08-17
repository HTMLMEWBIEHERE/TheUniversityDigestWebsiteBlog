<div id="editMemberModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editMemberModal')">&times;</span>
        <form id="editMemberForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="edit_org_id" name="org_id">
            <label for="edit_name">Name:</label>
            <input type="text" id="edit_name" name="name" required>
            
            <!-- Category FIRST -->
            <label for="edit_category_id">Category:</label>
            <select id="edit_category_id" name="category_id" required>
                <!-- Categories will be populated via AJAX -->
            </select>
            
            <!-- Position SECOND -->
            <label for="edit_position">Position:</label>
            <input type="text" id="edit_position" name="position" required>
            
            <label for="edit_member_image">Current Image:</label>
            <img id="edit_existing_image" src="" alt="Existing Image" style="max-width: 150px; max-height: 150px;">
            <label for="edit_member_image">New Image (Optional):</label>
            <input type="file" id="edit_member_image" name="image">
            <button type="submit">Update Member</button>
        </form>
    </div>
</div>