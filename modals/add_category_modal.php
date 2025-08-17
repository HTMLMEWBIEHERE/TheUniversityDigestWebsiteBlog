<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
        <h2>Add New Category</h2>
        <!-- <h3>Current Categories</h3>
        <select name="category_id" id="category_id">
                <option value="existing">Select existing category</option>
                <!-- Dynamic categories will be loaded here -->
            <!-- </select> --> 
        <form id="addCategoryForm">
            <label for="category_name">Category Name:</label>
            <input type="text" id="category_name" name="category_name" required>
            <button type="submit" class="btn">Add Category</button>
        </form>
    </div>
</div>
