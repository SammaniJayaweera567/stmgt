<?php
ob_start();
include '../../init.php';

if (!isset($_SESSION['ID'])) {
    header("Location:../login.php");
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="mb-5 w-auto">Manage Expense Categories</h5>
        </div>
        <div class="col-12 mt-3">
            <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                        <i class="fas fa-list me-1"></i> Category List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
                        <i class="fas fa-plus-circle me-1"></i> Add Category
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="categoryTabsContent">
                <!-- Category List Tab -->
                <div class="tab-pane fade show active mt-3" id="list" role="tabpanel">
                    <div class="card mt-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="categoryTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category Name</th>
                                            <th>Description</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Sample Data - Replace with actual data from database -->
                                        <tr>
                                            <td>1</td>
                                            <td>Office Supplies</td>
                                            <td>Stationery and office materials</td>
                                            <td>2023-10-15</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="expense_categories.php?edit=1" class="btn btn-sm btn-primary mr-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="expense_categories.php?delete=1" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>Utilities</td>
                                            <td>Electricity, water, internet bills</td>
                                            <td>2023-10-10</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="expense_categories.php?edit=2" class="btn btn-sm btn-primary mr-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="expense_categories.php?delete=2" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>Salaries</td>
                                            <td>Staff payments</td>
                                            <td>2023-10-05</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="expense_categories.php?edit=3" class="btn btn-sm btn-primary mr-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="expense_categories.php?delete=3" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Category Tab -->
                <div class="tab-pane fade" id="add" role="tabpanel">
                    <div class="col-md-12 mt-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Category</h3>
                            </div>
                            <form method="post" class="p-4">
                                <div class="card-body">
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-light">
                                            <h5 class="detail-header mb-0" style="color: #037e7d; font-size: 16px; font-weight: 600;">
                                                <i class="fas fa-tags me-2 mr-2" style="color: #037e7d;"></i>Category Details
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="name" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-light">
                                    <button type="submit" name="add_category" class="btn btn-primary px-4 py-2">
                                        Save Category
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary px-4 py-2 ms-2">
                                        Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal (Hidden by default) -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#categoryTable').DataTable({
        responsive: true,
        lengthMenu: [5, 10, 25, 50, 100],
        pageLength: 10,
        language: {
            searchPlaceholder: "Search categories...",
            search: "",
            lengthMenu: "Show _MENU_ entries"
        },
        order: [[1, 'asc']] // Sort by name
    });

    // Check if URL has edit parameter and switch to add tab
    if(window.location.href.indexOf('edit=') > -1) {
        $('#add-tab').tab('show');
        document.querySelector('#add-tab i').className = 'fas fa-edit me-1';
        document.querySelector('.card-title').textContent = 'Edit Category';
        document.querySelector('[name="add_category"]').textContent = 'Update Category';
        
        // Here you would normally fetch the category data to populate the form
        // For this UI-only version, we're just simulating the edit mode
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>