<?php

// 1. SYSTEM TIMEZONE SETTING: Indian Standard Time (IST) set kar raha hai
date_default_timezone_set("Asia/Kolkata");

// 2. SESSION CONTROL: Admin authentication check karne ke liye session start karta hai (agar pehle se start nahi hai)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. CORE INCLUDES: Database connection aur system functions load ho rahe hain
include("config.php");
include("functions.php");

// 4. LOSS STREAK ENGINE: Pichle 4 predictions ko check karke consecutive (lagatar) LOSS count karta hai
$streakQuery = $conn->query("SELECT status FROM trend_predictions ORDER BY id DESC LIMIT 4");
$currentLossStreak = 0;
while($row = $streakQuery->fetch_assoc()) {
    if(strtoupper($row['status']) == 'LOSS') {
        $currentLossStreak++; // Agar loss mila toh counter badhao
    } else {
        break; // Jaise hi WIN ya koi aur status mile, loop rok do (streak toot gayi)
    }
}

// 5. SIGNAL ENGINE: Current sequence pattern aur loss streak ko bhejkar RISK/ENTRY signal nikalta hai
$list = getLastEight($conn);
$fullPattern = getPattern($list);
$signalData = detectSignal($fullPattern, $currentLossStreak);

// 6. ADMIN AUTH CHECK: Check karta hai ki admin logged in hai ya nahi (UI buttons dikhane ke liye)
$isAdminLoggedIn = isset($_SESSION['admin']);

// 7. AI NUMBER PREDICTION: Top 3 sabse strong numbers calculate karta hai
$topNumbers = getTopNumbers($conn);

// 8. DATA INITIALIZATION: Game history se pichle 8 results nikaal kar dynamic pattern string banata hai

$current = $conn->query("
SELECT period
FROM game_results
ORDER BY id DESC
LIMIT 1
")->fetch_assoc();

if(!$current){
    $current['period'] = 0;
} // Example Output: "BBSBSSBS"

// 9. SUBSTRING PATTERN SLICING: Database matching ke liye alag-alag length (5 to 8) ke short patterns nikalna
$pattern = substr($fullPattern,-6);
$last5   = substr($fullPattern,-5);
$last6   = substr($fullPattern,-6);
$last7   = substr($fullPattern,-7);
$last8   = substr($fullPattern,-8);

// 10. DEFAULT LOGIC ENGINE: Agar koi custom pattern match nahi hua, toh default calculation chalti hai
$trend      = detectTrend($fullPattern);
$bias       = getBias($trend,$fullPattern);
$confidence = getConfidence($conn,$trend);
$decision   = getDecision($conn,$trend);

$signalName   = $signalData['signal'] ?? 'N/A';
$signalStatus = $signalData['status'] ?? 'N/A';

// 11. MANUAL PATTERN OVERRIDE: Admin ke banaye gaye special patterns ko priority base par check karta hai (Length 8 to 5)
$manual = $conn->query("
SELECT *
FROM manual_patterns
WHERE status=1
AND (
(pattern_code='$last5' AND pattern_length=5)
OR
(pattern_code='$last6' AND pattern_length=6)
OR
(pattern_code='$last7' AND pattern_length=7)
OR
(pattern_code='$last8' AND pattern_length=8)
)
ORDER BY pattern_length DESC
LIMIT 1
");

$patternName = $trend;
$manualData = null ;

if($manual->num_rows > 0){
    // Agar Manual Pattern mil jata hai, toh saara default system ruk jata hai aur manual prediction active hoti hai
    $manualData = $manual->fetch_assoc();

$bias = $manualData['predict'];

$confidence = getConfidence(
$conn,
$manualData['pattern_name']
);

$decision = getDecision(
$conn,
$manualData['pattern_name']
);

$patternName = $manualData['pattern_name'];
}

// 12. SYSTEM PATTERN OVERRIDE: Agar Manual Pattern nahi mila, toh System Patterns check hote hain
$systemData = null;
$system = $conn->query("
SELECT *
FROM system_patterns
WHERE status=1
AND (
(pattern_code='$last5' AND pattern_length=5)
OR
(pattern_code='$last6' AND pattern_length=6)
OR
(pattern_code='$last7' AND pattern_length=7)
OR
(pattern_code='$last8' AND pattern_length=8)
)
ORDER BY pattern_length DESC
LIMIT 1
");

if($manual->num_rows == 0 && $system->num_rows > 0){
    // System pattern match hone par prediction parameters override hote hain
    $systemData = $system->fetch_assoc();
    $bias        = $systemData['predict'];
    $confidence = getConfidence(
$conn,
$systemData['pattern_name']
);

$decision = getDecision(
$conn,
$systemData['pattern_name']
);
    $patternName = $systemData['pattern_name'];
}

// 13. AI MATHEMATICAL PATTERNS: Auto AI database analysis check (Elite, Premium, Dangerous tags)
$aiData = null;
$ai = $conn->query("
SELECT *
FROM ai_patterns
WHERE pattern_code='$pattern'
AND status=1
LIMIT 1
");

if($ai->num_rows > 0 && !$manualData){
    $aiData = $ai->fetch_assoc();

    if($aiData['type']=="ELITE"){
        $bias       = $aiData['predict'];
        $confidence = "VERY HIGH";
        $decision   = "PLAY";
    }
    else if($aiData['type']=="PREMIUM"){
        $bias       = $aiData['predict'];
        $confidence = "HIGH";
        $decision   = "PLAY";
    }
    else if($aiData['type']=="DANGEROUS"){
        // Dangerous pattern par trade skip karne ka automatic command jata hai
        $confidence = "LOW";
        $decision   = "SKIP";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Trend Predictor Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: #131c2e;
            --bg-card-alt: #1e293b;
            --accent-green: #10b981;
            --accent-red: #f43f5e;
            --accent-blue: #3b82f6;
            --accent-orange: #f59e0b;
            --accent-purple: #a855f7;
            --text-main: #f8fafc;
            --text-muted: #64748b;
            --border: rgba(255, 255, 255, 0.05);
            --glow-green: rgba(16, 185, 129, 0.15);
            --glow-blue: rgba(59, 130, 246, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            padding: 16px 12px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .app-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding-bottom: 30px;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 4px;
        }

        .main-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .main-title span {
            width: 10px;
            height: 10px;
            background: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-green);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }

        .nav-links {
            display: flex;
            gap: 6px;
        }

        .nav-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: #cbd5e1;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-btn:hover {
            background: var(--bg-card-alt);
            color: #fff;
            border-color: #475569;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .flex-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 4px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .flex-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #94a3b8;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .value {
            font-weight: 700;
            font-size: 0.94rem;
            color: #f1f5f9;
        }

        .seq-block {
            font-family: 'Courier New', Courier, monospace;
            background: rgba(15, 23, 42, 0.6);
            padding: 6px 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            font-weight: 800;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-badge.badge-green  { background: rgba(16, 185, 129, 0.12); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.25); box-shadow: 0 0 15px rgba(16, 185, 129, 0.08); }
        .status-badge.badge-blue   { background: rgba(59, 130, 246, 0.12); color: var(--accent-blue); border: 1px solid rgba(59, 130, 246, 0.25); box-shadow: 0 0 15px rgba(59, 130, 246, 0.08); }
        .status-badge.badge-orange { background: rgba(245, 158, 11, 0.12); color: var(--accent-orange); border: 1px solid rgba(245, 158, 11, 0.25); }
        .status-badge.badge-red    { background: rgba(244, 63, 94, 0.12); color: var(--accent-red); border: 1px solid rgba(244, 63, 94, 0.25); box-shadow: 0 0 15px rgba(244, 63, 94, 0.08); }
        .status-badge.badge-purple { background: rgba(168, 85, 247, 0.12); color: var(--accent-purple); border: 1px solid rgba(168, 85, 247, 0.25); }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .mini-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mini-card .num-val {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.5);
            padding: 14px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-item:not(:last-child) {
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stat-lbl {
            font-size: 0.72rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .stat-val {
            font-size: 1.15rem;
            font-weight: 800;
        }

        .table-responsive-wrapper {
            width: 100%;
            border-radius: 16px;
            background: rgba(9, 13, 22, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.04);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(30, 41, 59, 0.8);
            color: #94a3b8;
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 14px 8px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 8px;
            font-size: 0.86rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: #e2e8f0;
            font-weight: 600;
            text-align: center;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .period-txt {
            font-weight: 700;
            color: #ffffff;
            font-family: monospace;
        }

        .num-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 22px;
            border-radius: 50%;
            font-weight: 800;
            font-size: 0.8rem;
            text-align: center;
        }
        .num-violet { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .num-green  { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .num-red    { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }

        .txt-green { color: var(--accent-green); }
        .txt-blue { color: var(--accent-blue); }

        .footer-stamp {
            text-align: center;
            font-size: 0.75rem;
            color: #475569;
            margin-top: 14px;
            padding: 0 4px;
            display: flex;
            justify-content: space-between;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div id="live-engine-autoload-container" style="width: 100%; max-width: 480px;">
    <div class="app-container">

        <div class="app-header">
            <div class="main-title">
                <span></span> Live Engine
            </div>
            
            <?php if($isAdminLoggedIn): ?>
            <div class="nav-links">
                <a href="admin.php" class="nav-btn">🏠 Admin</a>
                <a href="prediction_history.php" class="nav-btn">📜 History</a>
                <a href="pattern_report.php" class="nav-btn">📊 Reports</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="live-dashboard-panel">

            <div class="card">
                <div class="flex-row">
                    <span class="label">Pattern Sequence</span>
                    <span class="value txt-blue seq-block"><?php echo htmlspecialchars($fullPattern); ?></span>
                </div>
                
                <div class="flex-row">
                    <span class="label">Manual AI Core</span>
                    <span class="value">
                        <?php
                        if($manualData){
                            echo "<span class='status-badge badge-green'>🧠 ACTIVE</span>";
                        }elseif($systemData){
                            echo "<span class='status-badge badge-blue'>⚙️ SYSTEM</span>";
                        }else{
                            echo "<span style='color:#475569; font-weight:800;'>--</span>";
                        }
                        ?>
                    </span>
                </div>
                
                <div class="flex-row">
                    <span class="label">AI Matrix Discovery</span>
                    <span class="value">
                        <?php
                        if($aiData){
                            echo (strtoupper($aiData['type']) == "PREMIUM") ? "<span class='status-badge badge-purple'>⭐ PREMIUM</span>" : "<span class='status-badge badge-red'>🚫 DANGEROUS</span>";
                        } else {
                            echo "<span class='status-badge' style='background:rgba(255,255,255,0.05); color:#94a3b8;'>NORMAL</span>";
                        }
                        ?>
                    </span>
                </div>
                
                <div class="flex-row">
                    <span class="label">AI Discovery Accuracy</span>
                    <span class="value" style="color: #fbbf24; font-weight:800;"><?php echo ($aiData) ? $aiData['accuracy']."%" : "--"; ?></span>
                </div>
                
                <div class="flex-row">
                    <span class="label">Current Trend Node</span>
                    <span class="value" style="color: #f8fafc; font-weight: 700;">
                        <?php echo htmlspecialchars($patternName); ?>
                    </span>
                </div>
                
                <div class="flex-row">
                    <span class="label">AI Signal Stream</span>
                    <span class="value" style="color:#22c55e; font-weight: 700;">
                        <?php echo htmlspecialchars($signalName ?? 'N/A'); ?>
                    </span>
                </div>
                
                <div class="flex-row">
                    <span class="label">Signal Engine Status</span>
                    <span class="value" style="color:#f59e0b; font-weight: 700;">
                        <?php echo htmlspecialchars($signalStatus ?? 'N/A'); ?>
                    </span>
                </div>
                
                <div class="flex-row" style="background: rgba(255,255,255,0.01); margin: 6px -4px; padding: 14px 8px; border-radius: 14px; border: 1px dashed rgba(255,255,255,0.04);">
                    <span class="label" style="color: #fff;">
                        🔮 Next Predict Period: 
                        <span style="font-weight:800; color:#facc15; font-family:monospace; font-size:1.05rem; margin-left:2px;">
                            <?php echo substr(($current['period'] + 1), -5); ?>
                        </span>
                    </span>
                    <?php $biasClass = (strtoupper($bias) == 'BIG') ? 'badge-green' : 'badge-blue'; ?>
                    <span class="status-badge <?php echo $biasClass; ?>" style="font-size: 0.85rem; padding: 6px 16px;">
    <?php echo htmlspecialchars($bias); ?>
</span>

<span style="
margin-left:10px;
font-weight:800;
color:#facc15;
font-size:0.95rem;
font-family:monospace;
">
<?php
echo ($topNumbers[0] ?? '-')
. " • " .
($topNumbers[1] ?? '-')
. " • " .
($topNumbers[2] ?? '-');
?>
</span>
                </div>
                
                <div class="flex-row">
                    <span class="label">Prediction Confidence</span>
                    <?php
                    $confStr = strtoupper($confidence);
                    $confClass = 'badge-red';
                    if(strpos($confStr, 'VERY HIGH') !== false || strpos($confStr, 'MANUAL') !== false) {
                        $confClass = 'badge-green';
                    } elseif(strpos($confStr, 'HIGH') !== false) {
                        $confClass = 'badge-purple';
                    }
                    ?>
                    <span class="status-badge <?php echo $confClass; ?>"><?php echo htmlspecialchars($confidence); ?></span>
                </div>
                
                <div class="flex-row">
                    <span class="label">Decision Engine</span>
                    <?php
                    if($currentLossStreak >= 2){
                        echo "<span class='status-badge badge-red' style='box-shadow: 0 0 12px var(--accent-red);'>⚠️ STOP/WAIT</span>";
                    } elseif($decision == "PLAY"){
                        echo "<span class='status-badge badge-green'>🚀 PLAY</span>";
                    } elseif($decision == "WAIT"){
                        echo "<span class='status-badge badge-orange'>⏳ WAIT</span>";
                    } else {
                        echo "<span class='status-badge badge-red'>🛑 SKIP</span>";
                    }
                    ?>
                </div>
            </div>

            <?php if ($currentLossStreak >= 2): ?>
                <div style="background: #f43f5e; color: #fff; padding: 10px; border-radius: 12px; margin-bottom: 15px; font-weight: 800; text-align: center;">
                    <marquee behavior="scroll" direction="left" scrollamount="6">
                        ⚠️ WARNING: 2 LOSSES DETECTED! STOP TRADING IMMEDIATELY TO AVOID FURTHER RISK. IT MAY APPEAR SBSB ⚠️
                    </marquee>
                </div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="mini-card" style="border-bottom: 3px solid var(--accent-green); box-shadow: inset 0 -10px 20px rgba(16, 185, 129, 0.02);">
                    <span class="label" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px;">BIG Hits</span>
                    <span class="num-val txt-green"><?php echo totalBig($list); ?></span>
                </div>
                <div class="mini-card" style="border-bottom: 3px solid var(--accent-blue); box-shadow: inset 0 -10px 20px rgba(59, 130, 246, 0.02);">
                    <span class="label" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px;">SMALL Hits</span>
                    <span class="num-val txt-blue"><?php echo totalSmall($list); ?></span>
                </div>
            </div>

            <div class="card" style="padding: 14px;">
                <div class="stats-bar" style="margin-bottom: 12px;">
                    <div class="stat-item">
                        <div class="stat-lbl">Session Win</div>
                        <div class="stat-val txt-green"><?php echo getWin($conn); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-lbl">Session Loss</div>
                        <div class="stat-val txt-red"><?php echo getLoss($conn); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-lbl">Accuracy Node</div>
                        <div class="stat-val" style="color: #fbbf24;"><?php echo getAccuracy($conn); ?>%</div>
                    </div>
                </div>

               
            </div>

            <div class="card" style="padding: 16px 12px;">
                <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 14px; color: #fff; display: flex; align-items: center; justify-content: space-between;">
                    <span>📊 Realtime Activity Log (Last 20)</span>
                    <span style="font-size:0.7rem; background:rgba(255,255,255,0.05); padding:2px 8px; border-radius:6px; color:#64748b; font-weight:500;">Auto Update</span>
                </div>
                
                <div class="table-responsive-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: left; padding-left: 12px; width: 22%;">Period</th>
                                <th style="width: 14%;">Num</th>
                                <th style="width: 22%;">Predict</th>
                                <th style="width: 22%;">Actual</th>
                                <th style="width: 20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $history = getHistory($conn);
                            while($row = $history->fetch_assoc()):
                                // Assign colors dynamically according to the rules of color games (Violet/Red/Green)
                                $num = intval($row['number']);
                                $numClass = 'num-green';
                                if(in_array($num, [0,5])) { $numClass = 'num-violet'; }
                                elseif(in_array($num, [2,4,6,8])) { $numClass = 'num-red'; }
                                
                                $predBias = strtoupper($row['bias'] ?? 'PENDING');
                                $actBias = strtoupper($row['actual'] ?? 'WAIT');
                                
                                $pColor = ($predBias == 'BIG') ? 'txt-green' : 'txt-blue';
                                $aColor = ($actBias == 'BIG') ? 'txt-green' : 'txt-blue';
                                
                                // Render Badge components for Win/Loss state
                                if(strtoupper($row['status'] ?? '') == 'WIN'){
                                    $statusHtml = "<span class='status-badge badge-green' style='font-size:0.65rem; padding:2px 8px; border-radius:6px;'>WIN</span>";
                                } elseif(strtoupper($row['status'] ?? '') == 'LOSS') {
                                    $statusHtml = "<span class='status-badge badge-red' style='font-size:0.65rem; padding:2px 8px; border-radius:6px;'>LOSS</span>";
                                } else {
                                    $statusHtml = "<span style='color:#475569;'>•</span>";
                                }
                            ?>
                            <tr>
                                <td style="text-align: left; padding-left: 12px;" class="period-txt"><?php echo substr($row['period'], -5); ?></td>
                                <td><span class="num-badge <?php echo $numClass; ?>"><?php echo $num; ?></span></td>
                                <td class="<?php echo $pColor; ?>"><?php echo $predBias; ?></td>
                                <td class="<?php echo $aColor; ?>"><?php echo $actBias; ?></td>
                                <td><?php echo $statusHtml; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer-stamp">
                <span>⚡ Core Matrix Engine v5.0</span>
                <span>Refreshed: <span id="sync-engine-clock"><?php echo date("h:i:s A"); ?></span></span>
            </div>

        </div>
    </div>
</div>

<script>
setInterval(function() {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            let parser = new DOMParser();
            let freshDoc = parser.parseFromString(html, 'text/html');
            
            let oldContent = document.getElementById('live-engine-autoload-container');
            let freshContent = freshDoc.getElementById('live-engine-autoload-container');
            
            if(oldContent && freshContent) {
                oldContent.innerHTML = freshContent.innerHTML;
            }
            
            let now = new Date();
            document.getElementById('sync-engine-clock').innerText = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        })
        .catch(err => console.log("Engine sync active..."));
}, 3000);
</script>

</body>
</html>
