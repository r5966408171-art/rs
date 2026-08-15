<?php

session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include("config.php");

// Determine active sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'win';
$order = "win DESC";

if($sort == "accuracy"){
    $order = "accuracy DESC";
} elseif($sort == "hit") {
    $order = "total DESC";
} elseif($sort == "win") {
    $order = "win DESC";
} elseif($sort == "loss") {
    $order = "accuracy ASC, loss DESC";
}

// Upgraded Query with LEFT JOIN to fetch pattern_name
$q = $conn->query("
SELECT
    tp.pattern,
    COALESCE(mp.pattern_name, sp.pattern_name, 'Unknown') AS pattern_name,
    tp.trend_type,

    COUNT(*) AS total,

    SUM(CASE WHEN tp.status='WIN' THEN 1 ELSE 0 END) AS win,

    SUM(CASE WHEN tp.status='LOSS' THEN 1 ELSE 0 END) AS loss,

    ROUND(
        (SUM(CASE WHEN tp.status='LOSS' THEN 1 ELSE 0 END) * 100) / COUNT(*),
        2
    ) AS loss_rate,

    ROUND(
        (SUM(CASE WHEN tp.status='WIN' THEN 1 ELSE 0 END) * 100) / COUNT(*),
        2
    ) AS accuracy

FROM trend_predictions tp

LEFT JOIN manual_patterns mp
    ON tp.pattern = mp.pattern_code

LEFT JOIN system_patterns sp
    ON tp.pattern = sp.pattern_code

WHERE tp.status IN ('WIN','LOSS')

GROUP BY
    tp.pattern,
    mp.pattern_name,
    sp.pattern_name,
    tp.trend_type

HAVING COUNT(*) >= 5

ORDER BY $order
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>Pattern Report</title>

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

/* Top Navigation Link */
.nav-top {
    margin-bottom: 20px;
}

.btn-back {
    background: #1e293b;
    border: 1px solid #334155;
    color: var(--text-muted);
}

.btn-back:hover {
    background: #334155;
    color: #fff;
}

/* Header Action Layout */
.title-area {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

h2, h3 {
    font-weight: 700;
    margin: 0;
}

h2 { font-size: 1.75rem; color: #fff; }
h3 { font-size: 1.35rem; color: #cbd5e1; margin-top: 40px; margin-bottom: 20px; }

/* Live Pulse Indicator */
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

/* Filter Buttons */
.filter-group {
    display: flex;
    gap: 12px;
    margin-bottom: 25px;
}

.btn {
    padding: 12px 20px;
    color: var(--text-main);
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

/* Active Button States */
.btn-filter { background: var(--bg-card); border: 1px solid #475569; }
.btn-accuracy.active { background: #16a34a; border-color: #22c55e; }
.btn-win.active { background: #2563eb; border-color: #3b82f6; }
.btn-hit.active { background: #ea580c; border-color: #f97316; }
.btn-loss.active { background: #dc2626; border-color: #ef4444; }

/* Responsive Tables & Smooth Transitions */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: var(--bg-card);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #334155;
    margin-bottom: 10px;
    transition: opacity 0.2s ease-in-out;
}

.table-responsive.is-loading {
    opacity: 0.5;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

th, td {
    padding: 14px 18px;
    border-bottom: 1px solid #334155;
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

tr:last-child td { border-bottom: none; }
tr:hover td { background-color: #24324d; }

.text-center { text-align: center; }
.win { color: var(--color-win); font-weight: 600; }
.loss { color: var(--color-loss); font-weight: 600; }
.acc { color: var(--color-acc); font-weight: 700; }

.pattern-txt {
    font-family: monospace;
    background: rgba(15, 23, 42, 0.6);
    padding: 4px 8px;
    border-radius: 6px;
    color: #e2e8f0;
    letter-spacing: 1.5px;
    font-weight: 700;
}

.badge {
    background: #334155;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.85rem;
}

@media (max-width: 640px) {
    body { padding: 16px; }
    h2 { font-size: 1.4rem; }
    h3 { font-size: 1.2rem; margin-top: 30px; }
    .filter-group {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .btn {
        padding: 12px 6px;
        font-size: 0.85rem;
        gap: 4px;
        border-radius: 8px;
    }
    .btn-back {
        width: 100%;
        padding: 10px;
    }
    th, td {
        padding: 12px 14px;
        font-size: 0.85rem;
    }
    .title-area { flex-direction: row; justify-content: space-between; align-items: center; }
}
</style>

</head>
<body>

<div class="container">

    <div class="nav-top">
        <a href="admin.php" class="btn btn-back">⬅️ Back to Admin</a>
    </div>

    <div class="filter-group">
        <a href="?sort=accuracy" class="btn btn-filter btn-accuracy <?php echo ($sort == 'accuracy') ? 'active' : ''; ?>">
            🏆 Accuracy
        </a>
        <a href="?sort=win" class="btn btn-filter btn-win <?php echo ($sort == 'win') ? 'active' : ''; ?>">
            ✅ Win
        </a>
        <a href="?sort=hit" class="btn btn-filter btn-hit <?php echo ($sort == 'hit') ? 'active' : ''; ?>">
            📊 Hit
        </a>
        <a href="?sort=loss" class="btn btn-filter btn-loss <?php echo ($sort == 'loss') ? 'active' : ''; ?>">
            ❌ Loss
        </a>
    </div>

    <div class="title-area">
        <h2>📊 Pattern Performance Report</h2>
        <div class="live-indicator" id="sync-status">
            <div class="pulse-dot"></div> Live Auto Sync
        </div>
    </div>

    <div id="main-table-container" class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Pattern Name</th>
                    <th>Sequence</th>
                    <th>Type</th>
                    <th class="text-center">Hit</th>
                    <th class="text-center">Win</th>
                    <th class="text-center">Loss</th>
                    <th class="text-center">Accuracy</th>
                    <th class="text-center">Loss %</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                while($r = $q->fetch_assoc()){
                    $acc = 0;
                    if($r['total'] > 0){
                        $acc = round(($r['win'] * 100) / $r['total'], 2);
                    }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['pattern_name']); ?></strong></td>
                    <td><span class="pattern-txt"><?php echo htmlspecialchars($r['pattern']); ?></span></td>
                    <td><span class="badge"><?php echo htmlspecialchars($r['trend_type']); ?></span></td>
                    <td class="text-center"><?php echo $r['total']; ?></td>
                    <td class="win text-center"><?php echo $r['win']; ?></td>
                    <td class="loss text-center"><?php echo $r['loss']; ?></td>
                    <td class="acc text-center"><?php echo $acc; ?>%</td>
                    <td class="loss text-center"><?php echo $r['loss_rate']; ?>%</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <h3>📈 Type Performance Summary</h3>

    <?php
    $type = $conn->query("
    SELECT
    trend_type,
    COUNT(*) total,
    SUM(CASE WHEN status='WIN' THEN 1 ELSE 0 END) win,
    SUM(CASE WHEN status='LOSS' THEN 1 ELSE 0 END) loss
    FROM trend_predictions
    WHERE status IN ('WIN','LOSS')
    GROUP BY trend_type
    ORDER BY win DESC
    ");
    ?>

    <div id="summary-table-container" class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="text-center">Hit</th>
                    <th class="text-center">Win</th>
                    <th class="text-center">Loss</th>
                    <th class="text-center">Accuracy</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while($r = $type->fetch_assoc()){
                    $acc = 0;
                    if($r['total'] > 0){
                        $acc = round(($r['win'] * 100) / $r['total'], 2);
                    }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['trend_type']); ?></strong></td>
                    <td class="text-center"><?php echo $r['total']; ?></td>
                    <td class="win text-center"><?php echo $r['win']; ?></td>
                    <td class="loss text-center"><?php echo $r['loss']; ?></td>
                    <td class="acc text-center"><?php echo $acc; ?>%</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('.filter-group .btn-filter');
    const mainTableContainer = document.getElementById('main-table-container');
    const summaryTableContainer = document.getElementById('summary-table-container');

    // 1. Manual Click Filtering Logic (Smooth Ajax Refresh)
    filterButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); 
            
            const targetUrl = this.getAttribute('href');
            
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            mainTableContainer.classList.add('is-loading');
            summaryTableContainer.classList.add('is-loading');

            fetch(targetUrl)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    mainTableContainer.innerHTML = doc.getElementById('main-table-container').innerHTML;
                    summaryTableContainer.innerHTML = doc.getElementById('summary-table-container').innerHTML;
                    
                    window.history.pushState({ path: targetUrl }, '', targetUrl);
                })
                .catch(err => console.error('Error handling refresh: ', err))
                .finally(() => {
                    mainTableContainer.classList.remove('is-loading');
                    summaryTableContainer.classList.remove('is-loading');
                });
        });
    });

    // 2. Continuous Background Auto Sync Engine (Every 10 Seconds)
    setInterval(() => {
        mainTableContainer.classList.add('is-loading');
        summaryTableContainer.classList.add('is-loading');

        fetch(window.location.href)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const freshDoc = parser.parseFromString(html, 'text/html');
                
                mainTableContainer.innerHTML = freshDoc.getElementById('main-table-container').innerHTML;
                summaryTableContainer.innerHTML = freshDoc.getElementById('summary-table-container').innerHTML;
            })
            .catch(err => console.error("Sync worker error:", err))
            .finally(() => {
                mainTableContainer.classList.remove('is-loading');
                summaryTableContainer.classList.remove('is-loading');
            });
    }, 10000); // Updated to match your standard 10s sync engine interval

    window.addEventListener('popstate', () => {
        window.location.reload();
    });
});
</script>

</body>
</html>
