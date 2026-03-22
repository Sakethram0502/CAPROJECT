<?php
include('db.php');
session_start();

$is_duplicate = false;
$success = false;
$student_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['initial_submit'])) {
    $reg_no = $_POST['reg_no'];
    $year = $_POST['year'];

    $check_sql = "SELECT * FROM student_submissions WHERE reg_no = ? AND year = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $reg_no, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $is_duplicate = true;
        $student_data = $result->fetch_assoc();
    } else {
        $semester = $_POST['semester'] ?? '';
        $sql = "INSERT INTO student_submissions (reg_no, student_name, branch, year, section, semester, domain, project_title, guide_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins = $conn->prepare($sql);
        $ins->bind_param("sssssssss", $reg_no, $_POST['student_name'], $_POST['branch'], $year, $_POST['section'], $semester, $_POST['domain'], $_POST['project_title'], $_POST['guide_name']);
        if ($ins->execute()) { $success = true; }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['do_full_update'])) {
    $upd = $conn->prepare("UPDATE student_submissions SET student_name=?, branch=?, section=?, semester=?, domain=?, project_title=?, guide_name=? WHERE reg_no=? AND year=?");
    $upd->bind_param("sssssssss", $_POST['student_name'], $_POST['branch'], $_POST['section'], $_POST['semester'], $_POST['domain'], $_POST['project_title'], $_POST['guide_name'], $_POST['reg_hidden'], $_POST['year_hidden']);
    if ($upd->execute()) {
        header("Location: thankyou.php?updated=true");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Status | VFSTR</title>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="page-wrapper">
        <div class="glass-panel floating" style="max-width: 600px; padding: 30px;">

            <?php if (isset($_GET['updated']) || $success): ?>
                <div class="status-hero">
                    <h1 class="status-hero-title">Project Recorded!</h1>
                    <p class="status-hero-subtitle">Your details have been successfully saved to the database.</p>
                    <a href="student_dashboard.php" class="btn-gradient" style="display:inline-block; margin-top:24px; text-decoration:none;">Back to Dashboard</a>
                </div>

            <?php elseif ($is_duplicate): ?>
                <div style="text-align: center;">
                    <h1 style="color: #ffcc00;">Existing Record Found</h1>
                    <p>You can update your current project information below.</p>
                </div>

                <form method="POST" class="form-glass">
                    <input type="hidden" name="reg_hidden" value="<?php echo $student_data['reg_no']; ?>">
                    <input type="hidden" name="year_hidden" value="<?php echo $student_data['year']; ?>">
                    
                    <div class="form-group">
                        <label>Student Name</label>
                        <input type="text" name="student_name" value="<?php echo htmlspecialchars($student_data['student_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Project Domain</label>
                        <input type="text" name="domain" value="<?php echo htmlspecialchars($student_data['domain']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" value="<?php echo htmlspecialchars($student_data['project_title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="">-- Select Semester --</option>
                            <?php foreach (['I' => 'Semester I', 'II' => 'Semester II'] as $val => $label):
                                $sel = (isset($student_data['semester']) && (string)$student_data['semester'] === $val) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Guide</label>
                        <select name="guide_name" required>
                            <?php 
                            $guides = ["Dr. K. Santhi Sri", "Gayatri", "Naga Sirisha", "Koteswarao", "Mahesh", ""];
                            foreach($guides as $g) {
                                $sel = ($student_data['guide_name'] == $g) ? 'selected' : '';
                                echo "<option value='$g' $sel>$g</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="do_full_update" class="btn-gradient" style="width: 100%;">Save Corrected Info</button>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="student_dashboard.php" style="color: #aaa; text-decoration: none;">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>