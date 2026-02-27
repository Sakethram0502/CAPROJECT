<?php
include('db.php');
session_start();

$is_duplicate = false;
$success = false;
$student_data = null;

// PART A: HANDLE INITIAL FORM SUBMISSION FROM DASHBOARD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['initial_submit'])) {
    $reg_no = $_POST['reg_no'];
    $year = $_POST['year'];

    // CHECK IF EXISTS
    $check_sql = "SELECT * FROM student_submissions WHERE reg_no = ? AND year = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $reg_no, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $is_duplicate = true;
        $student_data = $result->fetch_assoc();
    } else {
        // NEW INSERT
        $sql = "INSERT INTO student_submissions (reg_no, student_name, branch, year, section, project_title, guide_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $ins = $conn->prepare($sql);
        $ins->bind_param("sssssss", $reg_no, $_POST['student_name'], $_POST['branch'], $year, $_POST['section'], $_POST['project_title'], $_POST['guide_name']);
        if ($ins->execute()) {
            $success = true;
        }
    }
}

// PART B: HANDLE UPDATE REQUEST FROM THIS PAGE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['do_update'])) {
    $new_title = $_POST['new_title'];
    $reg = $_POST['reg_hidden'];
    $yr = $_POST['year_hidden'];

    $upd = $conn->prepare("UPDATE student_submissions SET project_title = ? WHERE reg_no = ? AND year = ?");
    $upd->bind_param("sss", $new_title, $reg, $yr);
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
    <title>Status | Project Management</title>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="dashboard-wrapper">
        <div class="glass-panel centered" style="max-width: 500px; padding: 40px; text-align: center;">

            <?php if (isset($_GET['updated']) || $success): ?>
                <h1 style="color: #00ffcc;">Success!</h1>
                <p>Your project information has been safely recorded.</p>
                <a href="student_dashboard.php" class="btn-gradient" style="text-decoration:none; display:inline-block; margin-top:20px;">Back to Home</a>

            <?php elseif ($is_duplicate): ?>
                <h1 style="color: #ffcc00;">Record Exists</h1>
                <p>Registration <strong><?php echo htmlspecialchars($student_data['reg_no']); ?></strong> has already submitted a project for <strong><?php echo htmlspecialchars($student_data['year']); ?></strong>.</p>
                
                <div style="margin: 20px 0; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; text-align: left;">
                    <p style="margin:0; font-size: 0.9em; color: #aaa;">Current Title:</p>
                    <p style="margin:5px 0 0 0; color: #fff; font-weight: bold;"><?php echo htmlspecialchars($student_data['project_title']); ?></p>
                </div>

                <form method="POST" class="form-glass">
                    <input type="hidden" name="reg_hidden" value="<?php echo $student_data['reg_no']; ?>">
                    <input type="hidden" name="year_hidden" value="<?php echo $student_data['year']; ?>">
                    
                    <div class="form-group" style="text-align: left;">
                        <label>Update Project Title Only</label>
                        <input type="text" name="new_title" required placeholder="Enter new project name...">
                    </div>
                    <button type="submit" name="do_update" class="btn-gradient" style="width: 100%;">Update My Project</button>
                    <a href="student_dashboard.php" style="display:block; margin-top:15px; color:#aaa; text-decoration:none;">Cancel and Go Back</a>
                </form>

            <?php else: ?>
                <h1>Error</h1>
                <p>Something went wrong with the submission.</p>
                <a href="student_dashboard.php" class="btn-gradient">Try Again</a>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>