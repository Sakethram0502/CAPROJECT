<?php
session_start();
$username = $_SESSION['username'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Project Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background-overlay"></div>
    <div class="water-animation"></div>

    <div class="dashboard-wrapper">
        <header class="top-nav">
            <div class="top-nav-left">
                <span class="brand-title">Department of Computer Applications</span>
            </div>
            <div class="top-nav-right">
                <span class="welcome-text">Welcome <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <main class="dashboard-main centered">
            <div class="glass-panel floating" style="max-width: 720px;">
                <div class="app-header">
                    <h1>Project Title Submission</h1>
                    <h3>Department of Computer Applications</h3>
                </div>

                <form method="post" action="thankyou.php" class="form-glass">
                    <div class="form-group">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="e.g. BCA101">
                    </div>

                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>

                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" required>
                            <option value=\"\">-- Select Branch --</option>
                            <option value=\"BCA\">BCA</option>
                            <option value=\"MCA\">MCA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" required>
                            <option value=\"\">-- Select Year --</option>
                            <option value=\"1st Year\">1st Year</option>
                            <option value=\"2nd Year\">2nd Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" required>
                            <option value=\"\">-- Select Section --</option>
                            <option value=\"A\">A</option>
                            <option value=\"B\">B</option>
                            <option value=\"C\">C</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project_title">Project Title</label>
                        <input type="text" id="project_title" name="project_title" required>
                    </div>

                    <div class="form-group">
                        <label for="guide_name">Guide Name</label>
                        <select id="guide_name" name="guide_name" required>
                            <option value=\"\">-- Select Guide --</option>
                            <option value=\"Rama\">Rama</option>
                            <option value=\"Sita\">Sita</option>
                            <option value=\"Chandu\">Chandu</option>
                            <option value=\"Mahesh\">Mahesh</option>
                            <option value=\"Dhamu\">Dhamu</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-gradient">Submit Project Title</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

