<?php

session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include("config.php");

if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $conn->query("
    DELETE FROM manual_patterns
    WHERE id='$id'
    ");
}

if(isset($_GET['off'])){
    $id=(int)$_GET['off'];
    $conn->query("
    UPDATE manual_patterns
    SET status=0
    WHERE id='$id'
    ");
    header("Location: manual_patterns.php");
    exit;
}

if(isset($_GET['on'])){
    $id=(int)$_GET['on'];
    $conn->query("
    UPDATE manual_patterns
    SET status=1
    WHERE id='$id'
    ");
    header("Location: manual_patterns.php");
    exit;
}

$message = "";
if(isset($_POST['save'])){
    $pattern_name = trim($_POST['pattern_name']);
    $pattern = strtoupper(trim($_POST['pattern']));
    $predict = $_POST['predict'];
    $length = strlen($pattern);

    if(!empty($pattern)){
        $check = $conn->query("
        SELECT id
        FROM manual_patterns
        WHERE pattern_code='$pattern'
        LIMIT 1
        ");

        if($check->num_rows > 0){
            $message = "⚠️ Pattern Already Exists!";
        }else{
            $conn->query("
            INSERT INTO manual_patterns
            (
            pattern_name,
            pattern_code,
            predict,
            pattern_length,
            priority,
            status
            )
            VALUES
            (
            '$pattern_name',
            '$pattern',
            '$predict',
            '$length',
            1,
            1
            )
            ");
            $message = "✅ Pattern Saved Successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Manual Pattern Manager</title>
    
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --bg-header: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --color-win: #4ade80;
            --color-loss: #f87171;
            --color-acc: #fbbf24;
            --primary-blue: #3b82f6;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 24px;
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top Header Navigation Matrix */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        /* Live Pulse Badge */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #60a5fa;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #3b82f6;
            border-radius: 50%;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        /* Form Card Grid System */
        .manager-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 850px) {
            .manager-grid {
                grid-template-columns: 380px 1fr;
            }
        }

        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-header);
            border-radius: 16px;
            padding: 20px;
            height: fit-content;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--bg-header);
            border-radius: 10px;
            padding: 12px;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .search-divider {
            border-top: 1px solid var(--bg-header);
            margin: 20px 0;
            padding-top: 20px;
        }

        /* Buttons Structure */
        .btn {
            padding: 12px 20px;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
            background: var(--bg-card);
            border: 1px solid #475569;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        
        .btn:hover { background: var(--bg-header); }
        .btn-admin { background: #16a34a; border-color: #22c55e; }
        .btn-backtest { background: #ea580c; border-color: #f97316; }
        
        .btn-submit {
            width: 100%;
            background: var(--primary-blue);
            border-color: #2563eb;
        }
        .btn-submit:hover { background: #2563eb; }

        .btn-search {
            width: 100%;
            background: transparent;
            border-color: var(--bg-header);
            color: var(--text-muted);
        }
        .btn-search:hover { background: var(--bg-header); color: #fff; }

        /* Alert Notification */
        .alert-box {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.2);
            color: var(--color-win);
            padding: 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 16px;
        }

        /* Table Architecture */
        .table-section {
            background: var(--bg-card);
            border: 1px solid var(--bg-header);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .table-title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            border: 1px solid var(--bg-header);
            transition: opacity 0.2s ease;
        }

        .table-responsive.is-loading { opacity: 0.5; }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--bg-header);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        th {
            background-color: rgba(15, 23, 42, 0.3);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #24324d; }

        .pattern-txt {
            font-family: monospace;
            background: rgba(15, 23, 42, 0.6);
            padding: 4px 8px;
            border-radius: 6px;
            color: #e2e8f0;
            letter-spacing: 1.5px;
            font-weight: 700;
        }

        /* Badges Engine */
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-block;
        }

        .badge-big { background: rgba(74, 222, 128, 0.15); color: var(--color-win); border: 1px solid rgba(74, 222, 128, 0.25); }
        .badge-small { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); }
        
        .status-active { color: var(--color-win); font-weight: 700; }
        .status-inactive { color: var(--text-muted); font-weight: 700; }

        /* Actions Layout */
        .action-container {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .act-btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        
        .act-on { background: rgba(74, 222, 128, 0.1); color: var(--color-win); border-color: rgba(74, 222, 128, 0.2); }
        .act-off { background: rgba(248, 113, 113, 0.1); color: var(--color-loss); border-color: rgba(248, 113, 113, 0.2); }
        .act-edit { background: rgba(251, 191, 36, 0.1); color: var(--color-acc); border-color: rgba(251, 191, 36, 0.2); }
        .act-del { background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.2); }
        
        .act-btn:hover { opacity: 0.85; }

        @media (max-width: 640px) {
            body { padding: 16px; }
            h2 { font-size: 1.3rem; }
            .header-flex { flex-direction: column; align-items: flex-start; gap: 14px; }
            .nav-buttons { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            th, td { padding: 10px 12px; font-size: 0.8rem; }
            .action-container { flex-direction: column; width: 100%; }
            .act-btn { text-align: center; width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-flex">
        <h2>Manual Pattern Manager</h2>
        <div class="nav-buttons">
            <a href="admin.php" class="btn btn-admin">🏠 Admin</a>
            <a href="backtest_manual.php" class="btn btn-backtest">📊 Backtest</a>
        </div>
    </div>

    <div class="manager-grid">
        
        <div class="form-card">
            <?php if(!empty($message)): ?>
                <div class="alert-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Pattern Name</label>
                    <input type="text" name="pattern_name" class="form-input" placeholder="e.g. Dragon Reversal" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">Pattern Sequence</label>
                    <input type="text" name="pattern" class="form-input" placeholder="e.g. BBSSB" required autocomplete="off" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label class="form-label">Predict Strategy</label>
                    <select name="predict" class="form-select">
                        <option value="BIG">BIG</option>
                        <option value="SMALL">SMALL</option>
                    </select>
                </div>
                <button type="submit" name="save" class="btn btn-submit">Save New Pattern</button>
            </form>

            <div class="search-divider">
                <form method="GET">
                    <div class="form-group">
                        <label class="form-label">Filter Configuration Matrix</label>
                        <input type="text" name="search" id="pattern-search-input" placeholder="Search Pattern Sequence..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-search">🔍 Run Filter</button>
                </form>
            </div>
        </div>

        <div class="table-section">
            <div class="table-title">
                <span>🗂️ Configuration Matrix</span>
                <div class="live-indicator">
                    <div class="pulse-dot"></div> Live Auto Sync
                </div>
            </div>
            
            <div id="table-sync-wrapper" class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Pattern</th>
                            <th>Predict</th>
                            <th>Len</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(isset($_GET['search']) && $_GET['search'] != ""){
                            $search = $conn->real_escape_string(trim($_GET['search']));
                            $list = $conn->query("SELECT * FROM manual_patterns WHERE pattern_code LIKE '%$search%' OR pattern_name LIKE '%$search%' ORDER BY id DESC");
                        } else {
                            $list = $conn->query("SELECT * FROM manual_patterns ORDER BY id DESC");
                        }
                        
                        if($list && $list->num_rows > 0):
                            while($r = $list->fetch_assoc()):
                                $predictBadge = (strtoupper($r['predict']) == 'BIG') ? 'badge-big' : 'badge-small';
                                $statusText = ($r['status'] == 1) ? '<span class="status-active">ON</span>' : '<span class="status-inactive">OFF</span>';
                        ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['pattern_name']); ?></strong></td>
                            <td><span class="pattern-txt"><?php echo htmlspecialchars($r['pattern_code']); ?></span></td>
                            <td><span class="badge <?php echo $predictBadge; ?>"><?php echo htmlspecialchars($r['predict']); ?></span></td>
                            <td><?php echo $r['pattern_length']; ?></td>
                            <td><?php echo $statusText; ?></td>
                            <td>
                                <div class="action-container" style="justify-content: flex-end;">
                                    <?php if($r['status'] == 1){ ?>
                                        <a href="?off=<?php echo $r['id']; ?>" class="act-btn act-off">OFF</a>
                                    <?php } else { ?>
                                        <a href="?on=<?php echo $r['id']; ?>" class="act-btn act-on">ON</a>
                                    <?php } ?>
                                    <a href="edit_pattern.php?id=<?php echo $r['id']; ?>" class="act-btn act-edit">EDIT</a>
                                    <a href="?delete=<?php echo $r['id']; ?>" onclick="return confirm('Delete Pattern?')" class="act-btn act-del">DELETE</a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">No matching custom patterns defined yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableContainer = document.getElementById('table-sync-wrapper');
    const inputFields = document.querySelectorAll('input, select');

    // Helper method to detect if admin is busy entering custom definitions
    const isUserActive = () => {
        let active = false;
        inputFields.forEach(field => {
            if (document.activeElement === field || (field.value !== "" && field.id === "pattern-search-input")) {
                active = true;
            }
        });
        return active;
    };

    // 10-Second Auto Sync Heartbeat
    setInterval(() => {
        if (isUserActive()) return; // Pause refresh cycle if inputs are active or focused

        tableContainer.classList.add('is-loading');

        fetch(window.location.href)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const freshDoc = parser.parseFromString(html, 'text/html');
                const cleanContent = freshDoc.getElementById('table-sync-wrapper').innerHTML;
                
                tableContainer.innerHTML = cleanContent;
            })
            .catch(err => console.error("Sync Engine connection dropped: ", err))
            .finally(() => {
                tableContainer.classList.remove('is-loading');
            });
    }, 10000);
});
</script>

</body>
</html>
