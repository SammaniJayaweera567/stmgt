<?php
ob_start();
// Since this file is in the 'web' folder, the path to init.php is ../init.php
include '../init.php'; 

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use a single database connection for the entire script
$db = dbConn(); 

// 1. Get the class_level_id from the URL that was passed from the homepage
$level_id = isset($_GET['level_id']) ? (int)dataClean($_GET['level_id']) : 0;

// It's good practice to get the level name to display it as a title
$level_name = '';
if ($level_id > 0) {
    $sql_level = "SELECT level_name FROM class_levels WHERE id = " . $level_id;
    $result_level_name = $db->query($sql_level);
    if ($result_level_name && $result_level_name->num_rows > 0) {
        $level_name = $result_level_name->fetch_assoc()['level_name'];
    } else {
        // If level_id is valid but no name found (e.g., ID doesn't exist)
        echo "Error: Invalid grade level selected.";
        exit();
    }
} else {
    // If no level_id is provided, show an error or redirect
    echo "Error: Please select a grade level first.";
    exit();
}

// 2. Fetch only the active subjects associated with the selected class_level_id
$sql_subjects = "SELECT 
                    s.id, 
                    s.subject_name, 
                    s.subject_code 
                 FROM 
                    class_levels_subjects cls
                 JOIN 
                    subjects s ON cls.subject_id = s.id
                 WHERE 
                    cls.class_level_id = " . $level_id . " 
                    AND cls.status = 'Active' 
                    AND s.status = 'Active' 
                 ORDER BY 
                    s.subject_name ASC";
                    
$result_subjects = $db->query($sql_subjects);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects for <?= htmlspecialchars($level_name) ?></title>
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .page-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }

    .page-header {
        text-align: center;
        color: #343a40;
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-weight: 700;
    }

    .page-header span {
        color: #e0703c;
    }

    .subjects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    .subject-card {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        text-align: center;
        padding: 30px;
        transition: all 0.3s ease;
    }

    .subject-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .subject-card h3 {
        margin-top: 0;
        color: #1cc1ba;
    }

    .subject-card p {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .subject-card .btn-view-classes {
        text-decoration: none;
        color: #fff;
        background-color: #e0703c;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 500;
        display: inline-block;
        margin-top: 15px;
    }

    .subject-card .btn-view-classes:hover {
        background-color: #c76131;
    }

    .no-subjects {
        text-align: center;
        padding: 50px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Added shadow for consistency */
    }
    </style>
</head>

<body>

    <div class="page-container">
        <div class="page-header">
            <h1>Subjects for <span><?= htmlspecialchars($level_name) ?></span></h1>
            <p>Select a subject to view available classes.</p>
        </div>

        <div class="subjects-grid">
            <?php
            // 3. Loop through the results and display each subject as a card
            if ($result_subjects && $result_subjects->num_rows > 0) {
                while ($subject = $result_subjects->fetch_assoc()) {
            ?>
                <div class="subject-card">
                    <h3><?= htmlspecialchars($subject['subject_name']) ?></h3>
                    <p>Code: <?= htmlspecialchars($subject['subject_code']) ?></p>

                    <a href="classes.php?level_id=<?= htmlspecialchars($level_id) ?>&subject_id=<?= htmlspecialchars($subject['id']) ?>"
                        class="btn-view-classes">
                        View Classes
                    </a>
                </div>
            <?php
                }
            } else {
                echo '<div class="no-subjects"><p>No subjects are currently available for <strong>' . htmlspecialchars($level_name) . '</strong> grade level. Please check back later!</p></div>';
            }
            ?>
        </div>
    </div>

<?php
$content = ob_get_clean();
include 'layouts.php'; // Your main layout file for the website
?>
