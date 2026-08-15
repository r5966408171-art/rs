<?php
include("config.php");
if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    $conn->query("
    DELETE FROM manual_patterns
    WHERE id='$id'
    ");

    header("Location: backtest_manual.php");
    exit;
}

// Get the active hit filter from URL (Default is '0' ie. show all)
$min_hit = isset($_GET['min_hit']) ? $_GET['min_hit'] : '0';

// Sabu history load
$data = [];

$q = $conn->query("
SELECT bigsmall
FROM game_results
ORDER BY id ASC
LIMIT 3000
");


while($r = $q->fetch_assoc()){
    if($r['bigsmall'] == "BIG"){
        $data[] = "B";
    } else {
        $data[] = "S";
    }
}

// Manual pattern load
$manual = $conn->query("SELECT * FROM manual_patterns WHERE status=1 ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pattern Backtest Engine</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #0b0f19;
            --bg-card: #161f30;
            --accent-green: #00e676;
            --accent-blue: #38bdf8;
            --accent-red: #ff3d00;
            --accent-orange: #ff9100;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            padding: 12px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding-bottom: 40px;
        }

        /* --- Header Configuration --- */
        .page-header {
            text-align: center;
            margin: 16px 0 20px 0;
            position: relative;
        }

        .top-nav-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .btn-back {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: #ffffff;
        }

        h2 {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #ffffff 60%, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        /* --- Premium Mobile Scrollable Filter Bar Layout --- */
        .filter-container {
            display: flex;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            padding: 5px;
            border-radius: 14px;
            margin-bottom: 24px;
            gap: 6px;
            overflow-x: auto;
            scrollbar-width: none; /* Hide scrollbar for Firefox */
            -webkit-overflow-scrolling: touch;
        }

        .filter-container::-webkit-scrollbar {
            display: none; /* Hide scrollbar for Chrome/Safari */
        }

        .filter-btn {
            flex: 0 0 auto; /* Prevent shrinking on dynamic content wrapper */
            text-align: center;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.68rem;
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 10px;
            text-transform: uppercase;
            transition: all 0.2s ease;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.01);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        
        .filter-btn.btn-repeat-active {
            background: linear-gradient(135deg, #ff9100, #ff3d00);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 61, 0, 0.25);
            border: 1px solid rgba(255, 61, 0, 0.2);
        }

        .filter-btn.btn-safe-active {
            background: linear-gradient(135deg, #00e676, #00b0ff);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 230, 118, 0.25);
            border: 1px solid rgba(0, 230, 118, 0.2);
        }

        /* --- Modern Backtest Card --- */
        .backtest-card {
            background: linear-gradient(145deg, var(--bg-card), #111a28);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.4s ease-out;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .pattern-identity {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lbl {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .pattern-txt {
            font-family: monospace;
            background: rgba(15, 23, 42, 0.8);
            padding: 4px 8px;
            border-radius: 6px;
            color: #ffffff;
            letter-spacing: 1.5px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }

        .metric-box {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            padding: 10px 6px;
            text-align: center;
        }

        .metric-val {
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 2px;
        }

        .accuracy-section {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .acc-percentage {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .txt-green { color: var(--accent-green); }
        .txt-red { color: var(--accent-red); }
        .txt-blue { color: var(--accent-blue); }

        .status-excellent { color: var(--accent-green); text-shadow: 0 0 10px rgba(0, 230, 118, 0.2); }
        .status-average { color: var(--accent-orange); text-shadow: 0 0 10px rgba(255, 145, 0, 0.2); }
        .status-low { color: var(--accent-red); text-shadow: 0 0 10px rgba(255, 61, 0, 0.2); }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .badge-big { background: rgba(0, 230, 118, 0.12); color: var(--accent-green); border: 1px solid rgba(0, 230, 118, 0.2); }
        .badge-small { background: rgba(56, 189, 248, 0.12); color: var(--accent-blue); border: 1px solid rgba(56, 189, 248, 0.2); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">

    <header class="page-header">
        <div class="top-nav-row">
            <a href="manual_patterns.php" class="btn-back">← Back</a>
            <h2>Pattern Backtest</h2>
            <div style="width: 45px;"></div>
        </div>
    </header>

    <div class="filter-container">
        <a href="?min_hit=0" class="filter-btn <?php echo ($min_hit === '0') ? 'active' : ''; ?>">All Rows</a>
        <a href="?min_hit=15" class="filter-btn <?php echo ($min_hit === '15') ? 'active' : ''; ?>">15+ Hits</a>
        <a href="?min_hit=50" class="filter-btn <?php echo ($min_hit === '50') ? 'active' : ''; ?>">50+ Hits</a>
        <a href="?min_hit=100" class="filter-btn <?php echo ($min_hit === '100') ? 'active' : ''; ?>">100+ Hits</a>
        <a href="?min_hit=repeat" class="filter-btn <?php echo ($min_hit === 'repeat') ? 'btn-repeat-active' : ''; ?>">🔄 Repeat</a>
        <a href="?min_hit=safe5" class="filter-btn <?php echo ($min_hit === 'safe5') ? 'btn-safe-active' : ''; ?>">🛡️ Safe 5+</a>
        <a href="?min_hit=safe10" class="filter-btn <?php echo ($min_hit === 'safe10') ? 'btn-safe-active' : ''; ?>">🛡️ Safe 10+</a>
        <a href="?min_hit=safe15" class="filter-btn <?php echo ($min_hit === 'safe15') ? 'btn-safe-active' : ''; ?>">🛡️ Safe 15+</a>
    </div>

    <?php
    $visible_cards_count = 0;
    if($manual && $manual->num_rows > 0):
        $data_count = count($data);
        
        while($m = $manual->fetch_assoc()){
            $pattern = $m['pattern_code'];
            $predict = $m['predict'];
            $len = strlen($pattern);

            $hit = 0;
            $win = 0;
            $loss = 0;

            // Engine processing calculation loop
            for($i = 0; $i < $data_count - $len; $i++){
                $current = "";
                for($j = 0; $j < $len; $j++){
                    $current .= $data[$i + $j];
                }

                if($current == $pattern){
                    $hit++;
                    $next = $data[$i + $len];
                    $actual = ($next == "B") ? "BIG" : "SMALL";

                    if($actual == $predict){
                        $win++;
                    } else {
                        $loss++;
                    }
                }
            }

            // Calculate accuracy safely
            $acc = 0;
            if($hit > 0){
                $acc = round(($win * 100) / $hit, 2);
            }

            // DYNAMIC MULTI-LEVEL SWITCH ENGINE MATRIX RULES
            if ($min_hit === 'repeat') {
                $unique_chars = array_unique(str_split($pattern));
                if (count($unique_chars) > 1) {
                    continue;
                }
            } elseif ($min_hit === 'safe5') {
                if ($hit < 5 || $acc < 60.00) {
                    continue;
                }
            } elseif ($min_hit === 'safe10') {
                if ($hit < 10 || $acc < 60.00) {
                    continue;
                }
            } elseif ($min_hit === 'safe15') {
                if ($hit < 15 || $acc < 60.00) {
                    continue;
                }
            } else {
                if ($hit < (int)$min_hit) {
                    continue;
                }
            }
            
            $visible_cards_count++;

            // Accuracy Threshold Status Styling Class allocation
            $accStyleClass = 'status-low';
            if ($acc >= 70) {
                $accStyleClass = 'status-excellent';
            } elseif ($acc >= 45) {
                $accStyleClass = 'status-average';
            }

            $predictBadgeClass = ($predict == "BIG") ? "badge-big" : "badge-small";
    ?>
    
    <div class="backtest-card">
        <div class="card-top">
            <div class="pattern-identity" style="flex-direction:column;align-items:flex-start;">
    
    <span class="lbl">
        <?php echo htmlspecialchars($m['pattern_name']); ?>
    </span>

    <span class="pattern-txt">
        <?php echo htmlspecialchars($pattern); ?>
    </span>

</div>
           <div style="display:flex;gap:8px;align-items:center;">

    <span class="badge <?php echo $predictBadgeClass; ?>">
        <?php echo htmlspecialchars($predict); ?>
    </span>

    <a href="?delete=<?php echo $m['id']; ?>"
       onclick="return confirm('Delete Pattern?')"
       style="
       background:#ff3d00;
       color:white;
       text-decoration:none;
       padding:5px 10px;
       border-radius:6px;
       font-size:11px;
       font-weight:700;">
       DELETE
    </a>

</div>
        </div>

        <div class="metrics-grid">
            <div class="metric-box">
                <div class="lbl">Total Hits</div>
                <div class="metric-val txt-blue"><?php echo $hit; ?></div>
            </div>
            <div class="metric-box">
                <div class="lbl">Wins (✔)</div>
                <div class="metric-val txt-green"><?php echo $win; ?></div>
            </div>
            <div class="metric-box">
                <div class="lbl">Losses (✘)</div>
                <div class="metric-val txt-red"><?php echo $loss; ?></div>
            </div>
        </div>

        <div class="accuracy-section">
            <span class="lbl" style="font-size: 0.75rem;">Calculated Accuracy</span>
            <span class="acc-percentage <?php echo $accStyleClass; ?>"><?php echo $acc; ?>%</span>
        </div>
    </div>

    <?php 
        } // End of while loop
    endif; 

    // Empty state fallback handler
    if($visible_cards_count === 0):
    ?>
        <div style="text-align: center; background: var(--bg-card); padding: 35px 20px; border-radius: 20px; border: 1px solid var(--glass-border); color: var(--text-secondary); font-size: 0.85rem;">
            <?php if($min_hit === 'repeat'): ?>
                ⚠️ No pure repeating streak sequences found in configuration database.
            <?php elseif($min_hit === 'safe5'): ?>
                🛡️ No patterns found matching safe criteria (Hits &ge; 5 and Acc &ge; 60%).
            <?php elseif($min_hit === 'safe10'): ?>
                🛡️ No patterns found matching safe criteria (Hits &ge; 10 and Acc &ge; 60%).
            <?php elseif($min_hit === 'safe15'): ?>
                🛡️ No patterns found matching safe criteria (Hits &ge; 15 and Acc &ge; 60%).
            <?php else: ?>
                ⚠️ No active patterns found with at least <?php echo htmlspecialchars($min_hit); ?> system hits.
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
