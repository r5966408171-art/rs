<?php

session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include("config.php");

if(isset($_POST['delete_selected'])){

    if(isset($_POST['ids'])){

        foreach($_POST['ids'] as $id){

            $id = (int)$id;

            $conn->query("
            DELETE FROM trend_predictions
            WHERE id='$id'
            ");

        }

    }

    header("Location: prediction_history.php");
    exit;
}

// Check agar user ne "Show All" par click kiya hai
$showAll = isset($_GET['show']) && $_GET['show'] === 'all';

$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if($page < 1){
    $page = 1;
}

$start = ($page - 1) * $limit;

// Agar show=all hai toh bina LIMIT ke saara data fetch hoga
if($showAll) {
    $list = $conn->query("
    SELECT *
    FROM trend_predictions
    ORDER BY id DESC
    ");
} else {
    $list = $conn->query("
    SELECT *
    FROM trend_predictions
    ORDER BY id DESC
    LIMIT $start,$limit
    ");
}

$total = $conn->query("
SELECT COUNT(*) total
FROM trend_predictions
")->fetch_assoc()['total'];

$totalPages = ceil($total/$limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>Prediction History</title>

<style>
:root {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --border-color: #334155;
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
    --color-win: #4ade80;
    --color-loss: #f87171;
    --color-delete: #ef4444;
    --color-delete-hover: #dc2626;
}

*, *::before, *::after {
    box-sizing: border-box;
}

body {
    background-color: var(--bg-main);
    color: var(--text-main);
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 24px;
    margin: 0;
    line-height: 1.5;
    -webkit-tap-highlight-color: transparent;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header Action Layout */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.title-area {
    display: flex;
    align-items: center;
    gap: 12px;
}

h2 {
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
    color: #fff;
}

/* Live Pulse Indicator */
.live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    padding: 4px 10px;
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

.total-badge {
    background: #1e293b;
    border: 1px solid var(--border-color);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    color: var(--text-muted);
}

.total-badge span {
    color: #fff;
    font-weight: bold;
}

/* Button UI Components */
.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn {
    padding: 12px 18px;
    color: var(--text-main);
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    cursor: pointer;
}

.btn:hover { background: var(--border-color); }
.btn-admin { background: #16a34a; border-color: #22c55e; }
.btn-admin:hover { background: #15803d; }
.btn-manual { background: #2563eb; border-color: #3b82f6; }
.btn-manual:hover { background: #1d4ed8; }

.btn-print { background: #0284c7; border-color: #38bdf8; color: white; }
.btn-print:hover { background: #0369a1; }

.btn-delete {
    background: var(--color-delete);
    border-color: #f87171;
    color: white;
}
.btn-delete:hover { background: var(--color-delete-hover); }

/* Fluid Responsive Table Styling */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: var(--bg-card);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-color);
    margin-bottom: 25px;
    transition: opacity 0.2s ease;
}

.table-responsive.is-syncing {
    opacity: 0.7;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

th, td {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.95rem;
    white-space: nowrap;
}

th {
    background-color: #1e293b;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
}

.text-center { text-align: center; }
tr:last-child td { border-bottom: none; }
tr:hover td { background-color: #24324d; }

input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #3b82f6;
    cursor: pointer;
}

/* Status Badges */
.status-pill {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    display: inline-block;
}

.status-win { background: rgba(74, 222, 128, 0.15); color: var(--color-win); }
.status-loss { background: rgba(248, 113, 113, 0.15); color: var(--color-loss); }
.status-pending { background: rgba(148, 163, 184, 0.15); color: var(--text-muted); }

.badge-info {
    background: #334155;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}

/* Pagination Container */
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 20px;
}

.page-link {
    padding: 10px 16px;
    background: #334155;
    color: var(--text-main);
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.page-link.active {
    background: #2563eb;
    font-weight: 600;
}

.page-link.btn-all-records {
    background: #a855f7; /* Purple badge for All Records link */
}
.page-link.btn-all-records.active {
    background: #7c3aed;
}

@media (max-width: 640px) {
    body { padding: 16px; }
    h2 { font-size: 1.4rem; }
    th, td { padding: 12px 14px; font-size: 0.85rem; }
    .dashboard-header { flex-direction: column; align-items: stretch; gap: 14px; }
    .header-actions { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .btn { width: 100%; justify-content: center; }
    .title-area { justify-content: space-between; }
}

/* 🖨️ SMART PRINT CONFIGURATION */
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0;
        margin: 0;
    }
    .dashboard-header, 
    .pagination, 
    .btn, 
    .header-actions, 
    #sync-status,
    .total-badge,
    th:first-child, 
    td:first-child {
        display: none !important;
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
    }
    .table-responsive {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        overflow: visible !important;
    }
    table {
        width: 100% !important;
        border: 1px solid #111111 !important;
    }
    th, td {
        border-bottom: 1px solid #111111 !important;
        color: #000000 !important;
        padding: 8px 10px !important;
        font-size: 11pt !important;
        background: transparent !important;
        white-space: normal !important;
    }
    th {
        background-color: #f1f5f9 !important;
        color: #000000 !important;
    }
    code, .badge-info, .status-pill {
        background: transparent !important;
        color: #000000 !important;
        padding: 0 !important;
        font-weight: bold !important;
        border: none !important;
    }
}
</style>

</head>

<body>

<div class="container">

    <form method="POST" id="main-history-form">
        
        <div class="dashboard-header">
            <div>
                <div class="title-area">
                    <h2>📜 Prediction History</h2>
                    <div class="live-indicator" id="sync-status">
                        <div class="pulse-dot"></div> Live Auto Sync
                    </div>
                </div>
                <div class="total-badge" style="margin-top: 8px;">Total Logged Records: <span id="total-count-display"><?php echo $total; ?></span></div>
            </div>
            
            <div class="header-actions">
                <a href="admin.php" class="btn btn-admin">🏠 Admin</a>
                <a href="manual_patterns.php" class="btn btn-manual">🗂️ Patterns</a>
                
                <button type="button" class="btn btn-print" onclick="window.print()">
                    🖨️ Print History
                </button>

                <button
                    type="submit"
                    name="delete_selected"
                    class="btn btn-delete"
                    onclick="return confirm('Delete Selected Records?')">
                    🗑️ Delete Selected
                </button>
            </div>
        </div>

        <div class="table-responsive" id="live-table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>Period</th>
                        <th>Pattern</th>
                        <th>Type</th>
                        <th>Predict</th>
                        <th>Actual</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($r = $list->fetch_assoc()){ 
                        $statusVal = strtoupper($r['status']);
                        $statusClass = 'status-pending';
                        if ($statusVal === 'WIN') $statusClass = 'status-win';
                        if ($statusVal === 'LOSS') $statusClass = 'status-loss';
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>">
                        </td>
                        <td><strong><?php echo htmlspecialchars($r['period']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($r['pattern']); ?></code></td>
                        <td><span class="badge-info"><?php echo htmlspecialchars($r['trend_type']); ?></span></td>
                        <td><?php echo htmlspecialchars($r['bias']); ?></td>
                        <td><?php echo htmlspecialchars($r['actual'] ?: '-'); ?></td>
                        <td class="text-center">
                            <span class="status-pill <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($r['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </form>

    <div class="pagination" id="live-pagination">
        <?php
        // Normal numerical pagination links
        for($i = 1; $i <= $totalPages; $i++){
            $activeClass = ($i == $page && !$showAll) ? 'active' : '';
            echo "<a href='?page=$i' class='page-link $activeClass'>$i</a>";
        }
        
        // "Show All" toggle button at the end of pagination links
        $allActiveClass = $showAll ? 'active' : '';
        echo "<a href='?show=all' class='page-link btn-all-records $allActiveClass'>🚀 Show All Records</a>";
        ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableContainer = document.getElementById('live-table-container');
    const totalCountDisplay = document.getElementById('total-count-display');
    const paginationContainer = document.getElementById('live-pagination');
    const syncStatusBadge = document.getElementById('sync-status');

    // Unified Event Delegation for selection checkboxes
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'selectAll') {
            let checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(box => box.checked = e.target.checked);
        }
    });

    // Intelligent Auto-Refresh Core Loop
    setInterval(() => {
        // Pause auto refresh if user is checking fields
        const checkedItems = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkedItems.length > 0) {
            syncStatusBadge.innerHTML = '<span style="background-color:#eab308; width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px;"></span> Paused (Selecting)';
            syncStatusBadge.style.color = '#facc15';
            return;
        }

        syncStatusBadge.innerHTML = '<div class="pulse-dot"></div> Live Auto Sync';
        syncStatusBadge.style.color = '#60a5fa';
        tableContainer.classList.add('is-syncing');

        fetch(window.location.href)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const freshDoc = parser.parseFromString(html, 'text/html');
                
                tableContainer.innerHTML = freshDoc.getElementById('live-table-container').innerHTML;
                totalCountDisplay.innerText = freshDoc.getElementById('total-count-display').innerText;
                paginationContainer.innerHTML = freshDoc.getElementById('live-pagination').innerHTML;
            })
            .catch(err => console.error("Sync error:", err))
            .finally(() => {
                tableContainer.classList.remove('is-syncing');
            });
    }, 10000); // 10 seconds background loop refresh
});
</script>

</body>
</html>
