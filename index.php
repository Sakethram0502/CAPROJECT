<?php
session_start();

// Demo passwords (change as needed)
$HOD_PASSWORD = 'hod123';
$STAFF_PASSWORD = 'staff123';

$view = $_GET['view'] ?? 'landing';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'hod_login') {
        $password = $_POST['hod_password'] ?? '';
        if ($password === $HOD_PASSWORD) {
            $_SESSION['role'] = 'HOD';
            $_SESSION['username'] = 'HOD';
            header('Location: hod_dashboard.php');
            exit;
        } else {
            $error = 'Invalid HOD password.';
            $view = 'hod';
        }
    } elseif ($formType === 'staff_login') {
        $staffName = $_POST['staff_name'] ?? '';
        $password = $_POST['staff_password'] ?? '';
        if ($staffName && $password === $STAFF_PASSWORD) {
            $_SESSION['role'] = 'Staff';
            $_SESSION['staff_name'] = $staffName;
            header('Location: staff_dashboard.php');
            exit;
        } else {
            $error = 'Invalid staff password or name.';
            $view = 'staff';
        }
    } elseif ($formType === 'student_login') {
        $regNo = trim($_POST['reg_no'] ?? '');
        if ($regNo !== '') {
            $_SESSION['role'] = 'Student';
            $_SESSION['student_reg_no'] = $regNo;
            header('Location: student_dashboard.php');
            exit;
        } else {
            $error = 'Please enter registration number.';
            $view = 'student';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Computer Applications | Project Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background-overlay"></div>
    <div class="water-animation"></div>

    <div class="auth-wrapper">
        <div class="login-card floating">
            <div class="login-header">
                <h1>Department of Computer Applications</h1>
                <h2>Project Management System</h2>
            </div>

            <div class="role-buttons">
                <a href="?view=hod" class="btn-gradient role-btn">HOD</a>
                <a href="?view=staff" class="btn-gradient role-btn">Staff</a>
                <a href="student_dashboard.php" class="btn-gradient role-btn">Student</a>
            </div>

            <?php if ($error): ?>
                <div class="message error" style="margin-top:16px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($view === 'hod'): ?>
                <form method="post" class="login-form role-form">
                    <input type="hidden" name="form_type" value="hod_login">
                    <div class="form-group">
                        <label for="hod_password">HOD Password</label>
                        <input type="password" id="hod_password" name="hod_password" required placeholder="Enter HOD password">
                    </div>
                    <button type="submit" class="btn-gradient login-btn">Login as HOD</button>
                </form>
            <?php elseif ($view === 'staff'): ?>
                <form method="post" class="login-form role-form">
                    <input type="hidden" name="form_type" value="staff_login">
                    <div class="form-group">
                        <label for="staff_name">Select Staff</label>
                        <select id="staff_name" name="staff_name" required>
                            <option value="">-- Select Staff --</option>
                            <option value="Rama">Rama</option>
                            <option value="Sita">Sita</option>
                            <option value="Chandu">Chandu</option>
                            <option value="Mahesh">Mahesh</option>
                            <option value="Dhamu">Dhamu</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="staff_password">Staff Password</label>
                        <input type="password" id="staff_password" name="staff_password" required placeholder="Enter staff password">
                    </div>
                    <button type="submit" class="btn-gradient login-btn">Login as Staff</button>
                </form>
            <?php elseif ($view === 'student'): ?>
                <form method="post" class="login-form role-form">
                    <input type="hidden" name="form_type" value="student_login">
                    <div class="form-group">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="Enter registration number">
                    </div>
                    <button type="submit" class="btn-gradient login-btn">Continue as Student</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

