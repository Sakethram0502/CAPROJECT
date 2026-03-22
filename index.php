<?php
session_start();

$HOD_PASSWORD = 'hod123';
$STAFF_PASSWORD = 'staff123';

$view = $_GET['view'] ?? 'landing';
$error = '';
$uploadFlash = $_SESSION['upload_flash'] ?? '';
$uploadMeta = $_SESSION['upload_last_meta'] ?? null;
if ($uploadFlash !== '') {
    unset($_SESSION['upload_flash']);
}
if ($uploadMeta !== null) {
    unset($_SESSION['upload_last_meta']);
}

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
    <title>Department of Computer Application</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="landing.css">
</head>
<body class="page-landing">
    <!-- Futuristic animated background -->
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

    <!-- Logo: top left, fixed (not scrolling) -->
    <div class="landing-logo">
        <img src="logo.png" alt="Vignan's Foundation for Science, Technology & Research" class="landing-logo-img">
    </div>

    <main class="landing-main">
        <!-- 1. Top center title -->
        <h1 class="title-main">
            <span class="title-text">Department of Computer Application</span>
        </h1>

        <!-- 2. Scrolling marquee -->
        <div class="marquee-wrap">
            <div class="marquee-inner">
                <span class="marquee-text">Welcome to the Department of Computer Application – VFSTR</span>
                <span class="marquee-text" aria-hidden="true">Welcome to the Department of Computer Application – VFSTR</span>
            </div>
        </div>

        <!-- 3. Role buttons + Upload Files (beside Student) -->
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
            <div class="btn-student-upload-pair">
                <a href="?view=student" class="btn-glass <?php echo $view === 'student' ? 'active' : ''; ?>" data-role="student">
                    <span class="btn-glass-inner">
                        <span class="btn-label">Student</span>
                        <span class="btn-sub">Login</span>
                    </span>
                    <span class="btn-ripple"></span>
                </a>
                <a href="?view=upload" class="btn-glass btn-upload-files <?php echo $view === 'upload' ? 'active' : ''; ?>" data-role="upload">
                    <span class="btn-glass-inner">
                        <span class="btn-label">Upload</span>
                        <span class="btn-sub">Files</span>
                    </span>
                    <span class="btn-ripple"></span>
                </a>
            </div>
        </div>
        <!-- Login form card (shows when HOD / Staff / Student selected) -->
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
                            <option value="Rama">Rama</option>
                            <option value="Sita">Sita</option>
                            <option value="Chandu">Chandu</option>
                            <option value="Mahesh">Mahesh</option>
                            <option value="Dhamu">Dhamu</option>
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

        <!-- Student file upload (main page): branch → year → section (MCA) → semester → files -->
        <?php if ($view === 'upload'): ?>
        <div class="login-card-glass upload-card-wide">
            <h2 class="upload-card-title">Student File Upload</h2>
            <p class="upload-card-hint">Select branch, year, section (MCA), semester, then your registration number and files.</p>

            <?php if ($uploadFlash): ?>
                <div class="message <?php echo (isset($_GET['upload']) && $_GET['upload'] === 'success') ? 'success' : 'error'; ?> landing-msg">
                    <?php echo htmlspecialchars($uploadFlash); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($uploadMeta) && isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class="upload-summary-box">
                    <strong>Last upload details</strong>
                    <ul>
                        <li>Branch: <?php echo htmlspecialchars($uploadMeta['branch'] ?? ''); ?></li>
                        <li>Year: <?php echo htmlspecialchars($uploadMeta['year'] ?? ''); ?></li>
                        <?php if (!empty($uploadMeta['section'])): ?>
                            <li>Section: <?php echo htmlspecialchars($uploadMeta['section']); ?></li>
                        <?php endif; ?>
                        <li>Semester: <?php echo htmlspecialchars($uploadMeta['semester'] ?? ''); ?></li>
                        <li>Reg. No.: <?php echo htmlspecialchars($uploadMeta['reg_no'] ?? ''); ?></li>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="student_upload.php" class="login-form role-form upload-cascade-form" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="return_to" value="index">

                <div class="form-group">
                    <label for="upload_branch">Branch</label>
                    <select id="upload_branch" name="branch" required>
                        <option value="">-- Select Branch --</option>
                        <option value="BCA">BCA</option>
                        <option value="MCA">MCA</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="upload_year">Year</label>
                    <select id="upload_year" name="academic_year" required disabled>
                        <option value="">-- Select branch first --</option>
                    </select>
                </div>

                <div class="form-group upload-section-row" id="upload_section_wrap" style="display:none;">
                    <label for="upload_section">Section (MCA)</label>
                    <select id="upload_section" name="section">
                        <option value="">-- Select Section --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="upload_semester">Semester</label>
                    <select id="upload_semester" name="semester" required>
                        <option value="">-- Semester --</option>
                        <option value="I">Semester I</option>
                        <option value="II">Semester II</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="upload_reg_no">Registration Number</label>
                    <input type="text" id="upload_reg_no" name="reg_no" required placeholder="Your registration number"
                           value="<?php echo isset($_SESSION['student_reg_no']) ? htmlspecialchars($_SESSION['student_reg_no']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="doc_file">Document (PDF / DOC / any)</label>
                    <input type="file" id="doc_file" name="doc_file">
                </div>
                <div class="form-group">
                    <label for="ppt_file">PPT / slides</label>
                    <input type="file" id="ppt_file" name="ppt_file">
                </div>
                <div class="form-group">
                    <label for="code_file">Code / zip / project</label>
                    <input type="file" id="code_file" name="code_file">
                </div>

                <button type="submit" class="btn-gradient login-btn">Upload Files</button>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <script src="landing.js"></script>
    <?php if ($view === 'upload'): ?>
    <script>
    (function () {
        var branch = document.getElementById('upload_branch');
        var year = document.getElementById('upload_year');
        var sectionWrap = document.getElementById('upload_section_wrap');
        var section = document.getElementById('upload_section');
        if (!branch || !year) return;

        function refreshYear() {
            var b = branch.value;
            year.innerHTML = '';
            year.disabled = !b;
            if (!b) {
                year.add(new Option('-- Select branch first --', ''));
                return;
            }
            year.add(new Option('-- Select Year --', ''));
            if (b === 'BCA') {
                ['1st Year', '2nd Year', '3rd Year'].forEach(function (y) {
                    year.add(new Option(y, y));
                });
            } else if (b === 'MCA') {
                ['1st Year', '2nd Year'].forEach(function (y) {
                    year.add(new Option(y, y));
                });
            }
        }

        function refreshSection() {
            var b = branch.value;
            if (b === 'MCA') {
                sectionWrap.style.display = 'block';
                section.required = true;
            } else {
                sectionWrap.style.display = 'none';
                section.required = false;
                section.value = '';
            }
        }

        branch.addEventListener('change', function () {
            refreshYear();
            refreshSection();
        });
        refreshYear();
        refreshSection();
    })();
    </script>
    <?php endif; ?>
    </main>

    <script src="landing.js"></script>
</body>
</html>
