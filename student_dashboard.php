<?php
session_start();
include('db.php');

if (!isset($_SESSION['student_reg_no'])) {
    header('Location: index.php?view=student');
    exit;
}

$regNo = $_SESSION['student_reg_no'];
$username = $regNo;
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
            <div class="top-nav-left"><span class="brand-title">Department of Computer Applications - VFSTR</span></div>
            <div class="top-nav-right">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <main class="dashboard-main centered">
            <!-- Existing project submission card kept as-is -->
            <div class="glass-panel floating">
                <div class="app-header">
                    <h1>Project Submission</h1>
                    <p>Enter your project details for review registration.</p>
                </div>

                <form method="post" action="thankyou.php" class="form-glass">
                    <div class="form-group">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="e.g. 231FA04000">
                    </div>

                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" required placeholder="Your Full Name">
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="branch">Branch</label>
                            <select id="branch" name="branch" required onchange="updateOptions()">
                                <option value="">-- Select --</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="year">Year</label>
                            <select id="year" name="year" required onchange="updateOptions()">
                                <option value="">-- Select --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" required>
                            <option value="">-- Select Section --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="domain">Project Domain</label>
                        <input type="text" id="domain" name="domain" required placeholder="e.g. Web Development, AI, Cyber Security">
                    </div>

                    <div class="form-group">
                        <label for="project_title">Project Title</label>
                        <input type="text" id="project_title" name="project_title" required placeholder="Full Project Name">
                    </div>

                    <div class="form-group">
                        <label for="guide_name">Supervisor / Guide Name</label>
                        <select id="guide_name" name="guide_name" required>
                            <option value="">-- Select Guide --</option>
                            <option value="Dr. K. Santhi Sri">Dr. K. Santhi Sri</option>
                            <option value="Rama">Rama</option>
                            <option value="Sita">Sita</option>
                            <option value="Chandu">Chandu</option>
                            <option value="Mahesh">Mahesh</option>
                            <option value="Dhamu">Dhamu</option>
                        </select>
                    </div>

                    <button type="submit" name="initial_submit" class="btn-gradient">Submit Project Details</button>
                </form>
            </div>

            <!-- New: Student File Upload section -->
            <div class="glass-panel" style="margin-top: 30px;">
                <div class="app-header">
                    <h2>Student File Upload</h2>
                    <h3>Your registration number is your login ID.</h3>
                </div>

                <form method="post" action="student_upload.php" class="form-glass" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="upload_reg_no">Registration Number</label>
                        <input
                            type="text"
                            id="upload_reg_no"
                            name="reg_no"
                            required
                            value="<?php echo htmlspecialchars($regNo); ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label for="doc_file">Document Upload (Any format)</label>
                        <input type="file" id="doc_file" name="doc_file">
                    </div>

                    <div class="form-group">
                        <label for="ppt_file">PPT Upload (Any format)</label>
                        <input type="file" id="ppt_file" name="ppt_file">
                    </div>

                    <div class="form-group">
                        <label for="code_file">Code Upload (Any format)</label>
                        <input type="file" id="code_file" name="code_file">
                    </div>

                    <button type="submit" class="btn-gradient">Upload Files</button>
                </form>
            </div>
        </main>
    </div>

    <script>
    function updateOptions() {
        const branch = document.getElementById('branch').value;
        const yearSelect = document.getElementById('year');
        const sectionSelect = document.getElementById('section');
        const selectedYear = yearSelect.value;

        yearSelect.innerHTML = '<option value=\"\">-- Select --</option>';
        if (branch === 'BCA') {
            ['1st Year', '2nd Year', '3rd Year'].forEach(y => yearSelect.add(new Option(y, y)));
        } else if (branch === 'MCA') {
            ['1st Year', '2nd Year'].forEach(y => yearSelect.add(new Option(y, y)));
        }
        yearSelect.value = selectedYear;

        sectionSelect.innerHTML = '<option value=\"\">-- Select Section --</option>';
        if (branch === 'BCA') {
            sectionSelect.add(new Option('A', 'A'));
        } else if (branch === 'MCA') {
            if (yearSelect.value === '1st Year') {
                sectionSelect.add(new Option('A', 'A'));
            } else if (yearSelect.value === '2nd Year') {
                ['A', 'B'].forEach(s => sectionSelect.add(new Option(s, s)));
            }
        }
    }
    </script>
</body>
</html>