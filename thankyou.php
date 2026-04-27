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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initial_submit'])) {
    $reg_no   = trim($_POST['reg_no']);
    $year     = trim($_POST['year']);
    $semester = trim($_POST['semester']);

<<<<<<< Updated upstream
    $chk = $conn->prepare("SELECT * FROM student_submissions WHERE reg_no=? AND year=? AND semester=?");
    $chk->bind_param("sss", $reg_no, $year, $semester);
    $chk->execute();
    $res = $chk->get_result();
=======
// Enforce branch/year from registration series:
// FJ => BCA (1,2,3), FD => MCA (1,2)
$letters = strtolower(preg_replace('/[^a-z]/i', '', $regNo));
$lettersArray = str_split($letters);
sort($lettersArray);
$track = implode('', $lettersArray);
$expectedBranch = ($track === 'fj') ? 'BCA' : 'MCA';
$allowedYears = ($expectedBranch === 'BCA') ? ['1', '2', '3'] : ['1', '2'];

// Validation
if ($regNo === '' || $studentName === '' || $branch === '' || $year === '' || $semester === '' ||
    $section === '' || $domain === '' || $projectTitle === '' || $guideName === '') {
    flash_redirect('warn', '⚠️ Please fill in all required fields.');
}
if ($branch !== $expectedBranch || !in_array($year, $allowedYears, true)) {
    flash_redirect('warn', '⚠️ Invalid branch/year for this registration number.');
}
>>>>>>> Stashed changes

    if ($res->num_rows > 0) {
        $is_duplicate = true;
        $student_data = $res->fetch_assoc();
    } else {
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
    <title>Status | CA Dept</title>
    <style>
        .field-row { display:flex; gap:14px; }
        .field-row .form-group { flex:1; }
        .success-icon {
            width: 64px; height: 64px;
            background: var(--green-pale); border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 18px;
            box-shadow: 0 8px 24px rgba(13,74,69,0.25);
        }
        .warn-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 18px;
            box-shadow: 0 8px 24px rgba(201,146,42,0.25);
        }
    </style>
</head>
<body>
<div class="background-overlay"></div>
<div class="page-wrapper">
    <div class="glass-panel" style="max-width:640px;padding:36px;">

        <?php if (isset($_GET['updated']) || $success): ?>
        <!-- SUCCESS -->
        <div style="text-align:center;">
            <div class="success-icon">✓</div>
            <h1 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--green-dark);margin-bottom:8px;">
                <?php echo isset($_GET['updated']) ? 'Details Updated!' : 'Project Recorded!'; ?>
            </h1>
            <p style="color:rgba(255,255,255,0.65);font-size:0.9rem;margin-bottom:28px;">
                Your project details have been saved successfully.
            </p>
            <a href="student_dashboard.php" class="btn-gradient">
                ← Back to Dashboard
            </a>
        </div>

        <?php elseif ($is_duplicate): ?>
        <!-- DUPLICATE — UPDATE FORM -->
        <div style="text-align:center;margin-bottom:26px;">
            <div class="warn-icon">⚠</div>
            <h2 style="font-family:'Playfair Display',serif;font-size:1.35rem;color:var(--green-dark);margin-bottom:8px;text-shadow:0 0 16px rgba(0,212,255,0.4);">
                Record Already Exists
            </h2>
            <p style="color:rgba(255,255,255,0.65);font-size:0.88rem;">
                You already submitted for
                <strong style="color:var(--green-dark);">
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

            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" value="<?php echo htmlspecialchars($student_data['reg_no']); ?>" readonly style="opacity:0.65;background:rgba(0,0,0,0.35);">
            </div>

            <div class="form-group">
                <label>Student Name</label>
                <input type="text" name="student_name"
                       value="<?php echo htmlspecialchars($student_data['student_name']); ?>" required>
            </div>

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

            <div class="field-row">
                <div class="form-group">
                    <label>Year</label>
                    <input type="text" value="Year <?php echo htmlspecialchars($student_data['year']); ?>" readonly style="opacity:0.65;background:rgba(0,0,0,0.35);">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" value="Semester <?php echo htmlspecialchars($student_data['semester']); ?>" readonly style="opacity:0.65;background:rgba(0,0,0,0.35);">
                </div>
            </div>

            <div class="form-group">
                <label>Project Domain</label>
                <input type="text" name="domain"
                       value="<?php echo htmlspecialchars($student_data['domain'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Project Title</label>
                <input type="text" name="project_title"
                       value="<?php echo htmlspecialchars($student_data['project_title']); ?>" required>
            </div>

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

            <button type="submit" class="btn-gradient" style="width:100%;margin-top:8px;">Save Updated Details</button>
            <div style="text-align:center;margin-top:14px;">
                <a href="student_dashboard.php" style="color:rgba(255,255,255,0.65);font-size:0.87em;text-decoration:none;transition:color 0.14s;"
                   onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Cancel, go back
                </a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>
</body>
</html>
