<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("location:login.php");
    exit();
}

include("config.php");

$totalPred = $conn->query("
SELECT COUNT(*) total
FROM trend_predictions
")->fetch_assoc()['total'];

$totalWin = $conn->query("
SELECT COUNT(*) total
FROM trend_predictions
WHERE status='WIN'
")->fetch_assoc()['total'];

$totalLoss = $conn->query("
SELECT COUNT(*) total
FROM trend_predictions
WHERE status='LOSS'
")->fetch_assoc()['total'];

$acc = 0;

if(($totalWin+$totalLoss)>0){

$acc = round(
($totalWin*100)/
($totalWin+$totalLoss),
2
);

}

$data = $conn->query("SELECT * FROM trend_settings ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Trend Admin Panel - Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        :root {
            --bg-main: #090d16;
            --bg-card: #131c2e;
            --bg-card-hover: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #64748b;
            --accent-green: #10b981;
            --accent-red: #f43f5e;
            --accent-orange: #f59e0b;
            --accent-blue: #3b82f6;
            --accent-purple: #a855f7;
            --border: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--bg-main) !important;
            color: var(--text-main) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .app-container {
            max-width: 650px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .header-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #131c2e, #090d16);
            border-bottom: 2px solid var(--accent-blue);
        }

        .badge-btn {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }

        .badge-red {
            background: rgba(244, 63, 94, 0.12);
            color: var(--accent-red);
            border: 1px solid rgba(244, 63, 94, 0.25);
        }

        .badge-red:hover {
            background: var(--accent-red);
            color: white;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.4);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: rgba(9, 13, 22, 0.5);
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        .table-responsive.is-loading {
            opacity: .5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 0.88rem;
            min-width: 600px;
        }

        th {
            background: rgba(30, 41, 59, 0.8);
            color: #94a3b8;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
            font-weight: 600;
            color: #e2e8f0;
        }

        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Pill Badge Styling */
        .pill-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .pill-play { background: rgba(16, 185, 129, 0.12); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.25); }
        .pill-wait { background: rgba(245, 158, 11, 0.12); color: var(--accent-orange); border: 1px solid rgba(245, 158, 11, 0.25); }
        .pill-skip { background: rgba(244, 63, 94, 0.12); color: var(--accent-red); border: 1px solid rgba(244, 63, 94, 0.25); }

        /* Action Buttons Grid Layout */
        .ui-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .grid-full-width {
            grid-column: span 2;
        }

        .panel-btn {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px;
            border-radius: 16px;
            color: white;
            font-weight: 700;
            font-size: 0.88rem;
            min-height: 54px;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .panel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,.4);
            filter: brightness(1.1);
        }

        .panel-btn:active {
            transform: scale(0.98);
        }

        .btn-analyze { background: linear-gradient(135deg, #059669, #047857); }
        .btn-optimize { background: linear-gradient(135deg, #1d4ed8, #1e40af); }
        .btn-update { background: linear-gradient(135deg, #0284c7, #0369a1); }
        .btn-ai { background: linear-gradient(135deg, #a855f7, #7e22ce); }
        .btn-premium { background: linear-gradient(135deg, #eab308, #ca8a04); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #b91c1c); }

        .footer-stamp-updated {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 0.75rem;
            padding: 14px 4px 0 4px;
            border-top: 1px solid var(--border);
            margin-top: 25px;
            font-weight: 500;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.03);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px #4ade80;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            justify-content: center;
        }

        .stat-title {
            font-size: 0.68rem;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1;
        }

        .blue { color: #38bdf8; }
        .green { color: #10b981; }
        .red { color: #f43f5e; }
        .orange { color: #f59e0b; }

        @media(max-width:640px){
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            th, td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>

<div class="app-container"> 

    <div class="premium-card header-card">
        <div style="display:flex;align-items:center;gap:12px;">
            <img src="banner.jpg" onerror="this.src='https://via.placeholder.com/300x100?text=AI+Dashboard';" style="width:50px;height:50px;border-radius:12px;object-fit:cover;border:1px solid var(--border);">
            <div>
                <div style="font-size:1.15rem;font-weight:800;letter-spacing:-0.5px;color:#fff;">Control Panel</div>
                <div style="font-size:11px;color:#64748b;font-weight:600;">AI Prediction Dashboard</div>
            </div>
        </div>
        <a href="logout.php" class="badge-btn badge-red">Logout</a>
    </div>

    <div class="stats-grid">
        <div class="premium-card stat-card">
            <div class="stat-title">Total Predicts</div>
            <div class="stat-value blue"><?php echo $totalPred; ?></div>
        </div>
        <div class="premium-card stat-card">
            <div class="stat-title">Accuracy Node</div>
            <div class="stat-value orange"><?php echo $acc; ?>%</div>
        </div>
        <div class="premium-card stat-card">
            <div class="stat-title">Total Wins</div>
            <div class="stat-value green"><?php echo $totalWin; ?></div>
        </div>
        <div class="premium-card stat-card">
            <div class="stat-title">Total Losses</div>
            <div class="stat-value red"><?php echo $totalLoss; ?></div>
        </div>
    </div>

    <div class="premium-card" style="padding: 16px 12px;">
        <div class="section-title">
            <span>⚙️ Trend Configurations</span>
            <div class="live-indicator">
                <div class="pulse-dot"></div> Live Auto Sync
            </div>
        </div>
        
        <div class="table-responsive" id="trendTableContainer">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 14px; width: 25%;">Pattern</th>
                        <th style="width: 10%;">Win</th>
                        <th style="width: 10%;">Loss</th>
                        <th style="width: 12%;">Acc%</th>
                        <th style="width: 18%;">Conf</th>
                        <th style="width: 15%;">Decision</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $data->fetch_assoc()){ ?>
                    <tr>
                        <td style="text-align: left; padding-left: 14px; font-family: monospace; font-weight: 700; color: #ffffff;">
                            <?php echo htmlspecialchars($r['pattern_name']); ?>
                        </td>
                        <td style="color: var(--accent-green); font-weight: 700;"><?php echo $r['win']; ?></td>
                        <td style="color: var(--accent-red); font-weight: 700;"><?php echo $r['loss']; ?></td>
                        <td style="color: #cbd5e1; font-weight: 800;"><?php echo $r['accuracy']; ?>%</td>
                        <td style="color: #94a3b8; font-weight: 600; font-size: 0.8rem;"><?php echo $r['confidence']; ?></td>
                        <td>
                            <?php 
                            if($r['decision'] == "PLAY") {
                                echo "<span class='pill-badge pill-play'>PLAY</span>";
                            } elseif($r['decision'] == "WAIT") {
                                echo "<span class='pill-badge pill-wait'>WAIT</span>";
                            } else {
                                echo "<span class='pill-badge pill-skip'>SKIP</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $r['id']; ?>" style="color: var(--accent-blue); text-decoration: none; font-weight: 700; font-size: 0.82rem;">Edit</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-title" style="padding-left: 4px; margin-top: 20px;">🛠️ Engine Diagnostics</div>
    <div class="ui-grid-2">
        <a href="analyze.php" class="panel-btn btn-analyze grid-full-width">🔍 Analyze Database Matrix</a>
        <a href="optimize.php" class="panel-btn btn-optimize">⚙️ Auto Optimize</a>
        <a href="cron_update.php" class="panel-btn btn-update">🤖 Run AI Update</a>
    </div>

    <div class="section-title" style="padding-left:4px;margin-top:20px;">🚀 Quick Access Pipeline</div>
    <div class="ui-grid-2">
        <a href="manual_patterns.php" class="panel-btn btn-ai">🧠 Manual Pattern</a>
        <a href="pattern_report.php" class="panel-btn btn-premium">📊 Pattern Report</a>
        <a href="prediction_history.php" class="panel-btn btn-update">📜 History</a>
        <a href="backtest_manual.php" class="panel-btn btn-analyze">🎯 Backtest</a>
        <a href="discovery_ai.php" class="panel-btn btn-ai">🔮 Discovery AI</a>
        <a href="premium_patterns.php" class="panel-btn btn-premium">⭐ Premium</a>
        <a href="dangerous_patterns.php" class="panel-btn btn-danger">🚫 Dangerous</a>
        <a href="logout.php" class="panel-btn btn-danger">🚪 Logout</a>
    </div>

    <div class="footer-stamp-updated">
        <span>🤖 AI Engine Core Active</span>
        <span style="color:#10b981;font-weight:bold;display:flex;align-items:center;gap:4px;">
            <span style="width:6px;height:6px;background:#10b981;border-radius:50%;"></span> ONLINE
        </span>
    </div>

</div>

<script>
function hasActiveInputs(){
    if(document.activeElement && (
        document.activeElement.tagName==="INPUT" ||
        document.activeElement.tagName==="TEXTAREA" ||
        document.activeElement.tagName==="SELECT"
    )){
        return true;
    }
    if(document.querySelector('input[type="checkbox"]:checked')){
        return true;
    }
    return false;
}

async function refreshTable(){
    if(hasActiveInputs()){
        return;
    }
    const container = document.getElementById("trendTableContainer");
    if(!container){ return; }
    
    container.classList.add("is-loading");
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newContainer = doc.getElementById("trendTableContainer");
        if(newContainer){
            container.innerHTML = newContainer.innerHTML;
        }
    } catch(e) {
        console.log(e);
    }
    container.classList.remove("is-loading");
}

// 10 Second Table Polling Loop
setInterval(refreshTable, 10000);
</script>
</body>
</html>
