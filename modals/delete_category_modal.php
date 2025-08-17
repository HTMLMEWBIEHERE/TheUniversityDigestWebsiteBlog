<?php
require_once '../classes/organization.class.php';

$organization = new Organization();
$categories = $organization->getCategories();
?>

<!-- Delete Category Modal -->
<div id="deleteCategoryModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeModal('deleteCategoryModal')">&times;</span>
    <h3>Manage Categories</h3>

    <button class="btn" onclick="closeModal('deleteCategoryModal'); openModal('addCategoryModal');">
      Add New Category
    </button>

    <div id="category-container">
      <table id="categoryTable">
        <tbody>
          <?php foreach ($categories as $category): ?>
            <tr id="category-<?php echo $category['category_id']; ?>">
              <td><?php echo htmlspecialchars($category['category_name']); ?></td>
              <td>
                <button class="btn-delete-category" data-id="<?php echo $category['category_id']; ?>">
                  Delete
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

