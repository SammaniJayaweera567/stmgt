<?php

//clean input data
function dataClean($input = null)
{
    // Use the null coalescing operator (??) to provide an empty string if $input is null.
    return htmlspecialchars(stripslashes(trim($input ?? '')));
}

function dbConn()
{
    $conn = new mysqli("localhost", "dev1", "123456", "stmgt", 3307);
    if ($conn->connect_error) {
        die("Connection failed:" . $conn->connect_error);
    } else {
        return $conn;
    }
}

function show_status_message() {
    if (isset($_GET['status'])) {
        $status_map = [
            // Standard Messages
            'added'          => ['message' => 'Record added successfully!', 'icon' => 'success'],
            'updated'        => ['message' => 'Record updated successfully!', 'icon' => 'success'],
            'deleted'        => ['message' => 'Record deleted successfully.', 'icon' => 'warning'],
            'error'          => ['message' => 'An unexpected error occurred.', 'icon' => 'error'],
            'notfound'       => ['message' => 'The requested record was not found.', 'icon' => 'question'],
            
            // Foreign Key / In-Use Error
            'error_fk'       => ['message' => 'Cannot delete! This record is currently in use by other parts of the system.', 'icon' => 'error'],
            
            // Enrollment Specific Errors
            'error_duplicate' => ['message' => 'This student is already enrolled in this class!', 'icon' => 'warning'],
            'error_capacity'  => ['message' => 'Cannot enroll student! The class has reached its maximum capacity.', 'icon' => 'error']
        ];

        $current_status = $_GET['status'];
        $message_to_display = '';
        $icon_to_display = 'info';

        if (array_key_exists($current_status, $status_map)) {
            $message_to_display = $status_map[$current_status]['message'];
            $icon_to_display = $status_map[$current_status]['icon'];
        }

        // Custom message
        if (isset($_GET['message']) && !empty($_GET['message'])) {
            $message_to_display = dataClean($_GET['message']);
        }

        if ($message_to_display) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        position: 'top-end',
                        icon: '{$icon_to_display}',
                        title: '{$message_to_display}',
                        showConfirmButton: false,
                        toast: true,
                        timer: 3000,
                        timerProgressBar: true
                    });
                });
            </script>";
        }
        
        // Clean Url page
        echo "<script>
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('status');
                url.searchParams.delete('message');
                window.history.replaceState({}, '', url.toString());
            }
        </script>";
    }
}

/**
 * --- UPDATED FUNCTION ---
 * Generates a Bootstrap badge with a color based on the provided status string.
 * This is now case-insensitive and handles more status types.
 * @param string $status The status text from the database.
 * @return string The HTML for the Bootstrap badge.
 */
function display_status_badge($status) {
    $status_lower = strtolower(trim($status));
    $badge_class = '';
    switch ($status_lower) {
        case 'active':
        case 'published':
            $badge_class = 'bg-success';
            break;
        case 'inactive':
        case 'draft':
            $badge_class = 'bg-danger';
            break;
        case 'closed':
            $badge_class = 'bg-secondary';
            break;
        default:
            $badge_class = 'bg-dark';
            break;
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

/**
 * --- NEW FUNCTION ---
 * Generates a Bootstrap badge for assessment types (Exam, Assignment, Quiz).
 * @param string $type The assessment type text from the database.
 * @return string The HTML for the Bootstrap badge.
 */
function display_assessment_type_badge($type) {
    $type_lower = strtolower(trim($type));
    $badge_class = '';
    switch ($type_lower) {
        case 'exam':
            $badge_class = 'bg-primary';
            break;
        case 'assignment':
            $badge_class = 'bg-info';
            break;
        case 'quiz':
            $badge_class = 'bg-warning text-dark';
            break;
        default:
            $badge_class = 'bg-dark';
            break;
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($type)) . '</span>';
}


// Function to convert 12-hour format (h:i A) to 24-hour format (HH:MM:SS)
// function convertTo24HourFormat($hour, $minute, $ampm) {
//     $time_str = sprintf("%02d:%02d %s", (int)$hour, (int)$minute, strtoupper($ampm));
//     $dateTime = DateTime::createFromFormat('h:i A', $time_str);
//     return $dateTime ? $dateTime->format('H:i:s') : false;
// }

// Function to convert 24-hour format (HH:MM:SS) to 12-hour format (h:i A)
// function convertTo12HourFormat($time24) {
//     $dateTime = DateTime::createFromFormat('H:i:s', $time24);
//     return $dateTime ? $dateTime->format('h:i A') : false;
// }

// --- Dropdown Data Functions ---

// function getAllClasses() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllClasses.");
//         return [];
//     }
//     $sql = "SELECT
//                 c.Id,
//                 c.class_level_id,
//                 cl.level_name AS class_level_name,
//                 c.subject_id AS class_default_subject_id,
//                 s_cls.subject_name AS class_default_subject_name,
//                 c.class_type_id,
//                 ct.type_name AS class_type_name,
//                 c.class_full_name,
//                 c.class_room_id,
//                 crm.room_name AS class_room_name
//             FROM classes c
//             LEFT JOIN class_levels cl ON c.class_level_id = cl.id
//             LEFT JOIN class_types ct ON c.class_type_id = ct.id
//             LEFT JOIN subjects s_cls ON c.subject_id = s_cls.id
//             LEFT JOIN class_rooms crm ON c.class_room_id = crm.id
//             ORDER BY c.class_full_name ASC";

//     $result = $conn->query($sql);
//     $classes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $classes;
// }

// function getAllSubjects() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllSubjects.");
//         return [];
//     }
//     $result = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
//     $subjects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $subjects;
// }

// function getAllTeachers() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllTeachers.");
//         return [];
//     }
//     $teachers = [];
//     $query = "SELECT id, full_name FROM teachers ORDER BY full_name ASC";
//     $result = $conn->query($query);

//     if ($result && $result->num_rows > 0) {
//         while ($row = $result->fetch_assoc()) {
//             $teachers[] = [
//                 'id' => $row['id'],
//                 'teacher_name' => $row['full_name']
//             ];
//         }
//     }
//     return $teachers;
// }

// function getAllAcademicYears() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllAcademicYears.");
//         return [];
//     }
//     $result = $conn->query("SELECT id, year_name FROM academic_years ORDER BY year_name DESC");
//     $years = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $years;
// }

// function getAllClassLevels() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllClassLevels.");
//         return [];
//     }
//     $result = $conn->query("SELECT id, level_name FROM class_levels ORDER BY level_name ASC");
//     $levels = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $levels;
// }

// function getAllClassTypes() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllClassTypes.");
//         return [];
//     }
//     $result = $conn->query("SELECT id, type_name FROM class_types ORDER BY type_name ASC");
//     $types = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $types;
// }

// function getAllClassRooms() {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllClassRooms.");
//         return [];
//     }
//     $result = $conn->query("SELECT id, room_name FROM class_rooms ORDER BY room_name ASC");
//     $rooms = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     return $rooms;
// }


// --- Class Routine CRUD Functions ---

// function addRoutine($class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $academic_year_id, $class_room_id) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in addRoutine.");
//         return false;
//     }

//     $query = "INSERT INTO class_routines (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, academic_year_id, class_room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

//     $stmt = $conn->prepare($query);
//     if ($stmt === false) {
//         error_log("Add Routine Prepare failed: (" . $conn->errno . ") " . $conn->error);
//         return false;
//     }

//     $bind_success = $stmt->bind_param("iiisssii", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $academic_year_id, $class_room_id);
//     if ($bind_success === false) {
//         error_log("Add Routine Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
//         $stmt->close();
//         return false;
//     }

//     $execute_success = $stmt->execute();
//     if ($execute_success === false) {
//         error_log("Add Routine Execute failed: (" . $stmt->errno . ") " . $stmt->error);
//     }

//     $stmt->close();
//     return $execute_success;
// }

// function updateRoutine($routine_id, $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $academic_year_id, $class_room_id) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in updateRoutine.");
//         return false;
//     }
//     $stmt = $conn->prepare("UPDATE class_routines SET class_id = ?, subject_id = ?, teacher_id = ?, day_of_week = ?, start_time = ?, end_time = ?, academic_year_id = ?, class_room_id = ? WHERE routine_id = ?");
//     if ($stmt === false) {
//         error_log("Update Routine Prepare failed: " . $conn->error);
//         return false;
//     }
//     $stmt->bind_param("iiisssiii", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $academic_year_id, $class_room_id, $routine_id);
//     $result = $stmt->execute();
//     if ($result === false) {
//         error_log("Update Routine Execute failed: (" . $stmt->errno . ") " . $stmt->error);
//     }
//     $stmt->close();
//     return $result;
// }

// function deleteRoutine($routine_id) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in deleteRoutine.");
//         return false;
//     }
//     $stmt = $conn->prepare("DELETE FROM class_routines WHERE routine_id = ?");
//     if ($stmt === false) {
//         error_log("Delete Routine Prepare failed: " . $conn->error);
//         return false;
//     }
//     $stmt->bind_param("i", $routine_id);
//     $result = $stmt->execute();
//     if ($result === false) {
//         error_log("Delete Routine Execute failed: (" . $stmt->errno . ") " . $stmt->error);
//     }
//     $stmt->close();
//     return $result;
// }

// function getRoutineById($routine_id) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getRoutineById.");
//         return false;
//     }
//     $stmt = $conn->prepare("SELECT
//                                 cr.*,
//                                 c.class_full_name,
//                                 c.class_level_id,
//                                 cl.level_name AS class_level_name,
//                                 c.class_type_id,
//                                 ct.type_name AS class_type_name,
//                                 s.subject_name,
//                                 t.full_name AS teacher_name,
//                                 ay.year_name,
//                                 crm.room_name AS room_name
//                             FROM class_routines cr
//                             JOIN classes c ON cr.class_id = c.Id
//                             LEFT JOIN class_levels cl ON c.class_level_id = cl.id
//                             LEFT JOIN class_types ct ON c.class_type_id = ct.id
//                             JOIN subjects s ON cr.subject_id = s.id
//                             JOIN teachers t ON cr.teacher_id = t.id
//                             JOIN academic_years ay ON cr.academic_year_id = ay.id
//                             LEFT JOIN class_rooms crm ON cr.class_room_id = crm.id
//                             WHERE routine_id = ?");
//     if ($stmt === false) {
//         error_log("Get Routine By ID Prepare failed: " . $conn->error);
//         return false;
//     }
//     $stmt->bind_param("i", $routine_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $routine = $result->fetch_assoc();
//     $stmt->close();

//     if ($routine) {
//         if (isset($routine['start_time'])) {
//             $start_parts = explode(':', $routine['start_time']);
//             $routine['start_hour_24h'] = $start_parts[0] ?? '';
//             $routine['start_minute'] = $start_parts[1] ?? '';
//         }
//         if (isset($routine['end_time'])) {
//             $end_parts = explode(':', $routine['end_time']);
//             $routine['end_hour_24h'] = $end_parts[0] ?? '';
//             $routine['end_minute'] = $end_parts[1] ?? '';
//         }
//     }
//     return $routine;
// }

// function getAllClassRoutines($class_level_id = null, $subject_id = null) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in getAllClassRoutines.");
//         return [];
//     }
//     $sql = "SELECT
//                 cr.*,
//                 c.class_full_name,
//                 c.class_level_id,
//                 cl.level_name AS class_level_name,
//                 c.class_type_id,
//                 ct.type_name AS class_type_name,
//                 s.subject_name,
//                 t.full_name AS teacher_name,
//                 ay.year_name,
//                 crm.room_name AS room_name
//             FROM class_routines cr
//             JOIN classes c ON cr.class_id = c.Id
//             LEFT JOIN class_levels cl ON c.class_level_id = cl.id
//             LEFT JOIN class_types ct ON c.class_type_id = ct.id
//             JOIN subjects s ON cr.subject_id = s.id
//             JOIN teachers t ON cr.teacher_id = t.id
//             JOIN academic_years ay ON cr.academic_year_id = ay.id
//             LEFT JOIN class_rooms crm ON cr.class_room_id = crm.id
//             ";
//     $params = [];
//     $types = "";
//     $where_clauses = [];

//     // If a class_level_id is selected, filter by it.
//     if ($class_level_id !== null && $class_level_id !== '') {
//         $where_clauses[] = "c.class_level_id = ?";
//         $params[] = $class_level_id;
//         $types .= "i";
//     }
//     // If a subject_id is selected, filter by it.
//     if ($subject_id !== null && $subject_id !== '') {
//         $where_clauses[] = "cr.subject_id = ?";
//         $params[] = $subject_id;
//         $types .= "i";
//     }

//     if (!empty($where_clauses)) {
//         $sql .= " WHERE " . implode(" AND ", $where_clauses);
//     }

//     $sql .= " ORDER BY FIELD(day_of_week, 'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'), start_time";

//     $stmt = $conn->prepare($sql);
//     if ($stmt === false) {
//         error_log("Get All Class Routines Prepare failed: " . $conn->error);
//         return [];
//     }

//     if (!empty($params)) {
//         $stmt->bind_param($types, ...$params);
//     }
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $routines = [];
//     while ($row = $result->fetch_assoc()) {
//         $routines[] = $row;
//     }
//     $stmt->close();
//     return $routines;
// }

// function checkRoutineOverlap($class_id, $teacher_id, $class_room_id, $day_of_week, $start_time, $end_time, $current_routine_id = null) {
//     $conn = dbConn();
//     if ($conn === null) {
//         error_log("Failed to get DB connection in checkRoutineOverlap.");
//         return ['status' => true, 'message' => 'Database connection error.'];
//     }

//     // --- 1. Check for Class Overlap ---
//     $sql_class_overlap = "SELECT routine_id FROM class_routines
//                               WHERE class_id = ? AND day_of_week = ?
//                               AND (? < end_time AND ? > start_time)";
//     $params_class = [$class_id, $day_of_week, $start_time, $end_time];
//     $types_class = "isss";
//     if ($current_routine_id !== null) {
//         $sql_class_overlap .= " AND routine_id != ?";
//         $params_class[] = $current_routine_id;
//         $types_class .= "i";
//     }
//     $stmt_class = $conn->prepare($sql_class_overlap);
//     if ($stmt_class === false) { error_log("Check Class Overlap Prepare failed: " . $conn->error); return ['status' => true, 'message' => 'Error preparing class overlap check.']; }
//     $stmt_class->bind_param($types_class, ...$params_class);
//     $stmt_class->execute();
//     $result_class = $stmt_class->get_result();
//     $num_rows_class = $result_class->num_rows;
//     $stmt_class->close();
//     if ($num_rows_class > 0) {
//         return ['status' => true, 'message' => 'This time slot for the selected class and day is already taken. Please choose a different time.'];
//     }

//     // --- 2. Check for Teacher Overlap ---
//     $sql_teacher_overlap = "SELECT routine_id FROM class_routines
//                                 WHERE teacher_id = ? AND day_of_week = ?
//                                 AND (? < end_time AND ? > start_time)";
//     $params_teacher = [$teacher_id, $day_of_week, $start_time, $end_time];
//     $types_teacher = "isss";
//     if ($current_routine_id !== null) {
//         $sql_teacher_overlap .= " AND routine_id != ?";
//         $params_teacher[] = $current_routine_id;
//         $types_teacher .= "i";
//     }
//     $stmt_teacher = $conn->prepare($sql_teacher_overlap);
//     if ($stmt_teacher === false) { error_log("Check Teacher Overlap Prepare failed: " . $conn->error); return ['status' => true, 'message' => 'Error preparing teacher overlap check.']; }
//     $stmt_teacher->bind_param($types_teacher, ...$params_teacher);
//     $stmt_teacher->execute();
//     $result_teacher = $stmt_teacher->get_result();
//     $num_rows_teacher = $result_teacher->num_rows;
//     $stmt_teacher->close();
//     if ($num_rows_teacher > 0) {
//         return ['status' => true, 'message' => 'The selected teacher is already scheduled for another class at this time on this day. Please choose a different teacher or time.'];
//     }

//     // --- 3. Check for Class Room Overlap ---
//     $sql_room_overlap = "SELECT routine_id FROM class_routines
//                               WHERE class_room_id = ? AND day_of_week = ?
//                               AND (? < end_time AND ? > start_time)";
//     $params_room = [$class_room_id, $day_of_week, $start_time, $end_time];
//     $types_room = "isss";
//     if ($current_routine_id !== null) {
//         $sql_room_overlap .= " AND routine_id != ?";
//         $params_room[] = $current_routine_id;
//         $types_room .= "i";
//     }
//     $stmt_room = $conn->prepare($sql_room_overlap);
//     if ($stmt_room === false) { error_log("Check Room Overlap Prepare failed: " . $conn->error); return ['status' => true, 'message' => 'Error preparing room overlap check.']; }
//     $stmt_room->bind_param($types_room, ...$params_room);
//     $stmt_room->execute();
//     $result_room = $stmt_room->get_result();
//     $num_rows_room = $result_room->num_rows;
//     $stmt_room->close();
//     if ($num_rows_room > 0) {
//         return ['status' => true, 'message' => 'The selected room is already occupied at this time on this day. Please choose a different room or time.'];
//     }

//     return ['status' => false, 'message' => 'No overlap found.'];
// }



// --- Payment/Invoice Module Functions ---

/**
 * Retrieves student and their associated invoice data based on filters.
 *
 * @param mysqli $conn Database connection object.
 * @param int|null $academic_year_id Optional Academic Year ID to filter by.
 * @param int|null $class_id Optional Class ID to filter by.
 * @param int|null $subject_id Optional Subject ID to filter by. (Requires student_subjects pivot table)
 * @return array An array of student and invoice data.
 */
function getStudentsAndInvoices($conn, $academic_year_id = null, $class_id = null, $subject_id = null) {
    $studentsData = [];
    $sql = "
        SELECT
            s.id AS student_id,
            s.registration_no,
            s.first_name,
            s.last_name,
            i.invoice_id,
            i.title,
            i.total_amount,
            i.amount_paid,
            i.due_amount,
            i.status,
            i.invoice_date
        FROM
            students s
        LEFT JOIN
            invoices i ON s.id = i.student_id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($academic_year_id !== null && $academic_year_id != '') {
        $sql .= " AND s.academic_year_id = ?";
        $types .= 'i';
        $params[] = $academic_year_id;
    }
    if ($class_id !== null && $class_id != '') {
        $sql .= " AND s.class_id = ?";
        $types .= 'i';
        $params[] = $class_id;
    }
    // Subject filter requires a pivot table like student_subjects if a student can have multiple subjects.
    // If you have a direct subject_id in the students table, uncomment and use this:
    /*
    if ($subject_id !== null && $subject_id != '') {
        $sql .= " AND s.subject_id = ?";
        $types .= 'i';
        $params[] = $subject_id;
    }
    */
    // If you need to filter students by subjects they are enrolled in for classes,
    // you would typically join student_class_enrollments and class_subjects tables.
    // For simplicity, I'm keeping the subject filter commented unless explicit table structure is provided.


    $sql .= " ORDER BY s.first_name, i.invoice_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed in getStudentsAndInvoices: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        // Use call_user_func_array for binding parameters dynamically
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $studentsData[] = $row;
        }
    }
    $stmt->close();
    return $studentsData;
}

/**
 * Retrieves payment history for a specific invoice.
 *
 * @param mysqli $conn Database connection object.
 * @param int $invoice_id The ID of the invoice.
 * @return array An array of payment records.
 */
function getPaymentHistory($conn, $invoice_id) {
    $history = [];
    $sql = "SELECT amount, method, payment_date FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed in getPaymentHistory: " . $conn->error);
        return [];
    }
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    $stmt->close();
    return $history;
}

/**
 * Records a new payment and updates the associated invoice.
 *
 * @param mysqli $conn Database connection object.
 * @param int $invoice_id The ID of the invoice being paid.
 * @param float $payment_amount The amount being paid.
 * @param string $payment_method The method of payment (e.g., 'Cash', 'Bank Transfer').
 * @return bool True on success, false on failure.
 */
function takePayment($conn, $invoice_id, $payment_amount, $payment_method) {
    $conn->begin_transaction(); // Start transaction

    try {
        // 1. Get current invoice details
        $sql_invoice = "SELECT total_amount, amount_paid FROM invoices WHERE invoice_id = ?";
        $stmt_invoice = $conn->prepare($sql_invoice);
        if ($stmt_invoice === false) throw new Exception("Prepare failed (invoice select): " . $conn->error);
        $stmt_invoice->bind_param("i", $invoice_id);
        $stmt_invoice->execute();
        $result_invoice = $stmt_invoice->get_result();
        $invoice = $result_invoice->fetch_assoc();
        $stmt_invoice->close();

        if (!$invoice) {
            throw new Exception("Invoice not found.");
        }

        $new_amount_paid = $invoice['amount_paid'] + $payment_amount;
        $new_due_amount = $invoice['total_amount'] - $new_amount_paid;
        $new_status = 'unpaid';
        if ($new_due_amount <= 0) {
            $new_status = 'paid';
            $new_due_amount = 0; // Ensure due amount is not negative
        } elseif ($new_amount_paid > 0) {
            $new_status = 'partially paid';
        }

        // 2. Insert into payments table
        $sql_insert_payment = "INSERT INTO payments (invoice_id, amount, method, payment_date) VALUES (?, ?, ?, CURDATE())";
        $stmt_insert_payment = $conn->prepare($sql_insert_payment);
        if ($stmt_insert_payment === false) throw new Exception("Prepare failed (insert payment): " . $conn->error);
        $stmt_insert_payment->bind_param("ids", $invoice_id, $payment_amount, $payment_method);
        $stmt_insert_payment->execute();
        $stmt_insert_payment->close();

        // 3. Update invoices table
        $sql_update_invoice = "UPDATE invoices SET amount_paid = ?, due_amount = ?, status = ? WHERE invoice_id = ?";
        $stmt_update_invoice = $conn->prepare($sql_update_invoice);
        if ($stmt_update_invoice === false) throw new Exception("Prepare failed (update invoice): " . $conn->error);
        $stmt_update_invoice->bind_param("ddsi", $new_amount_paid, $new_due_amount, $new_status, $invoice_id);
        $stmt_update_invoice->execute();
        $stmt_update_invoice->close();

        $conn->commit(); // Commit the transaction
        return true;
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        error_log("Payment transaction failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds a new monthly invoice for a student.
 * Checks for existing invoices for the same student, month, and year to prevent duplicates.
 *
 * @param mysqli $conn Database connection object.
 * @param int $student_id The ID of the student.
 * @param int $month The month for the invoice (1-12).
 * @param int $year The year for the invoice.
 * @param float $amount The total amount for the monthly invoice.
 * @return int|bool The new invoice_id on success, false on failure or if invoice already exists.
 */
function addMonthlyInvoice($conn, $student_id, $month, $year, $amount) {
    // Check if an invoice for this student for this specific monthly title already exists
    // We use the invoice_date's month and year for a more robust check
    $title_prefix = "Monthly Fees - "; // Standard prefix
    $invoice_month_name = date("F", mktime(0, 0, 0, $month, 1));
    $expected_title_pattern = $title_prefix . $invoice_month_name . " " . $year . "%"; // Use LIKE for flexible title matching

    $sql_check = "SELECT invoice_id FROM invoices WHERE student_id = ? AND title LIKE ? AND YEAR(invoice_date) = ? AND MONTH(invoice_date) = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check === false) {
        error_log("Prepare failed (addMonthlyInvoice check): " . $conn->error);
        return false;
    }
    $stmt_check->bind_param("isii", $student_id, $expected_title_pattern, $year, $month);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        error_log("Duplicate monthly invoice detected for student_id: $student_id, month: $month, year: $year");
        return false; // Invoice already exists for this month/year for this student
    }
    $stmt_check->close();

    $invoice_date = "$year-" . sprintf("%02d", $month) . "-01"; // Set invoice date to the the 1st of the selected month
    $title = $title_prefix . $invoice_month_name . " " . $year;
    $status = ($amount > 0) ? 'unpaid' : 'paid'; // If amount is 0, consider it paid (e.g., scholarship)

    $sql = "INSERT INTO invoices (student_id, title, total_amount, amount_paid, due_amount, status, invoice_date) VALUES (?, ?, ?, '0.00', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed (addMonthlyInvoice insert): " . $conn->error);
        return false;
    }
    $stmt->bind_param("isddss", $student_id, $title, $amount, $amount, $status, $invoice_date);

    if ($stmt->execute()) {
        $new_invoice_id = $conn->insert_id;
        $stmt->close();
        return $new_invoice_id; // Return the new invoice_id
    } else {
        error_log("Add monthly invoice failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
}


/**
 * Retrieves all students for the "Add Invoice" dropdown.
 * This is separate from getStudentsAndInvoices as it doesn't need invoice data or filters.
 *
 * @param mysqli $conn Database connection object.
 * @return array An array of student records (id, first_name, last_name).
 */
function getAllStudentsForDropdown($conn) {
    $students = [];
    $sql = "SELECT id, first_name, last_name FROM students ORDER BY first_name ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    return $students;
}

// Permission 
function hasPermission($userId, $slug) {
    $conn = dbConn(); // your DB connection
    
    $sql = "
        SELECT 1 
        FROM users u
        JOIN user_roles r ON u.user_role_id = r.id
        JOIN role_permissions rp ON rp.role_id = r.id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE u.id = ? AND p.slug = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("is", $userId, $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hasPermission = ($result && $result->num_rows > 0);
    
    $stmt->close();
    $conn->close();
    
    return $hasPermission;
}


function createPermission($name, $slug) {
    $conn = dbConn();
    $sql = "INSERT INTO permissions (name, slug) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $name, $slug);
    return $stmt->execute();
}

function getAllPermissions() {
    $conn = dbConn();
    $result = $conn->query("SELECT * FROM permissions ORDER BY id");
    return $result->fetch_all(MYSQLI_ASSOC);
}


function updatePermission($id, $name, $slug) {
    $conn = dbConn();
    $sql = "UPDATE permissions SET name = ?, slug = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $name, $slug, $id);
    return $stmt->execute();
}

function deletePermission($id) {
    $conn = dbConn();
    $sql = "DELETE FROM permissions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}
function assignPermissionToRole($roleId, $permissionId) {
    $conn = dbConn();
    $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $roleId, $permissionId);
    return $stmt->execute();
}

function removePermissionFromRole($roleId, $permissionId) {
    $conn = dbConn();
    $sql = "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $roleId, $permissionId);
    return $stmt->execute();
}

function renderPermissions($permissions, $filter_ids, $assigned_permission_ids) {
    foreach ($permissions as $perm) {
        if (in_array($perm['id'], $filter_ids)) {
            $checked = in_array($perm['id'], $assigned_permission_ids) ? 'checked' : '';

            echo '<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">';
            
            // Left side: checkbox and label
            echo '<div class="form-check">';
            echo '<input type="checkbox" class="form-check-input me-2" id="perm_'.$perm['id'].'" name="permissions[]" value="'.$perm['id'].'" '.$checked.'>';
            echo '<label class="form-check-label fw-semibold" for="perm_'.$perm['id'].'">'.htmlspecialchars($perm['name']).'</label>';
            echo '</div>';

            // Right side: action buttons
            echo '<div class="btn-group" role="group" aria-label="Permission actions">';
            if (hasPermission($_SESSION['user_id'], 'edit_permission')) {
                echo '<a href="edit.php?id='.$perm['id'].'" class="btn btn-sm btn-outline-primary" title="Edit">';
                echo '<i class="fas fa-edit"></i>';
                echo '</a>';
            }
            if (hasPermission($_SESSION['user_id'], 'delete_permission')) {
                echo '<button type="button" class="btn btn-sm btn-outline-danger delete-permission" data-id="'.$perm['id'].'" title="Delete">';
                echo '<i class="fas fa-trash"></i>';
                echo '</button>';
            }
            echo '</div>';

            echo '</div>';
        }
    }
}
