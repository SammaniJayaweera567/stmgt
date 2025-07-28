<?php
ob_start();
include '../../../init.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SYS_URL . "login.php");
    exit();
}
$db = dbConn();

// Handle assign permission form submission
if (isset($_POST['assign_permission'])) {
    $role_id = $_POST['role_id'];
    $permissions = $_POST['permissions'] ?? [];

    $db->query("DELETE FROM role_permissions WHERE role_id = $role_id");
    foreach ($permissions as $pid) {
        $db->query("INSERT INTO role_permissions (role_id, permission_id) VALUES ($role_id, $pid)");
    }

    // ✅ Set session BEFORE redirect
    $_SESSION['success'] = "Permissions updated successfully.";
    header("Location: permission.php");
    exit();
}

// Load all permissions and roles
$permissions = $db->query("SELECT * FROM permissions ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$roles = $db->query("SELECT * FROM user_roles ORDER BY RoleName")->fetch_all(MYSQLI_ASSOC);

// Get selected role ID (from GET or POST)
$selected_role_id = $_GET['role_id'] ?? $_POST['role_id'] ?? null;
$assigned_permission_ids = [];

if ($selected_role_id) {
    $stmt = $db->query("SELECT permission_id FROM role_permissions WHERE role_id = $selected_role_id");
    $assigned_permission_ids = array_column($stmt->fetch_all(MYSQLI_ASSOC), 'permission_id');
}



// Define permission groups
$permission_groups = [
    [
        'label' => 'User Management',
        'main' => [1],
        'sub' => [2],
        'items' => [3, 4, 5, 6]
    ],
    [
        'label' => 'Academic Years',
        'main' => [7],
        'sub' => [8],
        'items' => [9, 10, 11]
    ],
    [
        'label' => 'Classes',
        'main' => [12],
        'sub' => [13, 18, 19, 23, 27, 31, 35],
        'items' => [14, 15, 16, 17, 20, 21, 22, 24, 25, 26, 28, 29, 30, 32, 33, 34, 36, 37, 38]
    ],
    [
        'label' => 'Class Routine',
        'main' => [39],
        'sub' => [],
        'items' => []
    ],
    [
        'label' => 'Subjects',
        'main' => [40],
        'sub' => [41],
        'items' => [42, 43, 44, 45]
    ],
    [
        'label' => 'Parents',
        'main' => [46],
        'sub' => [47],
        'items' => [48, 49, 50, 51]
    ],
    [
        'label' => 'Student Enrollments',
        'main' => [52],
        'sub' => [53],
        'items' => [54, 55, 56, 57]
    ],
    [
        'label' => 'Teachers',
        'main' => [58],
        'sub' => [59],
        'items' => [60, 61, 62, 63]
    ],
    [
        'label' => 'Attendance',
        'main' => [64],
        'sub' => [65],
        'items' => [66, 67, 68, 69]
    ],
    [
        'label' => 'Assessment Grades',
        'main' => [70],
        'sub' => [71],
        'items' => [72, 73, 74, 75]
    ],
    [
        'label' => 'Exams & Assignments & Quizzes',
        'main' => [76],
        'sub' => [77, 82, 88],
        'items' => [78, 79, 80, 81, 83, 84, 85, 86, 87, 89, 90, 91, 93]
    ],
    [
        'label' => 'Payments & Discounts & Invoices',
        'main' => [94],
        'sub' => [95, 100, 105, 106],
        'items' => [96, 97, 98, 99, 101, 102, 103, 104]
    ],
    [
        'label' => 'Notices',
        'main' => [107],
        'sub' => [],
        'items' => [109, 110, 111, 112]
    ],
    [
        'label' => 'Reports & Settings',
        'main' => [112],
        'sub' => [],
        'items' => [113, 114, 115, 116, 117, 118, 119]
    ],
    [
        'label' => 'Permissions',
        'main' => [120],
        'sub' => [],
        'items' => [121, 122, 123]
    ],
    [
        'label' => 'Others',
        'main' => [],
        'sub' => [],
        'items' => array_filter(array_column($permissions, 'id'), fn($id) => $id > 123)
    ]
];

?>


    <style>
        .permission-menu {
            height: 100%;
            border-right: 1px solid #dee2e6;
            background-color: #fff;
        }
        .permission-menu .list-group-item {
            cursor: pointer;
        }
        .permission-menu .list-group-item.active {
            background-color: #0d6efd;
            color: white;
        }
        .permission-box {
            display: none;
        }
        .permission-box.active {
            display: block;
        }
    </style>
</head>
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
                <a href="create.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add New Permission</a>
            </div>
            <?php } ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title" style="font-size: 1.05rem !important;">User List</h6>
                </div>
                <div class="card-body">
<form method="POST">
    <div class="mb-4 w-50">
        <label for="role_id" class="form-label fw-bold">Select Role</label>
        <select name="role_id" class="form-select" required onchange="this.form.submit()">
            <option value="">Choose a Role</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['Id'] ?>" <?= ($role['Id'] == $selected_role_id ? 'selected' : '') ?>>
                    <?= htmlspecialchars($role['RoleName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($selected_role_id): ?>
        <div class="row">
            <div class="col-md-3 permission-menu">
                <div class="list-group" id="permissionMenu">
                    <?php foreach ($permission_groups as $index => $group): ?>
                        <button type="button" class="list-group-item list-group-item-action <?= $index === 0 ? 'active' : '' ?>" data-target="box<?= $index ?>">
                            <?= htmlspecialchars($group['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-9">
                <?php foreach ($permission_groups as $index => $group): ?>
                    <div class="permission-box <?= $index === 0 ? 'active' : '' ?>" id="box<?= $index ?>">
                        <h4><?= htmlspecialchars($group['label']) ?> Permissions</h4>
                        <hr>

                        <?php if (!empty($group['main'])): ?>
                            <div class="mb-3">
                                <h6 class="text-primary">Main</h6>
                                <?php renderPermissions($permissions, $group['main'], $assigned_permission_ids); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($group['sub'])): ?>
                            <div class="mb-3">
                                <h6 class="text-success">Sub</h6>
                                <?php renderPermissions($permissions, $group['sub'], $assigned_permission_ids); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($group['items'])): ?>
                            <div class="mb-3">
                                <h6 class="text-secondary">Items</h6>
                                <?php renderPermissions($permissions, $group['items'], $assigned_permission_ids); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
       <?php if (hasPermission($_SESSION['user_id'], 'change_permission')) { ?>
        <div class="text-end mt-4">
            <button type="submit" name="assign_permission" class="btn btn-primary px-4">Assign Permissions</button>
        </div>
         <?php } ?>
    <?php endif; ?>
</form>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const menuItems = document.querySelectorAll('#permissionMenu .list-group-item');
    const boxes = document.querySelectorAll('.permission-box');

    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            menuItems.forEach(i => i.classList.remove('active'));
            boxes.forEach(box => box.classList.remove('active'));

            item.classList.add('active');
            const target = item.getAttribute('data-target');
            document.getElementById(target).classList.add('active');
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-permission');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const permissionId = this.getAttribute('data-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This permission will be deleted permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to delete script
                    window.location.href = `delete.php?id=${permissionId}`;
                }
            });
        });
    });
});
</script>

<?php $content = ob_get_clean(); include '../../layouts.php'; ?>
