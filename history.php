<?php

include("config.php");

$result = $conn->query("
SELECT period, number, bigsmall
FROM game_results
ORDER BY id DESC
");

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = [
        "period" => $row["period"],
        "number" => $row["number"],
        "bigsmall" => $row["bigsmall"]
    ];
}

file_put_contents(
    "game_results.json",
    json_encode($data, JSON_PRETTY_PRINT)
);

echo "JSON Created Successfully";

?>