<?php
// get_subjects_by_level.php
include '../../init.php';

header('Content-Type: application/json');
$db = dbConn();

// Sanitize the input
$class_level_id = isset($_GET['class_level_id']) ? intval($_GET['class_level_id']) : 0;

if ($class_level_id > 0) {
    // FINAL CORRECTED LOGIC:
    // This query now uses your existing 'class_levels_subjects' table to find the correct subjects.
    $sql = "SELECT s.id, s.subject_name 
            FROM subjects s
            JOIN class_levels_subjects cls ON s.id = cls.subject_id
            WHERE cls.class_level_id = '$class_level_id' 
            AND s.status = 'Active' 
            AND cls.status = 'Active'
            ORDER BY s.subject_name ASC";
    
    $result = $db->query($sql);
    
    $subjects = [];
    if($result){
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    
    echo json_encode($subjects);

} else {
    // Return an empty array if no valid level id is provided
    echo json_encode([]);
}
?>
