<div id="addMemberModal" class="modal" style="display:none;">
    <div class="modal-content">
        <!-- Close button for the modal -->
        <span class="close" onclick="closeModal('addMemberModal')">&times;</span>      
        <!-- Form for adding a member -->
        <form id="addMemberForm" action="" method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>

              <label for="category_id">Category:</label>
            <select name="category_id" id="category_id">
                <option value="existing">Select existing category</option>
                <!-- Dynamic categories will be loaded here -->
            </select>


            <label for="position">Position:</label>
            <input type="text" name="position" id="position" required>

            <label for="date_appointed">Date Appointed:</label>
            <input type="date" name="date_appointed" id="date_appointed" required />

            <label for="image">Image:</label>
            <input type="file" name="image" id="image" accept="image/*">

            <button type="submit" class="btn">Add Member</button>
        </form>
    </div>
</div>


<div id="editMemberModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editMemberModal')">&times;</span>
        <form id="editMemberForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" id="edit_org_id" name="org_id">
    
    <label for="edit_name">Name:</label>
    <input type="text" id="edit_name" name="name">

    <label for="edit_category_id">Category:</label>
    <select id="edit_category_id" name="category_id">
        <!-- Options loaded dynamically -->
    </select>
    
    <label for="edit_position">Position:</label>
    <input type="text" id="edit_position" name="position">
    
    
    <label for="edit_date_appointed">Date Appointed:</label>
    <input type="date" id="edit_date_appointed" name="date_appointed">
    
    <label for="edit_date_ended">Date Ended: (optional)</label>
    <input type="date" id="edit_date_ended" name="date_ended">
    
    <label>Current Image:</label>
    <img id="edit_existing_image" src="" class="member-image" style="max-width: 200px;">

    <label for="edit_member_image">New Image:</label>
    <input type="file" id="edit_member_image" name="image">
 
    
    <button type="submit">Update</button>
</form>
    </div>
</div>