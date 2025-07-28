<?php
ob_start();
include '../../init.php';
?>
<style>
    .scanner-container { max-width: 500px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    #qr-reader { width: 100%; border: 2px solid #eee; border-radius: 8px; overflow: hidden; }
</style>
<div class="container-fluid">
    <div class="scanner-container">
        <div class="text-center mb-4">
            <i class="fas fa-qrcode" style="font-size: 48px; color: #1cc1ba;"></i>
            <h2 class="mt-2">Scan Invoice QR Code</h2>
            <p class="text-muted">Scan the QR code on the student's invoice to quickly record a payment.</p>
        </div>
        <div id="qr-reader"></div>
        <div id="scan_result" class="alert mt-3" style="display: none;"></div>
    </div>
</div>
<script src="../assets/js/html5-qrcode.min.js" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultContainer = document.getElementById('scan_result');
    const html5QrCode = new Html5Qrcode("qr-reader");

    const onScanSuccess = (decodedText, decodedResult) => {
        // decodedText should be "InvoiceID:123"
        if (decodedText.startsWith("InvoiceID:")) {
            const invoiceId = decodedText.split(':')[1];
            if (!isNaN(invoiceId)) {
                // If a valid Invoice ID is found, redirect to the record payment page
                resultContainer.textContent = 'Invoice Found! Redirecting...';
                resultContainer.className = 'alert alert-success';
                resultContainer.style.display = 'block';
                window.location.href = `record_payment.php?invoice_id=${invoiceId}`;
            }
        }
    };
    html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, onScanSuccess)
        .catch(err => console.error("Unable to start scanning.", err));
});
</script>
<?php
$content = ob_get_clean();
include '../layouts.php';
?>