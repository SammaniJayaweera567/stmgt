<!DOCTYPE html>
<html lang="en">

<head>
    <title>Stmgt</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,700,900" rel="stylesheet">
    <link rel="stylesheet" href="<?= WEB_URL ?>fonts/icomoon/style.css">

    <link rel="stylesheet" href="<?= WEB_URL ?>css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= WEB_URL ?>css/jquery-ui.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>css/owl.carousel.min.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>css/owl.theme.default.min.css">

    <link rel="stylesheet" href="<?= WEB_URL ?>css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>fonts/flaticon/font/flaticon.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>css/aos.css">
    <link href="<?= WEB_URL ?>css/jquery.mb.YTPlayer.min.css" media="all" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="<?= WEB_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= WEB_URL ?>css/custom.css">

    <style>
        body {
            padding-top: 98px; 
        }
        .site-wrap {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main.site-content-area {
            flex-grow: 1;
        }

        .btn-primary {
            --bs-btn-color: #fff;
            --bs-btn-bg: #e0703c;
            --bs-btn-border-color: #e0703c;
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #c76131; /* Darker on hover */
            --bs-btn-hover-border-color: #c76131;
            --bs-btn-active-bg: #c76131;
            --bs-btn-active-border-color: #c76131;
        }

        .btn-outline-warning {
            --bs-btn-color: #46667b;
            --bs-btn-border-color: #46667b;
            --bs-btn-hover-color: #fff; /* White text on hover */
            --bs-btn-hover-bg: #46667b;
            --bs-btn-hover-border-color: #46667b;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: #46667b;
            --bs-btn-active-border-color: #46667b;
        }
    </style>
</head>

<body data-spy="scroll" data-target=".site-navbar-target" data-offset="300" data-aos-easing="slide" data-aos-duration="800" data-aos-delay="0">

    <div class="site-wrap">

        <div class="site-mobile-menu site-navbar-target">
            <div class="site-mobile-menu-header">
                <div class="site-mobile-menu-close mt-3">
                    <span class="icon-close2 js-menu-toggle"></span>
                </div>
            </div>
            <div class="site-mobile-menu-body"></div>
        </div>

        <header class="site-navbar py-2 js-sticky-header site-navbar-target" role="banner">
            <div class="container">
                <div class="d-flex align-items-center">
                    <div class="site-logo">
                        <a href="<?= WEB_URL ?>index.php" class="d-block">
                            <img src="<?= WEB_URL ?>images/Thaksala-Logo.png" alt="Image" class="img-fluid">
                        </a>
                    </div>
                    <div class="mr-auto mx-auto">
                        <nav class="site-navigation position-relative text-right" role="navigation">
                            <ul class="site-menu main-menu js-clone-nav mr-auto d-none d-lg-block">
                                <li class="active">
                                    <a href="<?= WEB_URL ?>index.php" class="nav-link text-left">Home</a>
                                </li>
                                <li>
                                    <a href="<?= WEB_URL ?>teachers.php" class="nav-link text-left">Our Teachers</a>
                                </li>
                                <li>
                                    <a href="<?= WEB_URL ?>schedules.php" class="nav-link text-left">Class Schedules</a>
                                </li>
                                <li>
                                    <a href="#" class="nav-link text-left">Contact</a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <div class="ml-auto">
                        <div class="d-flex align-items-center">

                            <?php
                            // Get the filename of the currently executing script
                            $current_script = basename($_SERVER['SCRIPT_NAME']);

                            // Define which pages are part of the registration flow
                            $registration_pages = ['register_as.php', 'student_registration.php', 'parent_registration.php'];

                            // Show Registration button if NOT on a registration page
                            if (!in_array($current_script, $registration_pages)) {
                                echo '<a href="' . WEB_URL . 'auth/register_as.php" class="btn btn-primary btn-md mr-2">Registration</a>';
                            }

                            // Show Login button if NOT on the login page
                            if ($current_script !== 'login.php') {
                                echo '<a href="' . WEB_URL . 'auth/login.php" class="btn btn-outline-warning btn-md">Login</a>';
                            }
                            ?>

                            <a href="#" class="d-inline-block d-lg-none site-menu-toggle js-menu-toggle text-black ml-3">
                                <span class="icon-menu h3"></span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <main class="site-content-area">
            <?php echo $content; ?>
        </main>

        <div class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3">
                        <p class="mb-4"><img src="<?= WEB_URL ?>images/Thaksala-logo-footer.png" alt="Image" class="img-fluid"></p>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Beatae nemo minima qui dolor, iusto iure.</p>
                        <p><a href="#">Learn More</a></p>
                    </div>
                    <div class="col-lg-3">
                        <h3 class="footer-heading"><span>Our Campus</span></h3>
                        <ul class="list-unstyled">
                            <li><a href="#">Acedemic</a></li>
                            <li><a href="#">News</a></li>
                            <li><a href="#">Our Interns</a></li>
                            <li><a href="#">Our Leadership</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Human Resources</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3">
                        <h3 class="footer-heading"><span>Our Courses</span></h3>
                        <ul class="list-unstyled">
                            <li><a href="#">Math</a></li>
                            <li><a href="#">Science &amp; Engineering</a></li>
                            <li><a href="#">Arts &amp; Humanities</a></li>
                            <li><a href="#">Economics &amp; Finance</a></li>
                            <li><a href="#">Business Administration</a></li>
                            <li><a href="#">Computer Science</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3">
                        <h3 class="footer-heading"><span>Contact</span></h3>
                        <ul class="list-unstyled">
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Support Community</a></li>
                            <li><a href="#">Press</a></li>
                            <li><a href="#">Share Your Story</a></li>
                            <li><a href="#">Our Supporters</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="copyright">
                            <p>
                                Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="icon-heart" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- .site-wrap -->

    <!-- loader -->
    <div id="loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee" /><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#51be78" /></svg></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery-3.3.1.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery-migrate-3.0.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= WEB_URL ?>js/jquery-ui.js"></script>
    <script src="<?= WEB_URL ?>js/popper.min.js"></script>
    <script src="<?= WEB_URL ?>js/bootstrap.min.js"></script>
    <script src="<?= WEB_URL ?>js/owl.carousel.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.stellar.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.countdown.min.js"></script>
    <script src="<?= WEB_URL ?>js/bootstrap-datepicker.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.easing.1.3.js"></script>
    <script src="<?= WEB_URL ?>js/aos.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.fancybox.min.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.sticky.js"></script>
    <script src="<?= WEB_URL ?>js/jquery.mb.YTPlayer.min.js"></script>
    <script src="<?= WEB_URL ?>js/main.js"></script>

</body>
</html>
