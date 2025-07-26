<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <video id="scan_job" height="200" width="285"></video>

    <button type="button" onclick="scanjob()">QR Scan</button>
    <button type="button" onclick="stopscan()">Stop QR Scan</button>

    <script src="../../qr_scanner/instascan.min.js"></script>

    <script>
        function scanjob() {
            let scanner = new Instascan.Scanner({
                video: document.getElementById('scan_job')
            });

            scanner.addListener('scan', function(content) {
                window.location.href = "find_attendance.php?app_id=" + content;
            });

            Instascan.Camera.getCameras().then(function(camares) {
                if (camares.length > 0) {

                    scanner.start(camares[0]);

                } else {
                    alert('No cameras found');
                }
            }).catch(function(e) {
                alert(e);
            })


        }

        function stopscan() {
            const video = document.querySelector('video');
            const mediaStream = video.srcObject;
            const tracks = mediaStream.getTracks();
            tracks[0].stop();
            tracks.foreach(track => track.stop());
        }
    </script>





</body>

</html>