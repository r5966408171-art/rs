<?php
include("config.php");

$result = mysqli_query($conn,"
    SELECT number,bigsmall,color
    FROM game_results
    ORDER BY id DESC
    LIMIT 50
");

$data = [];

while($row = mysqli_fetch_assoc($result)){
    $data[] = $row;
}

if(empty($data)){
    die("No data found");
}

/* Number Frequency */
$freq = array_fill(0,10,0);

foreach($data as $row){
    $num = intval($row['number']);
    $freq[$num]++;
}

/* Missing Count */
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

/* Big Small */
$big = 0;
$small = 0;

foreach($data as $row){

    if(strtoupper($row['bigsmall'])=="BIG"){
        $big++;
    }else{
        $small++;
    }
}

/* Color Count */
$green = 0;
$red = 0;
$violet = 0;

foreach($data as $row){

    $color = strtoupper($row['color']);

    if(strpos($color,"GREEN") !== false){
        $green++;
    }

    if(strpos($color,"RED") !== false){
        $red++;
    }

    if(strpos($color,"VIOLET") !== false){
        $violet++;
    }
}

/* Score Engine */
$scores = [];

for($i=0;$i<=9;$i++){

    $score = 0;

    // Missing Weight
    $score += $missing[$i] * 2;

    // Frequency Weight
    $score += (50 - $freq[$i]);

    $scores[$i] = $score;
}

arsort($scores);

$topNumbers = array_slice(array_keys($scores),0,3);

/* Predict Big Small */
$predictBS = ($big > $small) ? "SMALL" : "BIG";

/* Predict Color */
if($red > $green){
    $predictColor = "GREEN";
}else{
    $predictColor = "RED";
}

?>
<!DOCTYPE html>
<html>
<head>
<title>AI Analysis</title>
</head>
<body>

<h2>AI Analysis Report</h2>

<p>BIG Count : <?= $big ?></p>
<p>SMALL Count : <?= $small ?></p>

<p>GREEN Count : <?= $green ?></p>
<p>RED Count : <?= $red ?></p>

<hr>

<h3>Prediction</h3>

<p>Number #1 : <?= $topNumbers[0] ?></p>
<p>Number #2 : <?= $topNumbers[1] ?></p>
<p>Number #3 : <?= $topNumbers[2] ?></p>

<p>Color : <?= $predictColor ?></p>
<p>Big/Small : <?= $predictBS ?></p>

</body>
</html>