<?php 
if (!empty($_SESSION['error'])) {
    echo "<script>
        $(document).ready(function() {
            toastr.error('" . addslashes($_SESSION['error']) . "');
        });
    </script>";
    unset($_SESSION['error']);
}


if (!empty($_SESSION['success'])) {
    echo "<script>
        $(document).ready(function() {
            toastr.success('" . addslashes($_SESSION['success']) . "');
        });
    </script>";
    unset($_SESSION['success']);
}
if (!empty($_SESSION['info'])) {
    echo "<script>
        $(document).ready(function() {
            toastr.info('" . addslashes($_SESSION['info']) . "');
        });
    </script>";
    unset($_SESSION['info']);
}