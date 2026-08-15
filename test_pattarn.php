
<?php

include("config.php");
include("functions.php");

$list = getLastEight($conn);

echo "<pre>";

print_r($list);

echo "</pre>";

echo "<hr>";

echo "Pattern : ";
echo getPattern($list);

echo "<br><br>";

echo "BIG : ";
echo totalBig($list);

echo "<br>";

echo "SMALL : ";
echo totalSmall($list);
echo "<br><br>";

echo "Trend : ";

echo detectTrend(
getPattern($list)
);
echo "<br><br>";

echo "Next Bias : ";

echo getBias(
detectTrend(getPattern($list)),
getPattern($list)
);
echo "<br><br>";

echo "Confidence : ";

echo getConfidence(
detectTrend(
getPattern($list)
)
);

?>