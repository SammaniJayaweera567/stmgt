<?php
// get_available_rooms.php
include '../../init.php'; // Include your DB connection and helper functions

header('Content-Type: application/json');
$db = dbConn();

// Sanitize all GET inputs
$day_of_week = dataClean($_GET['day_of_week'] ?? '');
$start_time = dataClean($_GET['start_time'] ?? '');
$end_time = dataClean($_GET['end_time'] ?? '');
// The class ID to exclude from conflict checks (important for editing)
$exclude_class_id = isset($_GET['exclude_class_id']) ? intval($_GET['exclude_class_id']) : 0;

// This file serves both add_classes.php and edit_classes.php
// We differentiate the request based on the presence of 'exclude_class_id'

// --- LOGIC FOR EDIT PAGE ---
if ($exclude_class_id > 0) {
    // Prepare the JSON response structure that edit_classes.php JavaScript expects
    $response = [
        'current_room' => null,      // To hold the room currently assigned to the class being edited
        'available_rooms' => []    // To hold all other rooms that are free
    ];

    // Step 1: Always fetch the current room for the class being edited
    $sql_current_room = "SELECT cr.id, cr.room_name, cr.capacity FROM class_rooms cr
                         JOIN classes c ON c.class_room_id = cr.id
                         WHERE c.id = '$exclude_class_id'";
    $result_current_room = $db->query($sql_current_room);
    if ($result_current_room && $result_current_room->num_rows > 0) {
        $response['current_room'] = $result_current_room->fetch_assoc();
    }

    // Step 2: If time slot is provided, find other available rooms
    if (!empty($day_of_week) && !empty($start_time) && !empty($end_time)) {
        // Find all room IDs that are already BOOKED in the given time slot
        $sql_booked_rooms = "SELECT class_room_id FROM classes 
                             WHERE day_of_week = '$day_of_week' 
                             AND (start_time < '$end_time' AND end_time > '$start_time')
                             AND id != '$exclude_class_id'"; // Exclude self
        
        $result_booked = $db->query($sql_booked_rooms);
        $booked_room_ids = [];
        if ($result_booked) {
            while ($row = $result_booked->fetch_assoc()) {
                $booked_room_ids[] = $row['class_room_id'];
            }
        }

        // Get all active rooms that are NOT in the booked list
        $sql_available_rooms = "SELECT id, room_name, capacity FROM class_rooms WHERE status = 'Active'";
        if (!empty($booked_room_ids)) {
            $id_list = implode(',', array_unique($booked_room_ids));
            $sql_available_rooms .= " AND id NOT IN ($id_list)";
        }
        $sql_available_rooms .= " ORDER BY room_name ASC";

        $result_available = $db->query($sql_available_rooms);
        if ($result_available) {
            while ($row = $result_available->fetch_assoc()) {
                $response['available_rooms'][] = $row;
            }
        }
    }
    
    // Return the final JSON object to the AJAX call for the edit page
    echo json_encode($response);
    exit();
}

// --- LOGIC FOR ADD PAGE (and other cases) ---
else {
    if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
        echo json_encode([]);
        exit();
    }

    // Find all rooms that are BOOKED during the given time slot
    $sql_booked_rooms = "SELECT class_room_id FROM classes 
                         WHERE day_of_week = '$day_of_week' 
                         AND (start_time < '$end_time' AND end_time > '$start_time')";
    
    $result_booked = $db->query($sql_booked_rooms);
    $booked_room_ids = [];
    if ($result_booked) {
        while ($row = $result_booked->fetch_assoc()) {
            $booked_room_ids[] = $row['class_room_id'];
        }
    }

    // Now, get all active rooms that are NOT in the booked list
    $sql_available_rooms = "SELECT id, room_name, capacity FROM class_rooms WHERE status = 'Active'";
    if (!empty($booked_room_ids)) {
        $id_list = implode(',', array_unique($booked_room_ids));
        $sql_available_rooms .= " AND id NOT IN ($id_list)";
    }
    $sql_available_rooms .= " ORDER BY room_name ASC";
    
    $result_available = $db->query($sql_available_rooms);
    $simple_response = [];
    if ($result_available) {
        while ($row = $result_available->fetch_assoc()) {
            $simple_response[] = $row;
        }
    }
    
    // Return a simple array of rooms, which add_classes.php JavaScript expects
    echo json_encode($simple_response);
    exit();
}
?>
