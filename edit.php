<?php

include("config.php");

$id = $_GET['id'];

if(isset($_POST['save'])){

    $confidence = $_POST['confidence'];
    $decision   = $_POST['decision'];
    $active     = $_POST['active'];

    $conn->query(
    "UPDATE trend_settings SET

    confidence='$confidence',
    decision='$decision',
    is_active='$active'

    WHERE id='$id'");

    header("location:admin.php");
}

$data = $conn->query(
"SELECT * FROM trend_settings
WHERE id='$id'");

$row = $data->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pattern - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Input aur Select options ko sleek glassmorphism design diya hai */
        select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-card-alt);
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            transition: all 0.3s ease;
            appearance: none; /* Default browser arrow hatane ke liye */
            cursor: pointer;
        }

        select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.15);
        }

        select option {
            background: var(--bg-card);
            color: var(--text-main);
        }

        /* Back link styling */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            font-size: 0.88rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--accent-blue);
        }
    </style>
</head>
<body>

<div class="app-container" style="max-width: 450px; padding-top: 20px;">

    <a href="admin.php" class="back-link">← Back to Dashboard</a>

    <div class="card" style="padding: 24px 20px;">
        <div class="main-title" style="margin-top: 0; margin-bottom: 24px; font-size: 1.3rem; text-align: left;">
            Edit Pattern Settings
        </div>

        <form method="post">

            <div class="form-group">
                <label>Confidence Level</label>
                <select name="confidence">
                    <option <?php if($row['confidence']=="VERY HIGH") echo "selected"; ?>>VERY HIGH</option>
                    <option <?php if($row['confidence']=="HIGH") echo "selected"; ?>>HIGH</option>
                    <option <?php if($row['confidence']=="MEDIUM") echo "selected"; ?>>MEDIUM</option>
                    <option <?php if($row['confidence']=="LOW") echo "selected"; ?>>LOW</option>
                </select>
            </div>

            <div class="form-group">
                <label>System Decision</label>
                <select name="decision">
                    <option <?php if($row['decision']=="PLAY") echo "selected"; ?>>PLAY</option>
                    <option <?php if($row['decision']=="WAIT") echo "selected"; ?>>WAIT</option>
                    <option <?php if($row['decision']=="SKIP") echo "selected"; ?>>SKIP</option>
                </select>
            </div>

            <div class="form-group">
                <label>Pattern Status</label>
                <select name="active">
                    <option value="1" <?php if($row['is_active']==1) echo "selected"; ?>>ON</option>
                    <option value="0" <?php if($row['is_active']==0) echo "selected"; ?>>OFF</option>
                </select>
            </div>

            <button name="save" class="btn-action btn-analyze" style="border-radius: 12px; margin-top: 10px; width: 100%;">
                SAVE CHANGES
            </button>

        </form>
    </div>

</div>

</body>
</html>
