<?php
ob_start();
include '../../init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Account Activation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Activate Your Parent Account</h3>
                </div>
                <div class="card-body p-4">
                    <?php if(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars(urldecode($_GET['message'] ?? 'An unknown error occurred.')) ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="text-muted">
                        Please enter your National Identity Card (NIC) number that you provided during your child's registration. We will find your details and help you create your account.
                    </p>
                    
                    <form method="post" action="process_parent_activation.php">
                        <div class="mb-3">
                            <label for="guardian_nic" class="form-label"><strong>Your NIC Number</strong></label>
                            <input type="text" id="guardian_nic" name="guardian_nic" class="form-control" placeholder="Enter your NIC number" required>
                        </div>
                        <button type="submit" name="find_account" class="btn btn-primary w-100 py-2">Find My Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered HTML content into the $content variable and include the layout file
$content = ob_get_clean();
include '../layouts.php'; // This path is correct according to your structure
?>
