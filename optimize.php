<?php

include("config.php");

$data = $conn->query(
"SELECT * FROM trend_settings");

while($r = $data->fetch_assoc()){

    $acc = $r['accuracy'];

    if($acc >= 60){

        $confidence = "VERY HIGH";
        $decision = "PLAY";

    }
    else if($acc >= 50){

        $confidence = "HIGH";
        $decision = "PLAY";

    }
    else if($acc >= 40){

        $confidence = "MEDIUM";
        $decision = "WAIT";

    }
    else{

        $confidence = "LOW";
        $decision = "SKIP";

    }

    $conn->query(
    "UPDATE trend_settings
    SET

    confidence='$confidence',
    decision='$decision'

    WHERE id='".$r['id']."'");

}

echo "<script>

alert('Auto Optimization Complete');

window.location='admin.php';

</script>";

?>