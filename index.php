<?php
session_start();

$HOD_PASSWORD   = 'hod123';
$STAFF_PASSWORD = 'staff123';

$view  = $_GET['view'] ?? 'landing';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'hod_login') {
        $password = $_POST['hod_password'] ?? '';
        if ($password === $HOD_PASSWORD) {
            $_SESSION['role']     = 'HOD';
            $_SESSION['username'] = 'HOD';
            header('Location: hod_dashboard.php');
            exit;
        } else {
            $error = 'Invalid HOD password.';
            $view  = 'hod';
        }
    } elseif ($formType === 'staff_login') {
        $staffName = $_POST['staff_name'] ?? '';
        $password  = $_POST['staff_password'] ?? '';
        if ($staffName && $password === $STAFF_PASSWORD) {
            $_SESSION['role']       = 'Staff';
            $_SESSION['staff_name'] = $staffName;
            header('Location: staff_dashboard.php');
            exit;
        } else {
            $error = 'Invalid staff password or name.';
            $view  = 'staff';
        }
    } elseif ($formType === 'student_login') {
        $regNo = trim($_POST['reg_no'] ?? '');
        if ($regNo !== '') {
            $_SESSION['role']            = 'Student';
            $_SESSION['student_reg_no']  = $regNo;
            header('Location: student_dashboard.php');
            exit;
        } else {
            $error = 'Please enter registration number.';
            $view  = 'student';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Computer Application</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="landing.css">
</head>
<body class="page-landing">
    <div class="bg-futuristic">
        <div class="bg-gradient-animated"></div>
        <div class="bg-glowing-circles">
            <span class="circle c1"></span>
            <span class="circle c2"></span>
            <span class="circle c3"></span>
            <span class="circle c4"></span>
            <span class="circle c5"></span>
        </div>
        <div class="bg-blur-shapes">
            <span class="shape s1"></span>
            <span class="shape s2"></span>
            <span class="shape s3"></span>
        </div>
        <div class="bg-particles" id="particles"></div>
    </div>

    <div class="landing-logo">
        <img src="vignan-logo.png" alt="Vignan's Foundation for Science, Technology & Research" class="landing-logo-img">
    </div>

    <main class="landing-main">
        <h1 class="title-main">
            <span class="title-text">Department of Computer Application</span>
        </h1>

        <div class="marquee-wrap">
            <div class="marquee-inner">
                <span class="marquee-text">Welcome to the Department of Computer Application – VFSTR</span>
                <span class="marquee-text" aria-hidden="true">Welcome to the Department of Computer Application – VFSTR</span>
            </div>
        </div>

        <!-- Role buttons: HOD | Staff | Student (Upload Files removed) -->
        <div class="btn-group-center">
            <a href="?view=hod" class="btn-glass <?php echo $view === 'hod' ? 'active' : ''; ?>" data-role="hod">
                <span class="btn-glass-inner">
                    <span class="btn-label">HOD</span>
                    <span class="btn-sub">Login</span>
                </span>
                <span class="btn-ripple"></span>
            </a>
            <a href="?view=staff" class="btn-glass <?php echo $view === 'staff' ? 'active' : ''; ?>" data-role="staff">
                <span class="btn-glass-inner">
                    <span class="btn-label">Staff</span>
                    <span class="btn-sub">Login</span>
                </span>
                <span class="btn-ripple"></span>
            </a>
            <a href="?view=student" class="btn-glass <?php echo $view === 'student' ? 'active' : ''; ?>" data-role="student">
                <span class="btn-glass-inner">
                    <span class="btn-label">Student</span>
                    <span class="btn-sub">Login</span>
                </span>
                <span class="btn-ripple"></span>
            </a>
        </div>

        <?php if ($view === 'hod' || $view === 'staff' || $view === 'student'): ?>
        <div class="login-card-glass">
            <?php if ($error): ?>
                <div class="message error landing-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($view === 'hod'): ?>
                <form method="post" class="login-form role-form">
                    <input type="hidden" name="form_type" value="hod_login">
                    <div class="form-group">
                        <label for="hod_password">HOD Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="hod_password" name="hod_password" required placeholder="Enter HOD password">
                            <button type="button" class="password-toggle" aria-label="Show password"></button>
                        </div>
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
                            <option value="Dr. K. Gayatri">Dr. K. Gayatri</option>
                            <option value="Dr. K. Santhi Sri">Dr. K. Santhi Sri</option>
                            <option value="Dr. M. Srikanth Yadav">Dr. M. Srikanth Yadav</option>
                            <option value="Dr. N. Veeranjaneyulu">Dr. N. Veeranjaneyulu</option>
                            <option value="Dr. R.S. Padma Priya">Dr. R.S. Padma Priya</option>
                            <option value="Dr. Siva Koteswararao Chinnam">Dr. Siva Koteswararao Chinnam</option>
                            <option value="Mrs. R. Swathika">Mrs. R. Swathika</option>
                            <option value="R. Naga Sirisha">R. Naga Sirisha</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="staff_password">Staff Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="staff_password" name="staff_password" required placeholder="Enter staff password">
                            <button type="button" class="password-toggle" aria-label="Show password"></button>
                        </div>
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
        <?php endif; ?>

        <section class="landing-stats" aria-label="Department highlights">
            <div class="landing-stats-inner">
                <div class="stat-item">
                    <div class="stat-value">60+</div>
                    <div class="stat-label">Programs</div>
                </div>
                <div class="stat-divider" aria-hidden="true"></div>
                <div class="stat-item">
                    <div class="stat-value">25+</div>
                    <div class="stat-label">Faculty</div>
                </div>
                <div class="stat-divider" aria-hidden="true"></div>
                <div class="stat-item">
                    <div class="stat-value">333+</div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-divider" aria-hidden="true"></div>
                <div class="stat-item">
                    <div class="stat-value">85%</div>
                    <div class="stat-label">Placements</div>
                </div>
            </div>
            <div class="landing-stats-orbs" aria-hidden="true">
                <span class="orb o1"></span>
                <span class="orb o2"></span>
                <span class="orb o3"></span>
            </div>
        </section>
    </main>

    <script src="landing.js"></script>
</body>
</html>
