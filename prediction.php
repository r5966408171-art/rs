<?php
include("config.php");

// ୭୫ ଟି ରାଉଣ୍ଡର ଡାଟା ଆଣିବା
$query = "SELECT number, bigsmall, color FROM game_results ORDER BY id DESC LIMIT 75";
$result = mysqli_query($conn, $query);

$data = [];
while($row = mysqli_fetch_assoc($result)){ $data[] = $row; }

if(empty($data)){
    die("<div style='color:#ff4d4d; text-align:center; margin-top:50px;'><h3>⚠️ Database ଖାଲି ଅଛି!</h3></div>");
}

/* =======================================================
   AI CORE ENGINE FUNCTIONS (UPDATED FOR NUMBER & BS)
   ======================================================= */
function getAIPredictionForWindow($window_data) {
    $freq = array_fill(0, 10, 0);
    foreach($window_data as $row){
        $num = intval($row['number']);
        if($num >= 0 && $num <= 9) $freq[$num]++;
    }
    $missing = [];
    for($i = 0; $i <= 9; $i++){
        $pos = -1;
        foreach($window_data as $k => $row){
            if(intval($row['number']) == $i){ $pos = $k; break; }
        }
        $missing[$i] = ($pos == -1) ? 50 : $pos;
    }
    $scores = [];
    for($i = 0; $i <= 9; $i++){ $scores[$i] = $missing[$i] + (50 - $freq[$i]); }
    arsort($scores);
    $top = array_keys($scores);
    
    // ଏହି ଫଙ୍କସନ୍ ଏବେ Number ଏବଂ Big/Small ଦୁଇଟିଯାକ ରିଟର୍ଣ୍ଣ କରିବ
    return [
        'number' => $top[0],
        'bs' => ($top[0] >= 5) ? "BIG" : "SMALL"
    ];
}

// ୧. ବର୍ତ୍ତମାନର ଲାଇଭ୍ ପ୍ରେଡିକ୍ସନ୍ ହିସାବ
// ୫୦ ବଦଳରେ ୩୫ କରନ୍ତୁ
$current_window = array_slice($data, 0, 35); 
$ai_live = getAIPredictionForWindow($current_window);
$predicted_num = $ai_live['number'];
$predictBS = $ai_live['bs'];

// ଟପ୍ ୩ ଟାର୍ଗେଟ୍ ନମ୍ବର ବାହାର କରିବା (ପୂର୍ବ ଭଳି ଦେଖାଇବା ପାଇଁ)
$freq = array_fill(0, 10, 0);
foreach($current_window as $row){ $num = intval($row['number']); if($num >= 0 && $num <= 9) $freq[$num]++; }
$missing = [];
for($i = 0; $i <= 9; $i++){
    $pos = -1;
    foreach($current_window as $k => $row){ if(intval($row['number']) == $i){ $pos = $k; break; } }
    $missing[$i] = ($pos == -1) ? 50 : $pos;
}
$scores = [];
for($i = 0; $i <= 9; $i++){ $scores[$i] = $missing[$i] + (50 - $freq[$i]); }
arsort($scores);
$topNumbers = array_slice(array_keys($scores), 0, 3);

// ୨. ଲାଇଭ୍ WIN RATE (%) ପ୍ରି-କାଲକୁଲେସନ୍
$win_count = 0;
for($i = 0; $i < 20; $i++) {
    if(!isset($data[$i + 50])) break;
    // ହିଷ୍ଟ୍ରି ଚେକର୍ ଭିତରେ ମଧ୍ୟ ୩୫ କରନ୍ତୁ
$past_window = array_slice($data, $i + 1, 35); 
    $ai_past = getAIPredictionForWindow($past_window);
    if($ai_past['bs'] == strtoupper($data[$i]['bigsmall'])) {
        $win_count++;
    }
}
$live_win_rate = ($win_count / 20) * 100;

// ୩. DRAGON TREND DETECTOR
$dragon_count = 1;
$first_bs = $data[0]['bigsmall'];
for($i = 1; $i < 6; $i++) {
    if(isset($data[$i]['bigsmall']) && $data[$i]['bigsmall'] == $first_bs) { $dragon_count++; } else { break; }
}
?>

<!DOCTYPE html>
<html lang="or">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Premium Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { background-color: #0b0f19; } </style>
</head>
<body class="text-gray-100 font-sans antialiased">

    <div class="max-w-md mx-auto min-h-screen flex flex-col justify-between p-4 space-y-4">
        
        <!-- Header -->
        <header class="text-center my-4">
            <h1 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-cyan-500 tracking-wider">
                BHUMIJA AI PRO
            </h1>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-1">Predictor Engine v4.0 Jackpot Edition</p>
        </header>

        <!-- Live Analytics Counter -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800 text-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">20 Rnds Accuracy</span>
                <div class="text-2xl font-black text-green-400 mt-0.5"><?= $live_win_rate ?>%</div>
            </div>
            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800 text-center flex flex-col justify-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Market Status</span>
                <span class="text-xs font-bold mt-1 text-cyan-400">JACKPOT LIVE MODE</span>
            </div>
        </div>

        <!-- Dragon Trend Alert -->
        <?php if($dragon_count >= 3): ?>
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 flex items-center space-x-3 animate-pulse">
            <span class="text-xl">🐉</span>
            <div class="text-xs text-red-300">
                <b>DRAGON ALERT:</b> ଲଗାତାର <span class="underline font-black text-white"><?= $dragon_count ?> ଥរ</span> ହେବ <b><?= strtoupper($first_bs) ?></b> ଆସୁଛି। ବେଟିଂ ରିସ୍କ ଅଧିକ ଅଛି!
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Dashboard Card -->
        <main class="space-y-4 flex-1">
            
            <!-- Live Prediction Size -->
            <div class="bg-slate-900 rounded-2xl p-6 border border-slate-800 shadow-xl text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 p-1.5 bg-green-500/10 rounded-bl-lg text-[9px] font-mono text-green-400 tracking-wider">LIVE PREDICTOR</div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">NEXT ROUND SIZE</h3>
                <div class="text-5xl font-black <?= ($predictBS == 'BIG') ? 'text-amber-400' : 'text-indigo-400' ?> tracking-wider">
                    <?= $predictBS ?>
                </div>
            </div>

            <!-- Top 3 Probable Numbers -->
            <div class="bg-slate-900 rounded-2xl p-4 border border-slate-800 shadow-xl">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-center mb-3">🎯 Top 3 High Probability Numbers</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-slate-950 p-2.5 rounded-xl border border-green-500/30">
                        <span class="text-[9px] text-green-400 block font-mono">Rank 1</span>
                        <span class="text-2xl font-black text-white"><?= $topNumbers[0] ?></span>
                    </div>
                    <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[9px] text-gray-500 block font-mono">Rank 2</span>
                        <span class="text-xl font-bold text-gray-300"><?= $topNumbers[1] ?></span>
                    </div>
                    <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[9px] text-gray-500 block font-mono">Rank 3</span>
                        <span class="text-xl font-bold text-gray-300"><?= $topNumbers[2] ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent 20 Rounds History Table -->
            <div class="bg-slate-900 rounded-2xl p-4 border border-slate-800 shadow-xl">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-center mb-3">📊 Recent 20 Rounds Trend</h3>
                <div class="overflow-hidden rounded-xl border border-slate-800/60 bg-slate-950/40">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-slate-950 text-[10px] font-semibold uppercase text-gray-500 border-b border-slate-800">
                                <th class="py-2.5">No.</th>
                                <th class="py-2.5">Result</th>
                                <th class="py-2.5">AI Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-900 text-sm">
                            <?php 
                            for($i = 0; $i < 20; $i++):
                                if(!isset($data[$i + 50])) break;

                                $h_num = intval($data[$i]['number']);
                                $h_bs = strtoupper($data[$i]['bigsmall']);
                                
                                // 🧠 ପାଷ୍ଟ ୱିଣ୍ଡୋରୁ AI ର Number ଏବଂ Size ଦୁଇଟିଯାକ ରେକର୍ଡ ବାହାର କରିବା
                                $past_window = array_slice($data, $i + 1, 50);
                                $ai_past = getAIPredictionForWindow($past_window);
                                $ai_pred_num = $ai_past['number'];
                                $ai_pred_bs = $ai_past['bs'];

                                // 🎯 ================= NEW JACKPOT & WIN/LOSS LOGIC =================
                                if($ai_pred_num == $h_num) {
                                    // 👑 ଯଦି ନମ୍ବର ସିଧା ମ୍ୟାଚ୍ ହେଲା -> JACKPOT!
                                    $statusText = "👑 JACKPOT (No. " . $h_num . " Won)";
                                    $statusClass = "text-amber-300 bg-gradient-to-r from-amber-600/30 to-yellow-500/20 border border-amber-500/40 font-extrabold animate-pulse";
                                } elseif($ai_pred_bs == $h_bs) {
                                    // 🟢 ଯଦି କେବଳ Size ମ୍ୟାଚ୍ ହେଲା -> WIN
                                    $statusText = "WIN";
                                    $statusClass = "text-green-400 bg-green-500/10 border border-green-500/20 font-bold";
                                } else {
                                    // 🔴 ଯଦି କିଛି ମ୍ୟାଚ୍ ହେଲାନି -> LOSS
                                    $statusText = "LOSS";
                                    $statusClass = "text-red-400 bg-red-500/10 border border-red-500/20";
                                }
                            ?>
                            <tr class="hover:bg-slate-900/50">
                                <td class="py-2.5 font-mono font-bold">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-900 border border-slate-800 text-xs text-white">
                                        <?= $h_num ?>
                                    </span>
                                </td>
                                <td class="py-2.5 text-xs font-black <?= ($h_bs == 'BIG') ? 'text-amber-400' : 'text-indigo-400' ?>">
                                    <?= $h_bs ?>
                                </td>
                                <td class="py-2.5">
                                    <!-- Dynamic Badge (Win / Loss / Jackpot) -->
                                    <span class="inline-block px-3 py-1 rounded-md text-[10px] tracking-wide <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <!-- Footer / 3 Seconds Auto-refresh Script -->
        <footer class="text-center text-[9px] text-gray-600 my-2">
            <p>© Powered by Bhumija AI Engine</p>
            <script>
                // ⏱️ ୩ ସେକେଣ୍ଡରେ ପେଜ୍ ଅଟୋ-ରିଫ୍ରେଶ୍ ହେବ
                setTimeout(function(){ window.location.reload(); }, 3000);
            </script>
        </footer>

    </div>

</body>
</html>
