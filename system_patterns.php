<?php

include("config.php");
include("functions.php");

if(
!isset($_GET['period'])
||
!isset($_GET['number'])
){
    die("Missing Data");
}

$period=$_GET['period'];
$check=$conn->query(
"SELECT id
FROM game_results
WHERE period='$period'
LIMIT 1"
);

if($check->num_rows>0){

    die("Already Saved");

}
$number=$_GET['number'];

$bigsmall=getBigSmall($number);

// SAVE HISTORY

$conn->query("
INSERT INTO game_results
(period,number,bigsmall)
VALUES
('$period','$number','$bigsmall')
");

/* KEEP ONLY LAST 3000 RECORDS */

$conn->query("
DELETE FROM game_results
WHERE id NOT IN (
    SELECT id FROM (
        SELECT id
        FROM game_results
        ORDER BY id DESC
        LIMIT 3000
    ) x
)
");

// GET LAST PREDICTION

$q=$conn->query("
SELECT *
FROM trend_predictions
ORDER BY id DESC
LIMIT 1
");

if($q->num_rows>0){

    $last=$q->fetch_assoc();

    if($last['status']=="PENDING"){

        if(
        $last['bias']==$bigsmall
        ){

            $conn->query("
            UPDATE trend_statistics
            SET total_win=
            total_win+1
            WHERE id=1
            ");

            $status="WIN";

        }else{

            $conn->query("
            UPDATE trend_statistics
            SET total_loss=
            total_loss+1
            WHERE id=1
            ");

            $status="LOSS";

        }

        $conn->query("
        UPDATE trend_predictions
        SET
        actual='$bigsmall',
        status='$status'
        WHERE id=".$last['id']);

    }

}

// CREATE NEW PREDICTION

$list=getLastEight($conn);

if(count($list)>=8){

    $pattern=getPattern($list);

$trend=detectTrend($pattern);

$bias=getBias(
$trend,
$pattern
);

$confidence=
getConfidence(
$conn,
$trend
);
$last5 = substr($pattern,-5);

$last6 = substr($pattern,-6);

$last7 = substr($pattern,-7);
$last8 = substr($pattern,-8);
// Manual Ai
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
ORDER BY priority ASC,
pattern_length DESC
LIMIT 1
");
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
ORDER BY
priority ASC,
pattern_length DESC
LIMIT 1
");
$patternName = "Default Logic";

if($manual->num_rows > 0){

    $m = $manual->fetch_assoc();

    $bias = $m['predict'];

    $confidence = "MANUAL AI";

    $trend = "MANUAL AI";

    $patternName = $m['pattern_name'];

}
else if($system->num_rows > 0){

    $s = $system->fetch_assoc();

    $bias = $s['predict'];

    $confidence = "SYSTEM AI";

    $trend = "SYSTEM AI";

    $patternName = $s['pattern_name'];

}
// DISCOVERY AI

if(
$manual->num_rows == 0
&&
$system->num_rows == 0
){
    $last6 = substr($pattern,-6);

    $ai = $conn->query("
    SELECT *
    FROM ai_patterns
    WHERE pattern_code='$last6'
    AND status=1
    LIMIT 1
    ");

    if($ai->num_rows > 0){

    $aiData = $ai->fetch_assoc();

    $trend = "DISCOVERY AI";

    $confidence = "DENGEROUS";

    if(
    $aiData['type']=="ELITE"
    ||
    $aiData['type']=="PREMIUM"
    ){

        $bias = $aiData['predict'];

    }

}

}

$nextPeriod=$period+1;

    $nextPeriod=$period+1;

    $conn->query("
    INSERT INTO trend_predictions
(
period,
pattern,
pattern_name,
trend_type,
bias,
confidence
)
VALUES
(
'$nextPeriod',
'$pattern',
'$patternName',
'$trend',
'$bias',
'$confidence'
)
    ");

}

echo "SUCCESS";

?>