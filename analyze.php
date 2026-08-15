<?php

include("config.php");

// Reset Old Data

$conn->query(
"UPDATE trend_settings
SET win=0,
loss=0,
accuracy=0");

// Get All Patterns

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
    // WIN Count

    $q1 = $conn->query(
    "SELECT COUNT(*)
    AS total
    FROM trend_predictions
    WHERE pattern_name='$pattern'
AND status='WIN'");

    $win = $q1->fetch_assoc()['total'];

    // LOSS Count

    $q2 = $conn->query(
    "SELECT COUNT(*)
    AS total
    FROM trend_predictions
    WHERE pattern_name='$pattern'
AND status='LOSS'");

    $loss = $q2->fetch_assoc()['total'];

    // Accuracy

    if(($win+$loss)==0)
        $acc=0;
    else
        $acc=round(
        ($win*100)/
        ($win+$loss),2);

    // Update Table

    $conn->query(
    "UPDATE trend_settings
    SET
    win='$win',
    loss='$loss',
    accuracy='$acc'
    WHERE pattern_name='$pattern'");

}

echo "<script>
alert('Database Analysis Complete');
window.location='admin.php';
</script>";

?>