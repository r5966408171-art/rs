<?php

session_start();

include("config.php");

if(isset($_POST['login'])){

$user=$_POST['username'];
$pass=$_POST['password'];

$q=$conn->query(
"SELECT *
FROM admin_user

WHERE username='$user'
AND password='$pass'");

if($q->num_rows>0){

$_SESSION['admin']="YES";

header("location:admin.php");

}else{

echo "<script>
alert('Invalid Login');
</script>";

}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Trend Predictor</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        body {
            align-items: center; /* Login box ko vertical center karne ke liye */
        }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Inputs ko sleek premium design diya hai */
        input {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-card-alt);
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: #4b5563;
        }

        input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
            background: rgba(26, 35, 51, 0.8);
        }
    </style>
</head>
<body>

<div class="app-container" style="max-width: 400px; padding: 10px;">

    <div class="card" style="padding: 32px 24px; text-align: center;">
        
        <div style="font-size: 2.5rem; margin-bottom: 10px;">🔒</div>
        <div class="main-title" style="margin-top: 0; margin-bottom: 30px; font-size: 1.4rem;">
            Admin Portal
        </div>

        <form method="post">

            <div class="form-group">
                <label>Username</label>
                <input 
                    type="text"
                    name="username" 
                    placeholder="Enter admin username" 
                    required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required>
            </div>

            <button name="login" class="btn-action btn-analyze" style="border-radius: 12px; margin-top: 15px; width: 100%;">
                SECURE LOGIN
            </button>

        </form>
    </div>

    <div class="footer-stamp" style="justify-content: center; gap: 6px; margin-top: 15px; opacity: 0.7;">
        <span>🛡️</span>
        <span>End-to-End Encrypted Session</span>
    </div>

</div>

</body>
</html>
