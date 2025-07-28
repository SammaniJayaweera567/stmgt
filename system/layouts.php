<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thaksalawa</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AdminLTE 3 | Dashboard</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/jqvmap/jqvmap.min.css">
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/sweetalert2/sweetalert2.min.css">

    <!-- Theme style -->
    <link rel="stylesheet" href="<?= SYS_URL ?>dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/daterangepicker/daterangepicker.css">

    <!-- summernote -->
    <link rel="stylesheet" href="<?= SYS_URL ?>plugins/summernote/summernote-bs4.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <link rel="stylesheet" href="<?= SYS_URL ?>dist/css/style.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <script src="<?= SYS_URL ?>plugins/jquery/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">


    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <script src="<?= SYS_URL ?>dist/js/app-scripts.js"></script>
    <script src="<?= SYS_URL ?>plugins/chart.js/Chart.min.js"></script>
    <script src="<?= SYS_URL ?>plugins/sweetalert2/sweetalert2.all.min.js"></script>

</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <?php
        include 'alerts.php'; 
    ?> 
    
    <div class="wrapper">

        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="<?= SYS_URL ?>dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60"
                width="60">
        </div>

        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <!-- <li class="nav-item d-none d-sm-inline-block">
                    <a href="index3.html" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link">Contact</a>
                </li> -->
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <i class="fas fa-search"></i>
                    </a>
                    <div class="navbar-search-block">
                        <form class="form-inline">
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-navbar" type="search" placeholder="Search"
                                    aria-label="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-navbar" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-comments"></i>
                        <span class="badge badge-danger navbar-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="<?= SYS_URL ?>dist/img/user1-128x128.jpg" alt="User Avatar"
                                    class="img-size-50 mr-3 img-circle">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                        Brad Diesel
                                        <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                    </h3>
                                    <p class="text-sm">Call me whenever you can...</p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="<?= SYS_URL ?>dist/img/user8-128x128.jpg" alt="User Avatar"
                                    class="img-size-50 img-circle mr-3">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                        John Pierce
                                        <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                                    </h3>
                                    <p class="text-sm">I got your message bro</p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <div class="media">
                                <img src="<?= SYS_URL ?>dist/img/user3-128x128.jpg" alt="User Avatar"
                                    class="img-size-50 img-circle mr-3">
                                <div class="media-body">
                                    <h3 class="dropdown-item-title">
                                        Nora Silvester
                                        <span class="float-right text-sm text-warning"><i
                                                class="fas fa-star"></i></span>
                                    </h3>
                                    <p class="text-sm">The subject goes here</p>
                                    <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-warning navbar-badge">15</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">15 Notifications</span>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i> 4 new messages
                            <span class="float-right text-muted text-sm">3 mins</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-users mr-2"></i> 8 friend requests
                            <span class="float-right text-muted text-sm">12 hours</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-file mr-2"></i> 3 new reports
                            <span class="float-right text-muted text-sm">2 days</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php" role="button">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="index3.html" class="brand-link text-center">
                <img src="<?= SYS_URL ?>dist/img/thaksalawa.png" alt="AdminLTE Logo" class="brand-image"
                    style="opacity: .8;">
            </a>

            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= SYS_URL ?>dist/img/user (1).png" class="img-circle elevation-2 mt-2"
                            alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?= $_SESSION['user_name'] ?? 'Guest User' ?></a>
                        <small class="text-white"><?= $_SESSION['user_role_name'] ?? 'No Role' ?></small>
                    </div>
                </div>

                <div class="form-inline">
                    <div class="input-group" data-widget="sidebar-search">
                        <input class="form-control form-control-sidebar" type="search" placeholder="Search"
                            aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-sidebar">
                                <i class="fas fa-search fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">

                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>dashboard_admin.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/activity.png" alt="Dashboard" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p class="d-inline">Dashboard</p>
                            </a>
                        </li>
                        <?php if (hasPermission($_SESSION['user_id'], 'user')) { ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/group.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Users
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'user_manage')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>users/manage.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage User Details</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Re-set Password</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php } ?>
                        <?php if (hasPermission($_SESSION['user_id'], 'academic-years')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>academic-years/manage.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/book.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Academic Year
                                </p>
                            </a>
                        </li>
                        <?php } ?>
                         <?php if (hasPermission($_SESSION['user_id'], 'classes')) { ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/book.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Classes
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_classes')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/manage_classes.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Classes</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_class_level')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/manage_class_levels.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Class Levels</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_class_type')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/manage_class_types.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Class Types</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_class_room')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/manage_class_rooms.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Class Room</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_class_subject')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/manage_class_levels_subjects.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Class Levels and Subjects</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_class_material')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>classes/materials/manage_materials.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Class Materials</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'class_routine')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>routine/view_class_schedule.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/book.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    View Class Routine
                                </p>
                            </a>
                        </li>
                        <?php } ?>

                         <?php if (hasPermission($_SESSION['user_id'], 'subject')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>subjects/manage_subjects.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/book.png" alt="Teachers" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Manage Subjects
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_subject')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>subjects/manage_subjects.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Subjects</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'parent_list')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>parents/manage_parents.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/classroom.png" alt="Teachers" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Parent List
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_parent')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>parents/manage_parents.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Parents</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'student_enrollment')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>student-enrollments/manage_enrollments.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/student.png" alt="Teachers" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Manage Student Enrollment
                                </p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>students/manage_students.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/classroom.png" alt="Teachers" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Student List
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                             <?php if (hasPermission($_SESSION['user_id'], 'manage_student_enrollment')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>student-enrollments/manage_enrollments.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Student Enrollment</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                         <?php if (hasPermission($_SESSION['user_id'], 'teachers')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>teachers/manage.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/classroom.png" alt="Teachers" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Teachers
                                </p>
                            </a>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'attendance')) { ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/calendar.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Manage Daily Attendance
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                 <?php if (hasPermission($_SESSION['user_id'], 'manage_attendance')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>attendance/mark_attendance.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Mark Attendance</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>attendance/view_attendance.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>View Attendance</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                         <?php if (hasPermission($_SESSION['user_id'], 'assement_grade')) { ?>           
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/calendar.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Manage Assement Grades
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_assement_grade')) { ?>    
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>grades/manage_grades.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Grades</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'assement')) { ?>   
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/test.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Assesments
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_assesments')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>assessments/grades/manage_grades.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Grades</p>
                                    </a>
                                </li>
                                <?php } ?>

                                  <?php if (hasPermission($_SESSION['user_id'], 'manage_exam')) { ?>   
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>assessments/exams/manage_exams.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Exams</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>

                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_assignment')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>assessments/assignments/manage_assignments.php"
                                        class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Assignments</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>

                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_quizz')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>assessments/quizzes/manage_quizzes.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Quizzes</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>

                        <?php if (hasPermission($_SESSION['user_id'], 'payments')) { ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/credit-card.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Payments
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_discount_type')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/discount_types/manage_types.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Discounts Types</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_discount_type')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/record_payment.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Payments Records</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_student_discount')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/manage_discounts.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Discounts</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'manage_student_discount')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/generate_invoices.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Generate Invoices</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'review_slips')) { ?> 
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/review_slips.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Review Slips</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'view_invoices')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>payments/view_invoices.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>View Invoices</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                        <!-- 
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/calculator.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Accounting Section
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>accounts/manage_income.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Income</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>accounts/manage_expenses.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Expenses</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>accounts/expense_category.php" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Expense Category</p>
                                    </a>
                                </li>
                            </ul>
                        </li> -->
                         
                        <?php if (hasPermission($_SESSION['user_id'], 'manage_notice')) { ?>
                        <li class="nav-item">
                            <a href="<?= SYS_URL ?>notice-board/manage_notices.php" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/message-board.png" alt="Users" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Notice Board
                                </p>
                            </a>
                        </li>
                        <?php } ?>
                        <?php if (hasPermission($_SESSION['user_id'], 'setting')) { ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <img src="<?= SYS_URL ?>dist/img/setting.png" alt="Settings" width="22" height="22"
                                    style="margin-right: 8px;">
                                <p>
                                    Settings
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <?php if (hasPermission($_SESSION['user_id'], 'reports')) { ?>
                                <li class="nav-item">
                                    <a href="#" class="nav-link"> <i class="far fa-circle nav-icon"></i>
                                        <p>
                                            Reports
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <?php if (hasPermission($_SESSION['user_id'], 'enrollment_report')) { ?>
                                        <li class="nav-item">
                                            <a href="<?= SYS_URL ?>settings/reports/enrollment_report.php"
                                                class="nav-link">
                                                <i class="fas fa-genderless nav-icon"
                                                    style="font-size: 0.6rem; vertical-align: middle;"></i>
                                                <p>Enrollment Report</p>
                                            </a>
                                        </li>
                                        <?php } ?>
                                        <?php if (hasPermission($_SESSION['user_id'], 'grade_distribution_report')) { ?>
                                        <li class="nav-item">
                                            <a href="<?= SYS_URL ?>settings/reports/grade_report.php" class="nav-link">
                                                <i class="fas fa-genderless nav-icon"
                                                    style="font-size: 0.6rem; vertical-align: middle;"></i>
                                                <p>Grade Distribution Report</p>
                                            </a>
                                        </li>
                                         <?php } ?>
                                        <?php if (hasPermission($_SESSION['user_id'], 'attendance_report')) { ?>
                                        <li class="nav-item">
                                            <a href="<?= SYS_URL ?>settings/reports/attendance_report.php"
                                                class="nav-link">
                                                <i class="fas fa-genderless nav-icon"
                                                    style="font-size: 0.6rem; vertical-align: middle;"></i>
                                                <p>Attendance Report</p>
                                            </a>
                                        </li>
                                         <?php } ?>
                                        <?php if (hasPermission($_SESSION['user_id'], 'student_report')) { ?>
                                        <li class="nav-item">
                                            <a href="<?= SYS_URL ?>settings/reports/select_student.php"
                                                class="nav-link">
                                                <i class="fas fa-genderless nav-icon"
                                                    style="font-size: 0.6rem; vertical-align: middle;"></i>
                                                <p>Select Student</p>
                                            </a>
                                        </li>
                                         <?php } ?>
                                        <?php if (hasPermission($_SESSION['user_id'], 'student_progress_report')) { ?>
                                        <li class="nav-item">
                                            <a href="<?= SYS_URL ?>settings/reports/generate_progress_report.php"
                                                class="nav-link">
                                                <i class="fas fa-genderless nav-icon"
                                                    style="font-size: 0.6rem; vertical-align: middle;"></i>
                                                <p>Generate Students Progress Report</p>
                                            </a>
                                        </li>
                                         <?php } ?>
                                    </ul>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'districts_report')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>districts/view.php" class="nav-link"> <i
                                            class="far fa-circle nav-icon"></i>
                                        <p>Districts</p>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (hasPermission($_SESSION['user_id'], 'permission')) { ?>
                                <li class="nav-item">
                                    <a href="<?= SYS_URL ?>settings/permission/permission.php" class="nav-link"> <i
                                            class="far fa-circle nav-icon"></i>
                                        <p>Permissions</p>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper bg-white">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                        <!-- <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard v1</li>
                            </ol>
                        </div> -->
                    </div>
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    <?= $content ?>
                </div>
            </section>
        </div>
        <footer class="main-footer">
            <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">BIT Project | Sammani
                    Jayaweera</a>.</strong>
            All rights reserved.
        </footer>

        <aside class="control-sidebar control-sidebar-dark">
        </aside>
    </div>



    <script src="<?= SYS_URL ?>plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
    $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="<?= SYS_URL ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="<?= SYS_URL ?>plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="<?= SYS_URL ?>plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="<?= SYS_URL ?>plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="<?= SYS_URL ?>plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?= SYS_URL ?>plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="<?= SYS_URL ?>plugins/moment/moment.min.js"></script>
    <script src="<?= SYS_URL ?>plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="<?= SYS_URL ?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="<?= SYS_URL ?>plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="<?= SYS_URL ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= SYS_URL ?>dist/js/adminlte.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?= SYS_URL ?>dist/js/demo.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="<?= SYS_URL ?>dist/js/pages/dashboard.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- <script src="<?= SYS_URL ?>dist/js/app-scripts.js"></script> -->

</body>

</html>