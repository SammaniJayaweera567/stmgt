<?php
ob_start();
include '../../init.php';
if (!hasPermission($_SESSION['user_id'], 'districts_report')) {
    // Set error message in session
    $_SESSION['error'] = "⚠️ You don't have permission to access this page.";

    // Redirect back using HTTP_REFERER if available, else fallback to dashboard
    $backUrl = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    header("Location: $backUrl");
    exit;
}
?>
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">View Districts</h3>
            </div>

            <!-- Data insert validation -->
            <?php
            if ($_SERVER['REQUEST_METHOD'] == "POST") {
                extract($_POST);

                $DistrictName = dataClean($DistrictName);

                $messages = array();

                //Form required fields validation
                if (empty($DistrictName)) {
                    $messages['DistrictName'] = "The District Name Should not be blank...!";
                }

                if (empty($messages)) {
                    $db = dbConn();
                    $sql = "INSERT INTO districts(DistrictName) VALUES('$DistrictName')";
                    $db->query($sql);
                }
            }

            ?>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>
                <div class="card-body">
                    <div class="form-group">
                        <label for="DistrictName">District Name</label>
                        <input type="text" class="form-control" id="DistrictName" name="DistrictName" placeholder="Enter First Name">
                        <span class="text-danger"><?= @$messages['DistrictName'] ?></span>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">

        <!-- Data Display -->
        <?php
        $db = dbConn();
        $sql = "SELECT * FROM districts";
        $result = $db->query($sql);
        ?>



        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>District Name</th>
                </tr>
            </thead>
            <tbody>
                <!-- Fetch data -->
                <?php

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                ?>
                        <tr>
                            <td><?= $row['Id'] ?></td>
                            <td><?= $row['DistrictName'] ?></td>
                        </tr>
                <?php
                    }
                }
                ?>
            </tbody>

        </table>



    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>