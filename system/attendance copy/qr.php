<?php
$qr_path = '../../qr_codes/';

include '../../qr_gen/qrlib.php';

if (!file_exists($qr_path)) {
    mkdir($qr_path);
}

$errorCorrectionLevel = 'L'; //L-7%,M-15%,Q-25%,H-30%
$matrixPointSize = 4; //1-10

$data = 24578956;

//../../qr_codes/test24578956|L|4

$filename = $qr_path . 'test' . md5($data . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';

//$q=QRcode();
//$q->png();

QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

echo '<img src="' . $qr_path . basename($filename) . '">';
