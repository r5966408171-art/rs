<?php

include("config.php");

$url = "https://draw.ar-lottery01.com/WinGo/WinGo_1M/GetHistoryIssuePage.json";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$json = curl_exec($ch);

curl_close($ch);

$obj = json_decode($json, true);

if (!isset($obj['data']['list'][0]['issueNumber'])) {
    die("API ERROR");
}

$period = $obj['data']['list'][0]['issueNumber'];
$number = $obj['data']['list'][0]['number'];

// Duplicate Check

$check = $conn->query(
"SELECT id FROM game_results
WHERE period='$period'
LIMIT 1"
);

if($check->num_rows > 0){
    die("Already Saved");
}

// Save Result

$response = file_get_contents(
"https://mybhumija.buzz/predictor/save_result.php?period="
.$period.
"&number="
.$number
);

echo $response;
echo date("Y-m-d H:i:s");
echo " - ";
echo $response;
?>