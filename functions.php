<?php
    function detectTrend($pattern){
    return "Default Logic";
}
// BIG / SMALL

function getBigSmall($number){

    if($number <= 4){
        return "SMALL";
    }else{
        return "BIG";
    }

}

function detectSignal($pattern, $lossStreak = 0)
{
    // 1. Streak Logic: 2 ya usse zyada loss par Caution
    if ($lossStreak >= 2) {
        return ['signal' => 'CAUTION', 'status' => 'WARNING: Recent Losses Detected'];
    }

    // 2. Danger Patterns (Risk Management)
    $dangerPatterns = [
        'BSBS', 'SBSB', 'SBBS', 'BBSB',
        'BSBBSSBS', 'SBBSBSBB', 'BBSSSBSS', 'BBSBBBBS', 'BBSSSSBB', 'SBSSBBSB',
        'BSBSSBBS', 'SSSSSBBB', 'SSBBBBBB', 'BSSSBSSB', 'BBBBBBSS', 
        'BBBSSSSS', 'BBSSBSBB', 'BBSSSBSS', 'SBBSSSBS', 'BSBBSSBS', 'SSBBBBSB'
    ];

    foreach ($dangerPatterns as $danger) {
        if (strpos($pattern, $danger) !== false) {
            return ['signal' => 'SKIP', 'status' => 'DANGER Ignore'];
        }
    }

    // 3. Pattern Detection Logic
    $last4 = substr($pattern, -4);
    if ($last4 == "BBBB") {
        return ['signal' => 'WATCH_SMALL', 'status' => 'REVERSAL'];
    }
    if ($last4 == "SSSS") {
        return ['signal' => 'WATCH_BIG', 'status' => 'REVERSAL'];
    }

    $last2 = substr($pattern, -2);
    if ($last2 == "BB") {
        return ['signal' => 'BIG', 'status' => 'ENTRY'];
    }
    if ($last2 == "SS") {
        return ['signal' => 'SMALL', 'status' => 'ENTRY'];
    }

    return ['signal' => 'NORMAL', 'status' => 'NO SIGNAL'];
}

// LAST 8 RESULTS
function getHistory($conn){

    return $conn->query(
    "SELECT
    g.period,
    g.number,
    g.bigsmall,
    p.pattern_name,
    p.bias,
    p.actual,
    p.status
     FROM game_results g

     LEFT JOIN trend_predictions p
     ON g.period = p.period

     ORDER BY g.id DESC
     LIMIT 20");

}

function getLastEight($conn){

    $list = [];

    $sql = $conn->query(
    "SELECT bigsmall
    FROM game_results
    ORDER BY id DESC
    LIMIT 8");

    while($row = $sql->fetch_assoc()){

        $list[] = $row['bigsmall'];

    }

    return array_reverse($list);

}


// SHORT PATTERN

function getPattern($data){

    $txt="";

    foreach($data as $d){

        if($d=="BIG")
            $txt.="B";
        else
            $txt.="S";

    }

    return $txt;

}


// COUNT BIG

function totalBig($data){

    $c=0;

    foreach($data as $d){

        if($d=="BIG")
            $c++;

    }

    return $c;

}


// COUNT SMALL

function totalSmall($data){

    $c=0;

    foreach($data as $d){

        if($d=="SMALL")
            $c++;

    }

    return $c;

}
  // Recovery Pattern
function getBias($trend,$pattern){

    $last = substr($pattern,-1);

    if($last=="B"){
        return "BIG";
    }

    return "SMALL";
}
function getConfidence($conn,$trend){

    $q = $conn->query(
    "SELECT confidence
    FROM trend_settings
    WHERE pattern_name='$trend'
    LIMIT 1");

    if($q->num_rows > 0){
        $r = $q->fetch_assoc();
        return $r['confidence'];
    }

    return "MEDIUM";
}
// Take Decision 
function getDecision($conn,$trend){

    $q = $conn->query(
    "SELECT decision
    FROM trend_settings
    WHERE pattern_name='$trend'
    LIMIT 1");

    if($q->num_rows > 0){
        $r = $q->fetch_assoc();
        return $r['decision'];
    }

    return "WAIT";
}
// GET WIN

function getWin($conn){

    $q=$conn->query(
    "SELECT total_win
    FROM trend_statistics
    WHERE id=1");

    $r=$q->fetch_assoc();

    return $r['total_win'];

}


// GET LOSS

function getLoss($conn){

    $q=$conn->query(
    "SELECT total_loss
    FROM trend_statistics
    WHERE id=1");

    $r=$q->fetch_assoc();

    return $r['total_loss'];

}


// ACCURACY

function getAccuracy($conn){

    $win=getWin($conn);

    $loss=getLoss($conn);

    if(($win+$loss)==0)
        return 0;

    return round(
    ($win*100)/
    ($win+$loss),2);

}

/* -----------------------------
   TOP 3 AI NUMBER PREDICTION
------------------------------*/

function getTopNumbers($conn){

    $result = $conn->query("
    SELECT number
    FROM game_results
    ORDER BY id DESC
    LIMIT 50
    ");

    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    if(empty($data)){
        return [0,1,2];
    }

    $freq = array_fill(0,10,0);

    foreach($data as $row){
        $freq[intval($row['number'])]++;
    }

    $missing = [];

    for($i=0;$i<=9;$i++){

        $pos = -1;

        foreach($data as $k=>$row){

            if(intval($row['number']) == $i){
                $pos = $k;
                break;
            }
        }

        $missing[$i] = ($pos == -1) ? 50 : $pos;
    }

    $scores = [];

    for($i=0;$i<=9;$i++){

        $score = 0;

        $score += ($missing[$i] * 2);
        $score += (50 - $freq[$i]);

        $scores[$i] = $score;
    }

    arsort($scores);

    return array_slice(array_keys($scores),0,3);
}

?>