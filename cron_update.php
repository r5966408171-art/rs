<?php

include("config.php");

/* ---------- ANALYZE ---------- */

$conn->query("
UPDATE trend_settings
SET win=0,
loss=0,
accuracy=0");

$settings = $conn->query("
SELECT DISTINCT pattern_name
FROM trend_predictions
WHERE pattern_name IS NOT NULL
AND pattern_name!=''
");

while($s = $settings->fetch_assoc()){

    $pattern = $s['pattern_name'];
    $conn->query("
INSERT IGNORE INTO trend_settings
(
pattern_name,
win,
loss,
accuracy
)
VALUES
(
'$pattern',
0,
0,
0
)
");

    $win = $conn->query("
    SELECT COUNT(*) total
    FROM trend_predictions
    WHERE pattern_name='$pattern'
AND status='WIN'
    ")->fetch_assoc()['total'];

    $loss = $conn->query("
    SELECT COUNT(*) total
    FROM trend_predictions
    WHERE pattern_name='$pattern'
AND status='LOSS'
    ")->fetch_assoc()['total'];

    $acc = 0;

    if(($win+$loss)>0){
        $acc = round(($win*100)/($win+$loss),2);
    }

    /* ---------- AUTO OPTIMIZE ---------- */

    if($acc >= 75){
        $confidence="VERY HIGH";
        $decision="PLAY";
    }
    else if($acc >= 50){
        $confidence="HIGH";
        $decision="PLAY";
    }
    else if($acc >= 40){
        $confidence="MEDIUM";
        $decision="WAIT";
    }
    else{
        $confidence="LOW";
        $decision="SKIP";
    }

    $conn->query("
    UPDATE trend_settings
    SET
    win='$win',
    loss='$loss',
    accuracy='$acc',
    confidence='$confidence',
    decision='$decision'
    WHERE pattern_name='$pattern'
    ");

}

echo "Cron Update Success";

?>