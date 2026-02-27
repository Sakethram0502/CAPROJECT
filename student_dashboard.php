<?php
session_start();
$username = $_SESSION['username'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background-overlay"></div>
    <div class="water-animation"></div>

    <div class="dashboard-wrapper">
        <header class="top-nav">
            <div class="top-nav-left"><span class="brand-title">Department of Computer Applications</span></div>
            <div class="top-nav-right">
                <span class="welcome-text">Welcome <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <main class="dashboard-main centered">
            <div class="glass-panel floating">
                <div class="app-header">
                    <h1>Project Submission</h1>
                    <h3>Department of Computer Applications</h3>
                </div>

                <form method="post" action="thankyou.php" class="form-glass">
                    <div class="form-group">
                        <label for="reg_no">Registration Number</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="e.g. 241FD...">
                    </div>

                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>

                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <select id="branch" name="branch" required onchange="updateOptions()">
                            <option value="">-- Select Branch --</option>
                            <option value="BCA">BCA</option>
                            <option value="MCA">MCA</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" required onchange="updateOptions()">
                            <option value="">-- Select Year --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <select id="section" name="section" required>
                            <option value="">-- Select Section --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project_title">Project Title</label>
                        <input type="text" id="project_title" name="project_title" required>
                    </div>

                    <div class="form-group">
                        <label for="guide_name">Guide Name</label>
                        <select id="guide_name" name="guide_name" required>
                            <option value="">-- Select Guide --</option>
                            <option value="Rama">Rama</option>
                            <option value="Sita">Sita</option>
                            <option value="Chandu">Chandu</option>
                            <option value="Mahesh">Mahesh</option>
                            <option value="Dhamu">Dhamu</option>
                        </select>
                    </div>

                    <button type="submit" name="initial_submit" class="btn-gradient">Submit Project Title</button>
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

        yearSelect.innerHTML = '<option value="">-- Select Year --</option>';
        if (branch === 'BCA') {
            ['1st Year', '2nd Year', '3rd Year'].forEach(y => yearSelect.add(new Option(y, y)));
        } else if (branch === 'MCA') {
            ['1st Year', '2nd Year'].forEach(y => yearSelect.add(new Option(y, y)));
        }
        yearSelect.value = selectedYear;

        sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
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