<?php
include('db.php');
session_start();

if (!isset($_SESSION['student_reg_no'])) {
    header('Location: index.php?view=student');
    exit;
}

$is_duplicate = false;
$success      = false;
$student_data = null;

$guides = [
    'Dr. K. Gayatri',
    'Dr. K. Santhi Sri',
    'Dr. M. Srikanth Yadav',
    'Dr. N. Veeranjaneyulu',
    'Dr. R.S. Padma Priya',
    'Dr. Siva Koteswararao Chinnam',
    'Mrs. R. Swathika',
    'R. Naga Sirisha',
];

// ── INITIAL SUBMIT: check duplicate by reg_no + year + semester ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initial_submit'])) {
    $reg_no   = trim($_POST['reg_no']);
    $year     = trim($_POST['year']);       // "1" or "2"
    $semester = trim($_POST['semester']);   // "I" or "II"

    // Duplicate check: same student, same year, same semester
    $chk = $conn->prepare("SELECT * FROM student_submissions WHERE reg_no=? AND year=? AND semester=?");
    $chk->bind_param("sss", $reg_no, $year, $semester);
    $chk->execute();
    $res = $chk->get_result();

    if ($res->num_rows > 0) {
        $is_duplicate = true;
        $student_data = $res->fetch_assoc();
    } else {
        // New record
        $ins = $conn->prepare(
            "INSERT INTO student_submissions
                (reg_no, student_name, branch, year, section, semester, domain, project_title, guide_name)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $ins->bind_param("sssssssss",
            $reg_no,
            $_POST['student_name'],
            $_POST['branch'],
            $year,
            $_POST['section'],
            $semester,
            $_POST['domain'],
            $_POST['project_title'],
            $_POST['guide_name']
        );
        if ($ins->execute()) { $success = true; }
    }
}

// ── UPDATE existing record ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_full_update'])) {
    $upd = $conn->prepare(
        "UPDATE student_submissions
         SET student_name=?, branch=?, section=?, domain=?, project_title=?, guide_name=?
         WHERE reg_no=? AND year=? AND semester=?"
    );
    $upd->bind_param("sssssssss",
        $_POST['student_name'],
        $_POST['branch'],
        $_POST['section'],
        $_POST['domain'],
        $_POST['project_title'],
        $_POST['guide_name'],
        $_POST['reg_hidden'],
        $_POST['year_hidden'],
        $_POST['sem_hidden']
    );
    if ($upd->execute()) {
        header("Location: thankyou.php?updated=true");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Status | VFSTR</title>
    <style>
        .field-row { display:flex; gap:14px; }
        .field-row .form-group { flex:1; }
    </style>
</head>
<body>
<div class="background-overlay"></div>
<div class="page-wrapper">
    <div class="glass-panel floating" style="max-width:640px;padding:32px;">

        <?php if (isset($_GET['updated']) || $success): ?>
        <!-- ── SUCCESS ── -->
        <div style="text-align:center;">
            <h1 style="color:#00ff88;">
                <?php echo isset($_GET['updated']) ? '✓ Details Updated!' : '✓ Project Recorded!'; ?>
            </h1>
            <p style="color:#aaa;">Your project details have been saved successfully.</p>
            <a href="student_dashboard.php" class="btn-gradient"
               style="display:inline-block;margin-top:24px;text-decoration:none;">
                Back to Dashboard
            </a>
        </div>

        <?php elseif ($is_duplicate): ?>
        <!-- ── DUPLICATE — UPDATE FORM ── -->
        <div style="text-align:center;margin-bottom:22px;">
            <h2 style="color:#ffcc00;">⚠️ Record Already Exists</h2>
            <p style="color:#aaa;font-size:0.88em;">
                You already submitted for
                <strong style="color:#fff;">
                    Year <?php echo htmlspecialchars($student_data['year']); ?> —
                    Semester <?php echo htmlspecialchars($student_data['semester']); ?>
                </strong>.
                Update your details below.
            </p>
        </div>

        <form method="POST" class="form-glass">
            <input type="hidden" name="do_full_update" value="1">
            <input type="hidden" name="reg_hidden"  value="<?php echo htmlspecialchars($student_data['reg_no']); ?>">
            <input type="hidden" name="year_hidden" value="<?php echo htmlspecialchars($student_data['year']); ?>">
            <input type="hidden" name="sem_hidden"  value="<?php echo htmlspecialchars($student_data['semester']); ?>">

            <!-- Reg No readonly -->
            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" value="<?php echo htmlspecialchars($student_data['reg_no']); ?>" readonly style="opacity:0.7;">
            </div>

            <!-- Student Name -->
            <div class="form-group">
                <label>Student Name</label>
                <input type="text" name="student_name"
                       value="<?php echo htmlspecialchars($student_data['student_name']); ?>" required>
            </div>

            <!-- Branch + Year + Section -->
            <div class="field-row">
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch" required>
                        <option value="BCA" <?php echo $student_data['branch']==='BCA'?'selected':''; ?>>BCA</option>
                        <option value="MCA" <?php echo $student_data['branch']==='MCA'?'selected':''; ?>>MCA</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Section</label>
                    <select name="section" required>
                        <?php foreach (['A','B'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $student_data['section']===$s?'selected':''; ?>>
                            Section <?php echo $s; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Year & Semester readonly -->
            <div class="field-row">
                <div class="form-group">
                    <label>Year</label>
                    <input type="text" value="Year <?php echo htmlspecialchars($student_data['year']); ?>" readonly style="opacity:0.7;">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" value="Semester <?php echo htmlspecialchars($student_data['semester']); ?>" readonly style="opacity:0.7;">
                </div>
            </div>

            <!-- Domain -->
            <div class="form-group">
                <label>Project Domain</label>
                <input type="text" name="domain"
                       value="<?php echo htmlspecialchars($student_data['domain'] ?? ''); ?>" required>
            </div>

            <!-- Project Title -->
            <div class="form-group">
                <label>Project Title</label>
                <input type="text" name="project_title"
                       value="<?php echo htmlspecialchars($student_data['project_title']); ?>" required>
            </div>

            <!-- Guide -->
            <div class="form-group">
                <label>Project Guide</label>
                <select name="guide_name" required>
                    <option value="">-- Select Guide --</option>
                    <?php foreach ($guides as $g): ?>
                    <option value="<?php echo $g; ?>"
                        <?php echo $student_data['guide_name']===$g?'selected':''; ?>>
                        <?php echo $g; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-gradient" style="width:100%;">Save Updated Details</button>
            <div style="text-align:center;margin-top:14px;">
                <a href="student_dashboard.php" style="color:#aaa;font-size:0.87em;text-decoration:none;">Cancel</a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>
</body>
</html>