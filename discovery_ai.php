<?php
include("config.php");

// 1. Reset Pattern Database
$conn->query("TRUNCATE TABLE ai_patterns");

// 2. Read History
$list = [];
$sql = $conn->query("SELECT number FROM game_results ORDER BY id ASC");

while($row = $sql->fetch_assoc()){
    if($row['number'] <= 4)
        $list[] = "S";
    else
        $list[] = "B";
}

// 3. 6 Character Scanner Engine
$ai = [];
$countList = count($list);

for($i = 0; $i < $countList - 6; $i++){
    $pattern = "";
    for($j = 0; $j < 6; $j++){
        $pattern .= $list[$i+$j];
    }

    $next = $list[$i+6];

    if(!isset($ai[$pattern])){
        $ai[$pattern] = [
            "BIG" => 0,
            "SMALL" => 0
        ];
    }

    if($next == "B")
        $ai[$pattern]["BIG"]++;
    else
        $ai[$pattern]["SMALL"]++;
}

// 4. Accuracy Calculation & DB Dynamic Classification
foreach($ai as $pattern => $data){
    $big = $data["BIG"];
    $small = $data["SMALL"];
    $hit = $big + $small;

    if($big > $small){
        $predict = "BIG";
        $win = $big;
        $loss = $small;
    } else {
        $predict = "SMALL";
        $win = $small;
        $loss = $big;
    }

    $accuracy = round(($win * 100) / $hit, 2);

    if($hit < 15){
        continue;
    }

    if($accuracy >= 75){
        $type = "ELITE";
    } else if($accuracy >= 68){
        $type = "PREMIUM";
    } else if($accuracy <= 52){
        $type = "DANGEROUS";
    } else {
        continue;
    }

    // Save Matrix Node
    $conn->query("
        INSERT INTO ai_patterns (pattern_code, predict, hit, win, loss, accuracy, type)
        VALUES ('$pattern', '$predict', '$hit', '$win', '$loss', '$accuracy', '$type')
    ");
}

// 5. Fetch Matrix Diagnostics Results for the Premium UI
$total_q = $conn->query("SELECT COUNT(*) total FROM ai_patterns")->fetch_assoc();
$elite_q = $conn->query("SELECT COUNT(*) total FROM ai_patterns WHERE type='ELITE'")->fetch_assoc();
$premium_q = $conn->query("SELECT COUNT(*) total FROM ai_patterns WHERE type='PREMIUM'")->fetch_assoc();
$danger_q = $conn->query("SELECT COUNT(*) total FROM ai_patterns WHERE type='DANGEROUS'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discovery AI Matrix Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-elite: #a855f7;
            --accent-premium: #eab308;
            --accent-danger: #f43f5e;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
        }

        body {
            background-color: var(--bg-main) !important;
            color: var(--text-main) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            margin: 0;
            padding: 0;
        }

        .app-container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .header-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-bottom: 2px solid var(--accent-elite);
        }

        .badge-btn {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .badge-blue {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-blue:hover {
            background: var(--accent-blue);
            color: white;
        }

        .success-banner {
            background: rgba(16, 185, 129, 0.08);
            border: 1px dashed var(--accent-green);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-title {
            color: var(--accent-green);
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        /* Metrics Display Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .grid-span-all {
            grid-column: span 2;
        }

        .metric-tile {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .tile-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .tile-value {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        /* Diagnostic Colors */
        .color-total { color: #f8fafc; text-shadow: 0 0 10px rgba(255,255,255,0.1); }
        .color-elite { color: var(--accent-elite); text-shadow: 0 0 10px rgba(168,85,247,0.2); }
        .color-premium { color: var(--accent-premium); text-shadow: 0 0 10px rgba(234,179,8,0.2); }
        .color-danger { color: var(--accent-danger); text-shadow: 0 0 10px rgba(244,63,94,0.2); }

        .footer-stamp {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 0.75rem;
            padding: 10px 4px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 25px;
        }
    </style>
</head>
<body>

<div class="app-container">

    <div class="premium-card header-card">
        <div style="font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
            🔮 Discovery AI Engine
        </div>
        <a href="admin.php" class="badge-btn badge-blue">← Dashboard</a>
    </div>

    <div class="success-banner">
        <div class="success-title">✓ Deep Scan Execution Complete</div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">Database truncated & all pattern weights recalculated successfully.</div>
    </div>

    <div class="premium-card">
        <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; color: #e2e8f0; letter-spacing: -0.3px;">
            📊 Deep Matrix Diagnostic Results
        </div>

        <div class="metrics-grid">
            <div class="metric-tile grid-span-all" style="border-left: 4px solid var(--accent-blue); background: rgba(59, 130, 246, 0.03);">
                <div class="tile-label">Total Valid Patterns Synced</div>
                <div class="tile-value color-total"><?php echo $total_q['total']; ?></div>
            </div>

            <div class="metric-tile" style="border-left: 3px solid var(--accent-elite);">
                <div class="tile-label">⚡ Elite Tier</div>
                <div class="tile-value color-elite"><?php echo $elite_q['total']; ?></div>
            </div>

            <div class="metric-tile" style="border-left: 3px solid var(--accent-premium);">
                <div class="tile-label">⭐ Premium Tier</div>
                <div class="tile-value color-premium"><?php echo $premium_q['total']; ?></div>
            </div>

            <div class="metric-tile grid-span-all" style="border-left: 3px solid var(--accent-danger); margin-top: 4px;">
                <div class="tile-label">🚫 Dangerous Threat Patterns</div>
                <div class="tile-value color-danger"><?php echo $danger_q['total']; ?></div>
            </div>
        </div>
    </div>

    <div class="footer-stamp">
        <span>🤖 AI Weighting: 6-Node Deep Trace</span>
        <span>Operational Stable</span>
    </div>

</div>

</body>
</html>
