<?php

include("config.php");

// Error styling block helper
$errorStyle = "
<style>
    body { background-color: #0b0f19; color: #ffffff; font-family: 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
    .err-card { background: #161f30; border: 1px solid rgba(255,255,255,0.05); padding: 30px 20px; border-radius: 20px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 12px 40px rgba(0,0,0,0.5); }
    .err-msg { color: #ff3d00; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; letter-spacing: 0.5px; }
    .btn-back { display: inline-block; background: rgba(255,255,255,0.05); color: #94a3b8; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s; }
    .btn-back:hover { background: rgba(255,255,255,0.1); color: #fff; }
</style>
";

if(!isset($_GET['id'])){
    die($errorStyle . "<div class='err-card'><div class='err-msg'>⚠️ Invalid ID Context Provided</div><a href='manual_patterns.php' class='btn-back'>Back to Patterns</a></div>");
}

$id=(int)$_GET['id'];

$q=$conn->query("
SELECT *
FROM manual_patterns
WHERE id='$id'
LIMIT 1
");

if($q->num_rows==0){
    die($errorStyle . "<div class='err-card'><div class='err-msg'>🔍 Requested Pattern Not Found</div><a href='manual_patterns.php' class='btn-back'>Back to Patterns</a></div>");
}

$data=$q->fetch_assoc();

if(isset($_POST['update'])){

    $pattern = strtoupper(trim($_POST['pattern']));

    $predict = $_POST['predict'];

    $length = strlen($pattern);

    $conn->query("
    UPDATE manual_patterns
    SET
    pattern_code='$pattern',
    predict='$predict',
    pattern_length='$length'
    WHERE id='$id'
    ");

    header("Location: manual_patterns.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pattern #<?php echo $id; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #0b0f19;
            --bg-card: #161f30;
            --accent-green: #00e676;
            --accent-blue: #38bdf8;
            --accent-orange: #f59e0b;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            padding: 12px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }

        /* --- Header Navigation Link Bar --- */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 4px;
        }

        .btn-cancel {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-cancel:hover {
            color: #ffffff;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #ffffff 60%, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        /* --- Form Structure Dashboard Configuration --- */
        .form-card {
            background: linear-gradient(145deg, var(--bg-card), #111a28);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 24px 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        }

        .info-tag {
            font-size: 0.72rem;
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-orange);
            border: 1px solid rgba(245, 158, 11, 0.2);
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.15);
        }

        .form-input {
            letter-spacing: 1.5px;
            font-family: monospace;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 15px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="top-bar">
        <h2>Edit Pattern</h2>
        <a href="manual_patterns.php" class="btn-cancel">✕ Cancel</a>
    </div>

    <div class="form-card">
        <div class="info-tag">Modifying Pattern ID: #<?php echo $id; ?></div>

        <form method="post">
            <div class="form-group">
                <label class="form-label">Pattern Sequence</label>
                <input 
                    type="text" 
                    name="pattern" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($data['pattern_code']); ?>" 
                    required 
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label class="form-label">Predict Target</label>
                <select name="predict" class="form-select">
                    <option value="BIG" <?php if($data['predict'] == "BIG") echo "selected"; ?>>BIG</option>
                    <option value="SMALL" <?php if($data['predict'] == "SMALL") echo "selected"; ?>>SMALL</option>
                </select>
            </div>

            <button type="submit" name="update" class="btn-submit">Update Pattern Metrics</button>
        </form>
    </div>

</div>

</body>
</html>
