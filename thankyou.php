<?php
session_start();
$username = $_SESSION['username'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You | Project Submission</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background-overlay"></div>
    <div class="water-animation"></div>

    <div class="auth-wrapper">
        <div class="login-card floating">
            <div class="login-header">
                <h1>Thank you for submitting your project successfully.</h1>
                <h2><?php echo htmlspecialchars($username); ?></h2>
            </div>
            <div style="text-align:center; margin-top: 16px;">
                <a href="logout.php" class="btn-gradient" style="display:inline-block; text-decoration:none; width:auto; padding:10px 26px;">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>

