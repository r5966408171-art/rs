<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Auto Refresh Engine</title>
    <style>
        body {
            background-color: #0f172a;
            color: #38bdf8;
            font-family: monospace;
            padding: 20px;
            text-align: center;
        }
        .status-box {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 50px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dot {
            height: 10px;
            width: 10px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
</head>
<body>

    <div class="status-box">
        <h2><span class="dot"></span> Engine Running</h2>
        <p>Refreshing 3 files every 0.5 seconds...</p>
        <p id="timer" style="color: #94a3b8;">Last Sync: --</p>
    </div>

    <div id="file1" style="display:none;"></div>
    <div id="file2" style="display:none;"></div>
    <div id="file3" style="display:none;"></div>

    <script>
    function refreshFiles() {
        // 1. First File Refresh
        fetch('api_import.php')
            .then(res => res.text())
            .then(data => document.getElementById('file1').innerHTML = data)
            .catch(e => console.log('File 1 Error'));

        // 2. Second File Refresh
        fetch('')
            .then(res => res.text())
            .then(data => document.getElementById('file2').innerHTML = data)
            .catch(e => console.log('File 2 Error'));

        // 3. Third File Refresh
        fetch('save_results.php')
            .then(res => res.text())
            .then(data => document.getElementById('file3').innerHTML = data)
            .catch(e => console.log('File 3 Error'));

        // Time Update
        let now = new Date();
        document.getElementById('timer').innerText = "Last Sync: " + now.toLocaleTimeString();
    }

    // Har 0.5 second (500ms) mein execute hoga
    setInterval(refreshFiles, 500);
    
    // Pehli baar immediately run karne ke liye
    refreshFiles();
    </script>

</body>
</html>
