<?php
ob_start();
// Start session if not already active
include '../../init.php'; // Path to your init.php

// --- Security Check ---
// You can add a check here to ensure only Admins can access this page.
// For example: if ($_SESSION['user_role'] != 'Admin') { die('Access Denied'); }


// --- Step 1: Get Student ID from URL ---
// Example URL to access this page: .../attendance/generate_student_qr.php?id=15
$student_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_user_id <= 0) {
    die("Error: Please provide a valid student ID in the URL. (e.g., ?id=15)");
}

// --- Step 2: Fetch Student Details from Database ---
$db = dbConn();
$sql = "SELECT 
            u.FirstName, 
            u.LastName, 
            sd.registration_no 
        FROM users u
        JOIN student_details sd ON u.Id = sd.user_id
        WHERE u.Id = '$student_user_id'";

$result = $db->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Error: No student found with the ID: " . $student_user_id);
}

$student = $result->fetch_assoc();
$student_name = htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']);
$registration_no = htmlspecialchars($student['registration_no']);


// --- Step 3: Generate the QR Code ---
// Include the QR code library
include '../../qr_gen/qrlib.php';

// Define the path to save the QR code images
$qr_path = '../../qr_codes/';

// Create the directory if it doesn't exist
if (!file_exists($qr_path)) {
    mkdir($qr_path, 0777, true);
}

// The data to be encoded in the QR code is the student's unique user ID.
$qr_data = $student_user_id;

// QR code settings
$errorCorrectionLevel = 'L'; // Low
$matrixPointSize = 5;      // Size of the QR code dots

// Define the filename for the QR code image
$filename = $qr_path . 'student_' . $qr_data . '.png';

// Generate the QR code and save it as a PNG file
QRcode::png($qr_data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);


// --- Step 4: Display the Printable ID Card ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ID Card for <?= $student_name ?></title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #e9ecef;
        }
        .id-card {
            display: inline-block;
            width: 350px;
            border: 1px solid #dee2e6;
            padding: 25px;
            border-radius: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        .id-card .header {
            border-bottom: 2px solid #1cc1ba;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .id-card .header h2 {
            margin: 0;
            font-weight: 700;
            color: #343a40;
            font-size: 1.5rem;
        }
        .id-card p {
            margin: 8px 0;
            font-size: 1rem;
            color: #6c757d;
        }
        .id-card p.reg-no {
            font-weight: 600;
            color: #495057;
        }
        .id-card img.qr-code {
            margin-top: 15px;
            width: 180px;
            height: 180px;
            border: 5px solid #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .id-card .footer {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #adb5bd;
        }
        .print-button {
            margin-top: 25px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background-color: #1cc1ba;
            color: white;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(28, 193, 186, 0.2);
        }
        .print-button:hover {
            background-color: #17a29b;
            box-shadow: 0 6px 20px rgba(28, 193, 186, 0.3);
            transform: translateY(-2px);
        }
        /* Styles for printing */
        @media print {
            body {
                background-color: #fff;
                margin: 0;
            }
            .print-button {
                display: none;
            }
            .id-card {
                box-shadow: none;
                border: 2px solid #000;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="header">
            <h2>STUDENT ID CARD</h2>
        </div>
        <h3><?= $student_name ?></h3>
        <p class="reg-no">Reg. No: <?= $registration_no ?></p>
        <img class="qr-code" src="<?= $filename ?>" alt="QR Code for student ID <?= $qr_data ?>">
        <div class="footer">
            <p>Scan for Attendance</p>
        </div>
    </div>
    <br>
    <button class="print-button" onclick="window.print()">Print ID Card</button>
</body>
</html>
<?php
ob_end_flush();
?>
