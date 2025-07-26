<?php
ob_start();
include '../../init.php'; // Adjust path as necessary for your project structure

// Ensure user is logged in
if (!isset($_SESSION['ID'])) {
    header("Location: ../login.php");
    exit();
}

$db = dbConn(); // Get database connection

// Initialize messages array to prevent warnings on first load
$messages = [];

// Define dataClean function if not already defined in init.php or functions.php
if (!function_exists('dataClean')) {
    function dataClean($data) {
        // Handle null input gracefully for htmlspecialchars
        if (is_array($data)) {
            return array_map('dataClean', $data);
        }
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Show success message if redirected with success=1 (for Add Notice)
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Notice added successfully!',
                showConfirmButton: false,
                timer: 2000
            });
            setTimeout(() => {
                const triggerEl = document.querySelector('[data-bs-target=\"#list\"]');
                if (triggerEl) {
                    const tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                }
            }, 500);
        });
    </script>";
}

// Show success message if redirected with update_success=1 (for Edit Notice) - Handled via AJAX now, but good to keep if direct redirect is ever used
if (isset($_GET['update_success']) && $_GET['update_success'] == 1) {
    echo "<script>
        window.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Notice updated successfully!',
                showConfirmButton: false,
                timer: 2000
            });
        });
    </script>";
}

// --- PHP Logic for handling POST requests ---

// Add Notice Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_notice') {
    $title = dataClean($_POST['Title'] ?? '');
    $description = dataClean($_POST['Description'] ?? '');
    $notice_type = dataClean($_POST['NoticeType'] ?? '');
    $published_date = dataClean($_POST['PublishedDate'] ?? '');
    $expiry_date = dataClean($_POST['ExpiryDate'] ?? ''); // Can be empty/null
    $is_active = isset($_POST['IsActive']) ? 1 : 0;

    // Validation for Add Notice
    if (empty($title)) $messages['Title'] = "Title is required.";
    if (empty($description)) $messages['Description'] = "Description is required.";
    if (empty($notice_type)) $messages['NoticeType'] = "Notice Type is required.";
    if (empty($published_date)) $messages['PublishedDate'] = "Published Date is required.";

    // Date Validation
    if (!empty($published_date) && !strtotime($published_date)) {
        $messages['PublishedDate'] = "Invalid Published Date format.";
    }
    if (!empty($expiry_date) && !strtotime($expiry_date)) {
        $messages['ExpiryDate'] = "Invalid Expiry Date format.";
    }
    if (empty($messages['PublishedDate']) && empty($messages['ExpiryDate']) && !empty($published_date) && !empty($expiry_date) && strtotime($published_date) > strtotime($expiry_date)) {
        $messages['ExpiryDate'] = "Expiry Date cannot be before Published Date.";
    }

    if (empty($messages)) {
        $sql = "INSERT INTO notices (title, description, notice_type, published_date, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing insert statement for notice: " . $db->error);
            header("Location: manage_notices.php?error=db_prepare_add");
            exit();
        }
        // Handle expiry_date for DB: if empty, store as NULL
        $expiry_date_for_db = empty($expiry_date) ? NULL : $expiry_date;
        $stmt->bind_param("sssssi", $title, $description, $notice_type, $published_date, $expiry_date_for_db, $is_active);

        if ($stmt->execute()) {
            header("Location: manage_notices.php?success=1");
            exit();
        } else {
            error_log("Error inserting notice data: " . $stmt->error);
            header("Location: manage_notices.php?error=db_insert_add");
            exit();
        }
        $stmt->close();
    }
}

// Update Notice Form Submission (via AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_notice_ajax') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'errors' => []];

    $id = dataClean($_POST['Id'] ?? '');
    $title_update = dataClean($_POST['Title'] ?? '');
    $description_update = dataClean($_POST['Description'] ?? '');
    $notice_type_update = dataClean($_POST['NoticeType'] ?? '');
    $published_date_update = dataClean($_POST['PublishedDate'] ?? '');
    $expiry_date_update = dataClean($_POST['ExpiryDate'] ?? '');
    $is_active_update = isset($_POST['IsActive']) ? 1 : 0;

    // Validation for Edit Notice
    if (empty($id)) $response['errors']['Id'] = "Notice ID is missing.";
    if (empty($title_update)) $response['errors']['Title'] = "Title is required.";
    if (empty($description_update)) $response['errors']['Description'] = "Description is required.";
    if (empty($notice_type_update)) $response['errors']['NoticeType'] = "Notice Type is required.";
    if (empty($published_date_update)) $response['errors']['PublishedDate'] = "Published Date is required.";

    // Date Validation
    if (!empty($published_date_update) && !strtotime($published_date_update)) {
        $response['errors']['PublishedDate'] = "Invalid Published Date format.";
    }
    if (!empty($expiry_date_update) && !strtotime($expiry_date_update)) {
        $response['errors']['ExpiryDate'] = "Invalid Expiry Date format.";
    }
    if (empty($response['errors']['PublishedDate']) && empty($response['errors']['ExpiryDate']) && !empty($published_date_update) && !empty($expiry_date_update) && strtotime($published_date_update) > strtotime($expiry_date_update)) {
        $response['errors']['ExpiryDate'] = "Expiry Date cannot be before Published Date.";
    }

    if (empty($response['errors'])) {
        $sql = "UPDATE notices SET title = ?, description = ?, notice_type = ?, published_date = ?, expiry_date = ?, is_active = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            error_log("Failed to prepare update statement for notice: " . $db->error);
            $response['message'] = "Database error: Failed to prepare update query.";
        } else {
            // Handle expiry_date for DB: if empty, store as NULL
            $expiry_date_for_db = empty($expiry_date_update) ? NULL : $expiry_date_update;
            $stmt->bind_param("sssssii", $title_update, $description_update, $notice_type_update, $published_date_update, $expiry_date_for_db, $is_active_update, $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Notice updated successfully!";
            } else {
                error_log("Error updating notice data: " . $stmt->error);
                $response['message'] = "Failed to update notice in the database.";
            }
            $stmt->close();
        }
    } else {
        $response['message'] = "Validation errors occurred.";
    }

    echo json_encode($response);
    exit();
}


// Fetch all notices for displaying in the table
$allNoticesSql = "SELECT id, title, description, notice_type, published_date, expiry_date, is_active FROM notices ORDER BY published_date DESC, created_at DESC";
$allNoticesResult = $db->query($allNoticesSql);

// Define notice types for dropdown
$notice_types = ['General', 'Academic', 'Event', 'Urgent', 'Holiday']; // Customize as needed
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text"><i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="mb-5 w-auto">Manage Notices</h5>
        </div>
        <div class="col-12 mt-3">
            <ul class="nav nav-tabs" id="noticeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list"
                        type="button" role="tab">
                        <i class="fas fa-list me-1" style="font-size: 15px; margin-right: 5px;"></i> Notice List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button"
                        role="tab">
                        <i class="fas fa-plus-circle me-1" style="font-size: 15px; margin-right: 5px;"></i>
                        Add New Notice
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="noticeTabsContent">
                <div class="tab-pane fade show active mt-3" id="list" role="tabpanel">
                    <div class="card mt-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="noticeTable" class="table table-striped table-bordered"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Published Date</th>
                                            <th>Expiry Date</th>
                                            <th>Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($allNoticesResult && $allNoticesResult->num_rows > 0) {
                                            while ($row = $allNoticesResult->fetch_assoc()) {
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($row['id']) . "</td>
                                                        <td>" . htmlspecialchars($row['title']) . "</td>
                                                        <td><div style='max-height: 80px; overflow-y: auto; white-space: pre-wrap;'>" . htmlspecialchars($row['description']) . "</div></td>
                                                        <td>" . htmlspecialchars($row['notice_type']) . "</td>
                                                        <td>" . htmlspecialchars($row['published_date']) . "</td>
                                                        <td>" . htmlspecialchars($row['expiry_date'] ?: 'N/A') . "</td>
                                                        <td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>
                                                        <td>
                                                            <button type='button' class='btn btn-info btn-sm view-btn me-1' data-id='{$row['id']}'><i class='fas fa-eye'></i></button>
                                                            <button type='button' class='btn btn-primary btn-sm edit-btn' data-id='{$row['id']}' data-bs-toggle='modal' data-bs-target='#editNoticeModal'><i class='fas fa-edit'></i></button>
                                                            <button type='button' class='btn btn-danger btn-sm delete-btn' data-id='{$row['id']}'><i class='fas fa-trash'></i></button>
                                                        </td>
                                                    </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>No notices found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="add" role="tabpanel">
                    <div class="col-md-12 mt-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Create New Notice</h3>
                            </div>
                            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate
                                enctype="multipart/form-data" class="p-4" id="addNoticeForm">
                                <div class="card-body">
                                    <div class="form-row row">
                                        <div class="form-group col-md-6">
                                            <label for="Title">Title</label>
                                            <input type="text" name="Title" id="Title"
                                                class="form-control" value="<?= htmlspecialchars(@$_POST['Title'] ?? '') ?>">
                                            <span class="text-danger"><?= @$messages['Title'] ?? '' ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="NoticeType">Notice Type</label>
                                            <select name="NoticeType" id="NoticeType" class="form-control" required>
                                                <option value="">Select Type</option>
                                                <?php foreach ($notice_types as $type): ?>
                                                    <option value="<?php echo htmlspecialchars($type); ?>"
                                                        <?php echo (@$_POST['NoticeType'] === $type) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($type); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="text-danger"><?= @$messages['NoticeType'] ?? '' ?></span>
                                        </div>
                                    </div>
                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-12">
                                            <label for="Description">Description</label>
                                            <textarea name="Description" id="Description" class="form-control" rows="5" required><?= htmlspecialchars(@$_POST['Description'] ?? '') ?></textarea>
                                            <span class="text-danger"><?= @$messages['Description'] ?? '' ?></span>
                                        </div>
                                    </div>
                                    <div class="form-row row mt-3">
                                        <div class="form-group col-md-6">
                                            <label for="PublishedDate">Published Date</label>
                                            <input type="date" name="PublishedDate" id="PublishedDate" class="form-control"
                                                value="<?= htmlspecialchars(@$_POST['PublishedDate'] ?? date('Y-m-d')) ?>">
                                            <span class="text-danger"><?= @$messages['PublishedDate'] ?? '' ?></span>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="ExpiryDate">Expiry Date (Optional)</label>
                                            <input type="date" name="ExpiryDate" id="ExpiryDate" class="form-control"
                                                value="<?= htmlspecialchars(@$_POST['ExpiryDate'] ?? '') ?>">
                                            <span class="text-danger"><?= @$messages['ExpiryDate'] ?? '' ?></span>
                                        </div>
                                    </div>
                                    <div class="form-group form-check mt-3">
                                        <input type="checkbox" class="form-check-input" id="IsActive" name="IsActive" value="1" <?php echo isset($_POST['IsActive']) || !isset($_POST['action']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="IsActive">Is Active</label>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="action" value="add_notice"
                                        class="btn btn-primary px-4 py-2">Save Notice</button>
                                    <button type="reset" class="btn btn-outline-secondary px-4 py-2 ms-2"
                                        id="resetAddNoticeForm">
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

<div class="modal fade" id="editNoticeModal" tabindex="-1" aria-labelledby="editNoticeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="color: #ffff; background-color: #d76c3a;">
                <h5 class="modal-title" id="editNoticeModalLabel">Edit Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editNoticeForm" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_notice_ajax"> 
                    <input type="hidden" name="Id" id="editNoticeId">

                    <div class="form-row row">
                        <div class="form-group col-md-6">
                            <label for="editTitle">Title</label>
                            <input type="text" name="Title" id="editTitle" class="form-control">
                            <span class="text-danger" id="editTitleError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editNoticeType">Notice Type</label>
                            <select name="NoticeType" id="editNoticeType" class="form-control" required>
                                <option value="">Select Type</option>
                                <?php foreach ($notice_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-danger" id="editNoticeTypeError"></span>
                        </div>
                    </div>
                    <div class="form-row row mt-3">
                        <div class="form-group col-md-12">
                            <label for="editDescription">Description</label>
                            <textarea name="Description" id="editDescription" class="form-control" rows="5" required></textarea>
                            <span class="text-danger" id="editDescriptionError"></span>
                        </div>
                    </div>
                    <div class="form-row row mt-3">
                        <div class="form-group col-md-6">
                            <label for="editPublishedDate">Published Date</label>
                            <input type="date" name="PublishedDate" id="editPublishedDate" class="form-control">
                            <span class="text-danger" id="editPublishedDateError"></span>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editExpiryDate">Expiry Date (Optional)</label>
                            <input type="date" name="ExpiryDate" id="editExpiryDate" class="form-control">
                            <span class="text-danger" id="editExpiryDateError"></span>
                        </div>
                    </div>
                    <div class="form-group form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="editIsActive" name="IsActive" value="1">
                        <label class="form-check-label" for="editIsActive">Is Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-labelledby="viewNoticeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="color: #ffff; background-color: #d76c3a;">
                <h5 class="modal-title" id="viewNoticeModalLabel">Notice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>ID:</strong> <span id="viewId"></span></p>
                        <p><strong>Title:</strong> <span id="viewTitle"></span></p>
                        <p><strong>Description:</strong> <span id="viewDescription" style="white-space: pre-wrap;"></span></p>
                        <p><strong>Type:</strong> <span id="viewNoticeType"></span></p>
                        <p><strong>Published Date:</strong> <span id="viewPublishedDate"></span></p>
                        <p><strong>Expiry Date:</strong> <span id="viewExpiryDate"></span></p>
                        <p><strong>Is Active:</strong> <span id="viewIsActive"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#noticeTable').DataTable(); // Initialize DataTables

    // Function to reset Add Notice Form
    function resetAddNoticeForm() {
        $('#addNoticeForm')[0].reset();
        $('#addNoticeForm .text-danger').text(''); // Clear validation messages
        $('#PublishedDate').val('<?php echo date('Y-m-d'); ?>'); // Set default published date to today
        $('#IsActive').prop('checked', true); // Default to active
    }

    // Handle Reset Form button click for Add Notice Form
    $(document).on('click', '#resetAddNoticeForm', function() {
        resetAddNoticeForm();
    });

    // Handle Edit button click (for opening modal and populating data)
    $(document).on('click', '.edit-btn', function() {
        var noticeId = $(this).data('id');

        // Clear previous errors specifically for the edit form
        $('#editNoticeForm .text-danger').text('');

        // Fetch notice data via AJAX
        $.ajax({
            url: 'fetch_notice_data.php', // This file will fetch notice data by ID
            type: 'POST',
            data: {
                id: noticeId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Populate the Edit Notice Modal fields
                    $('#editNoticeId').val(response.data.id);
                    $('#editTitle').val(response.data.title);
                    $('#editDescription').val(response.data.description);
                    $('#editNoticeType').val(response.data.notice_type);
                    $('#editPublishedDate').val(response.data.published_date);
                    $('#editExpiryDate').val(response.data.expiry_date);
                    $('#editIsActive').prop('checked', response.data.is_active == 1);

                    $('#editNoticeModal').modal('show'); // Show the Notice Edit Modal
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to fetch notice data for editing.',
                        showConfirmButton: true
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while fetching notice data. Please try again.',
                    showConfirmButton: true
                });
            }
        });
    });

    // Handle AJAX form submission for editing notice
    $('#editNoticeForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        // Clear previous validation error messages
        $('#editTitleError').text('');
        $('#editDescriptionError').text('');
        $('#editNoticeTypeError').text('');
        $('#editPublishedDateError').text('');
        $('#editExpiryDateError').text('');

        var formData = $(this).serialize(); // Serialize form data

        $.ajax({
            url: 'manage_notices.php', // Send to the same PHP file for processing (update_notice_ajax action)
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        $('#editNoticeModal').modal('hide'); // Hide modal on success
                        location.reload(); // Reload the page to show updated data
                    });
                } else {
                    // Display validation errors
                    if (response.errors) {
                        if (response.errors.Title) {
                            $('#editTitleError').text(response.errors.Title);
                        }
                        if (response.errors.Description) {
                            $('#editDescriptionError').text(response.errors.Description);
                        }
                        if (response.errors.NoticeType) {
                            $('#editNoticeTypeError').text(response.errors.NoticeType);
                        }
                        if (response.errors.PublishedDate) {
                            $('#editPublishedDateError').text(response.errors.PublishedDate);
                        }
                        if (response.errors.ExpiryDate) {
                            $('#editExpiryDateError').text(response.errors.ExpiryDate);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed!',
                            text: response.message || 'An unknown error occurred during update.',
                            showConfirmButton: true
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while trying to update. Please try again.',
                    showConfirmButton: true
                });
            }
        });
    });

    // Handle Delete button click
    $(document).on('click', '.delete-btn', function() {
        var noticeId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, send an AJAX request to delete_notice.php
                $.ajax({
                    url: 'delete_notice.php', // Path to your delete_notice.php file
                    type: 'POST',
                    data: {
                        id: noticeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            ).then(() => {
                                location.reload(); // Simple way to refresh data
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        Swal.fire(
                            'Error!',
                            'An error occurred during deletion. Please try again.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Handle View button click
    $(document).on('click', '.view-btn', function() {
        var noticeId = $(this).data('id');

        // Fetch notice data via AJAX
        $.ajax({
            url: 'fetch_notice_data.php', // Use the same file for viewing, just fetch data
            type: 'POST',
            data: {
                id: noticeId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Populate the View Notice Modal fields
                    $('#viewId').text(response.data.id);
                    $('#viewTitle').text(response.data.title);
                    $('#viewDescription').text(response.data.description);
                    $('#viewNoticeType').text(response.data.notice_type);
                    $('#viewPublishedDate').text(response.data.published_date);
                    $('#viewExpiryDate').text(response.data.expiry_date || 'N/A'); // Display 'N/A' if null
                    $('#viewIsActive').text(response.data.is_active == 1 ? 'Yes' : 'No');

                    $('#viewNoticeModal').modal('show'); // Show the Notice View Modal
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to fetch notice data for viewing.',
                        showConfirmButton: true
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while fetching notice data. Please try again.',
                    showConfirmButton: true
                });
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php'; // Adjust path as necessary for your project structure
?>