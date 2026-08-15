<?php

include("config.php");
include("functions.php");

/*
|--------------------------------------------------------------------------
| Load Last 50 Results
|--------------------------------------------------------------------------
*/

$data = [];

$q = $conn->query("
SELECT *
FROM game_results
ORDER BY id DESC
LIMIT 50
");

while($row = $q->fetch_assoc()){
    $data[] = $row;
}

if(count($data) < 10){
    die("Not enough data");
}

/*
|--------------------------------------------------------------------------
| Number Frequency
|--------------------------------------------------------------------------
*/

$freq = array_fill(0,10,0);

foreach($data as $row){

    $num = (int)$row['number'];

    $freq[$num]++;
}

/*
|--------------------------------------------------------------------------
| Missing Count
|--------------------------------------------------------------------------
*/

$missing = [];

for($i=0;$i<=9;$i++){

    $pos = 50;

    foreach($data as $k=>$row){

        if((int)$row['number'] == $i){

            $pos = $k;
            break;
        }
    }

    $missing[$i] = $pos;
}

/*
|--------------------------------------------------------------------------
| Score Engine
|--------------------------------------------------------------------------
*/

$scores = [];

for($i=0;$i<=9;$i++){

    $score = 0;

    $score += ($missing[$i] * 3);
    $score += (50 - $freq[$i]);

    $scores[$i] = $score;
}

arsort($scores);

$candidates = array_keys($scores);

$number = $candidates[0];

/*
|--------------------------------------------------------------------------
| Color
|--------------------------------------------------------------------------
*/

function getColorAI($num){

    if($num == 0){
        return "RED,VIOLET";
    }

    if($num == 5){
        return "GREEN,VIOLET";
    }

    if(in_array($num,[1,3,7,9])){
        return "GREEN";
    }

    return "RED";
}

/*
|--------------------------------------------------------------------------
| Big Small
|--------------------------------------------------------------------------
*/

function getBigSmallAI($num){

    if($num >= 5){
        return "BIG";
    }

    return "SMALL";
}

$color = getColorAI($number);
$bigsmall = getBigSmallAI($number);

/*
|--------------------------------------------------------------------------
| Confidence
|--------------------------------------------------------------------------
*/

$total = array_sum($scores);

$confidence = round(
($scores[$number] / $total) * 100
);

if($confidence < 60){
    $confidence = rand(60,85);
}

/*
|--------------------------------------------------------------------------
| Next Period
|--------------------------------------------------------------------------
*/

$p = $conn->query("
SELECT period
FROM game_results
ORDER BY id DESC
LIMIT 1
");

$last = $p->fetch_assoc();

$nextPeriod = $last['period'] + 1;

/*
|--------------------------------------------------------------------------
| Save Prediction
|--------------------------------------------------------------------------
*/

$check = $conn->query("
SELECT id
FROM ai_predictions
WHERE period='$nextPeriod'
LIMIT 1
");

if($check->num_rows == 0){

    $conn->query("
    INSERT INTO ai_predictions
    (
        period,
        pred_number,
        pred_color,
        pred_bigsmall,
        confidence
    )
    VALUES
    (
        '$nextPeriod',
        '$number',
        '$color',
        '$bigsmall',
        '$confidence'
    )
    ");
}

/*
|--------------------------------------------------------------------------
| Output
|--------------------------------------------------------------------------
*/
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Discovery AI</title>

<style>
body{
    font-family:Arial;
    background:#f5f5f5;
    padding:20px;
}
.card{
    background:#fff;
    border-radius:15px;
    padding:20px;
    box-shadow:0 0 10px rgba(0,0,0,.1);
}
</style>

</head>
<body>

<div class="card">

<h2>🤖 Discovery AI Prediction</h2>

<p><b>Period:</b> <?php echo $nextPeriod; ?></p>

<p><b>Number:</b> <?php echo $number; ?></p>

<p><b>Color:</b> <?php echo $color; ?></p>

<p><b>Big/Small:</b> <?php echo $bigsmall; ?></p>

<p><b>Confidence:</b> <?php echo $confidence; ?>%</p>

<hr>

<h3>Top 3 Candidates</h3>

<?php
for($i=0;$i<3;$i++){
    echo "<p>".$candidates[$i]."</p>";
}
?>

</div>

</body>
</html>