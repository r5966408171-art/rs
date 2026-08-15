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
function getColor($number){

    if($number == 0){
        return "RED,VIOLET";
    }

    if($number == 5){
        return "GREEN,VIOLET";
    }

    if(in_array($number,[1,3,7,9])){
        return "GREEN";
    }

    return "RED";
}

$color = getColor($number);

$bigsmall=getBigSmall($number);

// SAVE HISTORY

$conn->query("
INSERT INTO game_results
(period,number,bigsmall,color)
VALUES
('$period','$number','$bigsmall','$color')
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
$patternName = "Default Logic";
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
if($manual->num_rows > 0){

    $m = $manual->fetch_assoc();

    $bias = $m['predict'];

    $confidence = "MANUAL AI";

    $trend = "MANUAL AI";

    $patternName = $m['pattern_name'];


}

//system ai
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
ORDER BY priority ASC,
pattern_length DESC
LIMIT 1
");

if(
$manual->num_rows == 0
&&
$system->num_rows > 0
){

    $s = $system->fetch_assoc();

    $bias = $s['predict'];

    $confidence = "SYSTEM AI";

    $trend = "SYSTEM AI";

    $patternName = $s['pattern_name'];

}

$nextPeriod=$period+1;

    $nextPeriod=$period+1;

    $conn->query("
    INSERT INTO
    trend_predictions
    (
    period,
    pattern,
    trend_type,
    bias,
    confidence,
    source,
    pattern_name
    )
    VALUES
    (
    '$nextPeriod',
    '$pattern',
    '$trend',
    '$bias',
    '$confidence',
    '$trend',
    '$patternName'
    )
    ");

}

echo "SUCCESS";

?>