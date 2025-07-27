<?php
ob_start();
include '../../../init.php';
$db = dbConn();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../dashboard.php.php");
    exit();
}
if (!hasPermission($_SESSION['user_id'], 'permission')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}

if (isset($_POST['add_permission'])) {
    $name = $_POST['name'];
    $slug = $_POST['slug'];

    try {
        // Enable mysqli exceptions for error handling
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $stmt = $db->prepare("INSERT INTO permissions (name, slug) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $slug);
        $stmt->execute();

        $_SESSION['success'] = "Permission created successfully.";
        header("Location: permission.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        // Handle error gracefully
        $_SESSION['error'] = "Error creating permission: " . $e->getMessage();
        header("Location: create.php");
        exit();
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text">
            <i class="fas fa-users mt-1 me-2 mr-2" style="font-size: 17px;"></i>
            <h5 class="w-auto">Permission</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <?php if (hasPermission($_SESSION['user_id'], 'permission')) { ?>
            <div class="d-flex justify-content-start mb-4">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Permission</a>
            </div>
            <?php } ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title" style="font-size: 1.05rem !important;">User List</h6>
                </div>
                <div class="card-body">
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Permission Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>
        <div class="mb-3">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" class="form-control" name="slug" required>
        </div>
        <button type="submit" name="add_permission" class="btn btn-success">Create</button>
    </form>
                </div>
            </div>
        </div>  
    </div>
    <

<?php $content = ob_get_clean(); include '../../layouts.php'; ?>
