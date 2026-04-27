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

        $isValidRegNo = false;
        $studentTrack = '';
        if ($regNo !== '' && strlen($regNo) === 10 && ctype_alnum($regNo)) {
            $digitCount = preg_match_all('/\d/', $regNo);
            $letters    = strtolower(preg_replace('/[^a-z]/i', '', $regNo));

            if ($digitCount === 8 && strlen($letters) === 2) {
                $lettersArray = str_split($letters);
                sort($lettersArray);
                $normalizedLetters = implode('', $lettersArray);
                $isValidRegNo = in_array($normalizedLetters, ['fj', 'df'], true);
                if ($isValidRegNo) {
                    $studentTrack = $normalizedLetters;
                }
            }
        }

        if ($isValidRegNo) {
            $_SESSION['role']            = 'Student';
            $_SESSION['student_reg_no']  = $regNo;
            $_SESSION['student_track']   = $studentTrack; // 'fj' => BCA, 'df' => MCA
            header('Location: student_dashboard.php');
            exit;
        } else {
            $error = 'Invalid registration number. Use 10 characters: 8 digits + letters (FJ for BCA or FD for MCA) in any order.';
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
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3.2" fill="#E2B27B"/>
                            <path d="M5 19.2c.4-3.5 2.9-5.6 7-5.6s6.6 2.1 7 5.6c.1.7-.4 1.3-1.1 1.3H6.1c-.7 0-1.2-.6-1.1-1.3Z" fill="#E3515A"/>
                            <path d="m6.3 6.2 5.7-2.3 5.7 2.3-5.7 2.3-5.7-2.3Z" fill="#476087"/>
                            <rect x="11.4" y="8.5" width="1.2" height="2.2" rx=".4" fill="#C93E49"/>
                        </svg>
                    </span>
                    <span class="btn-label">HOD</span>
                    <span class="btn-sub">Login</span>
                </span>
                <span class="btn-ripple"></span>
            </a>
            <a href="?view=staff" class="btn-glass <?php echo $view === 'staff' ? 'active' : ''; ?>" data-role="staff">
                <span class="btn-glass-inner">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4.8" width="18" height="10.8" rx="1.2" fill="#6F98DB"/>
                            <rect x="4.3" y="6.1" width="15.4" height="8.2" rx=".7" fill="#E9F1FF"/>
                            <rect x="10.6" y="15.6" width="2.8" height="3.6" rx=".5" fill="#476087"/>
                            <rect x="7.5" y="19.1" width="9" height="1.4" rx=".6" fill="#476087"/>
                            <circle cx="8.2" cy="10.2" r="1.5" fill="#E2B27B"/>
                            <path d="M6.4 13.2c.2-1.1 1-1.8 2.3-1.8s2.1.7 2.3 1.8H6.4Z" fill="#59B35C"/>
                        </svg>
                    </span>
                    <span class="btn-label">Staff</span>
                    <span class="btn-sub">Login</span>
                </span>
                <span class="btn-ripple"></span>
            </a>
            <a href="?view=student" class="btn-glass <?php echo $view === 'student' ? 'active' : ''; ?>" data-role="student">
                <span class="btn-glass-inner">
                    <span class="btn-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m3 8.4 9-3.6 9 3.6-9 3.6-9-3.6Z" fill="#476087"/>
                            <path d="M6.1 10.2v3.1c0 1.7 2.6 3 5.9 3s5.9-1.3 5.9-3v-3.1" fill="#5C7AA3"/>
                            <circle cx="12" cy="12.2" r="3.2" fill="#E2B27B"/>
                            <path d="M7 20c.3-2.6 2.2-4.1 5-4.1s4.7 1.5 5 4.1H7Z" fill="#6F98DB"/>
                        </svg>
                    </span>
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
                        <input type="text" id="reg_no" name="reg_no" required maxlength="10" placeholder="Enter 10-char reg no (8 digits + FJ/FD)">
                    </div>
                    <button type="submit" class="btn-gradient login-btn">Continue as Student</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <section class="landing-stats" aria-label="Department highlights" style="margin-top: clamp(32px, 5vw, 56px);">
            <div class="landing-stats-inner">
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8.4" r="3.2" fill="#E2B27B"/>
                            <path d="M5.2 19c.3-3 2.5-4.8 6.8-4.8 4.2 0 6.5 1.8 6.8 4.8.1.6-.4 1.2-1 1.2H6.2c-.6 0-1.1-.6-1-1.2Z" fill="#59B35C"/>
                            <path d="m6.4 6.5 5.6-2.2 5.6 2.2-5.6 2.2-5.6-2.2Z" fill="#476087"/>
                        </svg>
                    </div>
                    <div class="stat-value">25+</div>
                    <div class="stat-label">Faculty</div>
                </div>
                <div class="stat-divider" aria-hidden="true"></div>
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m2.8 9 9.2-3.7L21.2 9 12 12.7 2.8 9Z" fill="#476087"/>
                            <circle cx="12" cy="12.3" r="3.3" fill="#E2B27B"/>
                            <path d="M5.5 20c.3-2.7 2.3-4.3 6.5-4.3s6.2 1.6 6.5 4.3H5.5Z" fill="#6F98DB"/>
                        </svg>
                    </div>
                    <div class="stat-value">300+</div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-divider" aria-hidden="true"></div>
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3.2" y="12.8" width="3.2" height="7" rx=".7" fill="#59B35C"/>
                            <rect x="8.2" y="10.3" width="3.2" height="9.5" rx=".7" fill="#6F98DB"/>
                            <rect x="13.2" y="7.8" width="3.2" height="12" rx=".7" fill="#476087"/>
                            <path d="m7 8.8 4.4-3.9 2.5 2 3.5-3.1" stroke="#F0B03E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16.7 3.9h3.1V7" stroke="#F0B03E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
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
