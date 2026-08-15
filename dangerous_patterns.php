<?php
include("config.php");

$result = $conn->query("
SELECT *
FROM ai_patterns
WHERE type='DANGEROUS'
ORDER BY accuracy ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dangerous Patterns - Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-red: #f43f5e;
            --accent-blue: #3b82f6;
        }

        body {
            background-color: var(--bg-main) !important;
            color: var(--text-main) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            margin: 0;
            padding: 0;
        }

        .app-container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .header-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-bottom: 2px solid var(--accent-red);
        }

        .badge-btn {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .badge-blue {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-blue:hover {
            background: var(--accent-blue);
            color: white;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f8fafc;
        }

        /* Table Styling */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 0.95rem;
        }

        th {
            background: rgba(51, 65, 85, 0.5);
            color: var(--text-muted);
            font-weight: 600;
            padding: 14px 10px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .danger-badge {
            background: rgba(244, 63, 94, 0.1);
            color: var(--accent-red);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
        }

        .footer-stamp {
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
            font-size: 0.75rem;
            padding: 10px 4px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 25px;
        }
    </style>
</head>
<body>

<div class="app-container">

    <div class="premium-card header-card">
        <div class="section-title">🚫 Dangerous Patterns</div>
        <a href="admin.php" class="badge-btn badge-blue">← Dashboard</a>
    </div>

    <div class="premium-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 16px;">Pattern Code</th>
                        <th>Total Hits</th>
                        <th>Accuracy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()){ 
                    ?>
                    <tr>
                        <td style="text-align: left; padding-left: 16px; font-family: monospace; font-weight: 700; color: #e2e8f0;">
                            <?php echo htmlspecialchars($row['pattern_code']); ?>
                        </td>
                        <td style="color: #cbd5e1; font-weight: 600;"><?php echo $row['hit']; ?></td>
                        <td>
                            <span class="danger-badge"><?php echo $row['accuracy']; ?>%</span>
                        </td>
                    </tr>
                    <?php 
                        } 
                    } else {
                    ?>
                    <tr>
                        <td colspan="3" style="padding: 30px; color: var(--text-muted); font-style: italic;">
                            No dangerous patterns identified yet.
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-stamp">
        <span>⚠️ Filtered by System Risk Matrix</span>
        <span>Secure Stream OK</span>
    </div>

</div>

</body>
</html>
