<?php
session_start();
include('db.php');
$username = $_SESSION['username'] ?? 'HOD';
$view     = $_GET['view'] ?? 'overview';
$batchYearSelected = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$batchDecadeStart  = isset($_GET['decade']) ? (int)$_GET['decade'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | Project Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* All styles handled by style.css (warm academic theme) */
        .year-picker-wrap { max-width: 520px; }
        .year-picker-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 16px;
            padding: 16px;
        }
        .year-picker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .year-nav-btn {
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.05);
            color: var(--text-dark);
            border-radius: 10px;
            width: 34px;
            height: 34px;
            line-height: 32px;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
        }
        .year-picker-title {
            font-weight: 600;
            color: var(--text-dark);
            letter-spacing: 0.3px;
        }
        .year-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(80px, 1fr));
            gap: 10px;
        }
        .year-chip {
            display: block;
            text-align: center;
            padding: 10px 8px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-dark);
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.04);
            font-weight: 500;
        }
        .year-chip.active {
            background: linear-gradient(135deg, rgba(13,74,69,0.92), rgba(11,105,93,0.92));
            border-color: rgba(13,74,69,0.95);
            color: #fff;
        }
        .year-chip.muted {
            opacity: 0.38;
            pointer-events: none;
        }
    </style>
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
                <span class="welcome-text">Welcome, HOD</span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="sidebar">
                <div class="sidebar-title">Menu</div>
                <a href="hod_dashboard.php?view=overview"  class="sidebar-link <?php echo $view==='overview'                        ? 'active':''; ?>">Overview</a>
                <a href="hod_dashboard.php?view=mca"       class="sidebar-link <?php echo strpos($view,'mca')!==false              ? 'active':''; ?>">MCA</a>
                <a href="hod_dashboard.php?view=staff"     class="sidebar-link <?php echo $view==='staff'                          ? 'active':''; ?>">Staff</a>
                <a href="hod_dashboard.php?view=bca"       class="sidebar-link <?php echo $view==='bca'                            ? 'active':''; ?>">BCA</a>
                <a href="hod_dashboard.php?view=batch"     class="sidebar-link <?php echo $view==='batch'                          ? 'active':''; ?>">Batch</a>
            </aside>

            <main class="dashboard-main">

            <?php
            /* ═══════════════════════════════════════════════════════
               OVERVIEW
            ═══════════════════════════════════════════════════════ */
            if ($view === 'overview'):
                $total       = $conn->query("SELECT COUNT(*) AS c FROM student_submissions")->fetch_assoc()['c'] ?? 0;
                $staff_count = $conn->query("SELECT COUNT(DISTINCT guide_name) AS c FROM student_submissions")->fetch_assoc()['c'] ?? 0;
            ?>
                <h2 class="section-heading">HOD Overview</h2>
                <p class="sub-heading">Department of Computer Applications — Project Management System</p>
                <div class="card-grid" style="margin-top:20px;">
                    <div class="year-card floating" style="text-align:center;">
                        <div style="font-size:2.2em;font-weight:700;color:var(--green);font-family:'Inter',monospace;"><?php echo $total; ?></div>
                        <div style="color:var(--text-muted);font-size:0.85em;margin-top:4px;">Total Students</div>
                    </div>
                    <div class="year-card floating" style="text-align:center;">
                        <div style="font-size:2.2em;font-weight:700;color:var(--gold);font-family:'Inter',monospace;"><?php echo $staff_count; ?></div>
                        <div style="color:var(--text-muted);font-size:0.85em;margin-top:4px;">Project Guides</div>
                    </div>
                </div>

            <?php
            /* ═══════════════════════════════════════════════════════
               MCA — year/section card buttons
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'mca'):
            ?>
                <h2 class="section-heading">MCA Programme</h2>
                <p class="sub-heading">Select a section to view project details</p>
                <div class="card-grid">
                    <div class="year-card floating">
                        <h3>MCA 1st Year</h3>
                        <div class="pill-row">
                            <a href="hod_dashboard.php?view=mca_detail&sec=A&batch=2025-2027" class="pill-link"><span class="pill">Section A</span></a>
                        </div>
                    </div>
                    <div class="year-card floating">
                        <h3>MCA 2nd Year</h3>
                        <div class="pill-row">
                            <a href="hod_dashboard.php?view=mca_detail&sec=A&batch=2024-2026" class="pill-link"><span class="pill">Section A</span></a>
                            <a href="hod_dashboard.php?view=mca_detail&sec=B&batch=2024-2026" class="pill-link"><span class="pill">Section B</span></a>
                        </div>
                    </div>
                </div>

            <?php
            /* ═══════════════════════════════════════════════════════
               MCA SECTION DETAIL — queries only branch + section
               (no 'year' column needed)
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'mca_detail'):
                $sec   = $_GET['sec']   ?? '';
                $batch = $_GET['batch'] ?? '';

                $stmt = $conn->prepare(
                    "SELECT reg_no, student_name, project_title, guide_name,
                            r1_marks, r2_marks, r3_marks, r4_marks, r5_marks
                     FROM student_submissions
                     WHERE branch='MCA' AND section=?
                     ORDER BY reg_no ASC"
                );
                $stmt->bind_param("s", $sec);
                $stmt->execute();
                $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
                <a href="hod_dashboard.php?view=mca" class="back-link">← Back to MCA Programme</a>
                <h2 class="section-heading">MCA 2nd Year — Section <?php echo htmlspecialchars($sec); ?></h2>
                <p class="sub-heading">
                    Batch <?php echo htmlspecialchars($batch); ?> &nbsp;|&nbsp;
                    <?php echo count($students); ?> Students
                </p>

                <?php if (empty($students)): ?>
                    <p class="no-data">No student data found for this section. Please import the SQL data first.</p>
                <?php else: ?>
                <div class="table-container">
                    <table class="hod-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reg No</th>
                                <th>Student Name</th>
                                <th>Project Title</th>
                                <th>Guide</th>
                                <th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $i => $row): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['guide_name']); ?></td>
                                <td><?php echo $row['r1_marks'] ?: '—'; ?></td>
                                <td><?php echo $row['r2_marks'] ?: '—'; ?></td>
                                <td><?php echo $row['r3_marks'] ?: '—'; ?></td>
                                <td><?php echo $row['r4_marks'] ?: '—'; ?></td>
                                <td><?php echo $row['r5_marks'] ?: '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php
            /* ═══════════════════════════════════════════════════════
               STAFF — cards + drill-down table
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'staff'):
                $selected_guide = $_GET['guide'] ?? null;
                $all_staff = [
                    'Dr. K. Gayatri',
                    'Dr. K. Santhi Sri',
                    'Dr. M. Srikanth Yadav',
                    'Dr. N. Veeranjaneyulu',
                    'Dr. R.S. Padma Priya',
                    'Dr. Siva Koteswararao Chinnam',
                    'Mrs. R. Swathika',
                    'R. Naga Sirisha',
                ];

                $staff_profiles = [
                    'Dr. K. Gayatri' => [
                        'full_name' => 'Dr Gayatri Ketepalli',
                        'role'      => 'Assistant Professor',
                        'phone'     => '8555041186',
                        'email'     => 'gk_ca@vignan.ac.in',
                        'image'     => 'staff-gayatri.png',
                    ],
                    'Dr. K. Santhi Sri' => [
                        'full_name' => 'Dr Kurra Santhi Sri',
                        'role'      => 'Professor',
                        'phone'     => '9297105269',
                        'email'     => 'drkss_ca@vignan.ac.in',
                        'image'     => 'staff-santhi-sri.png',
                    ],
                    'Dr. M. Srikanth Yadav' => [
                        'full_name' => 'Dr Srikanth Yadav M',
                        'role'      => 'Associate Professor',
                        'phone'     => '8121827423',
                        'email'     => 'sym_it@vignan.ac.in',
                        'image'     => 'staff-srikanth-yadav.png',
                    ],
                    'Dr. N. Veeranjaneyulu' => [
                        'full_name' => 'Dr N. Veeranjaneyulu',
                        'role'      => 'Professor',
                        'phone'     => '9347162038',
                        'email'     => 'drnvn_it@vignan.ac.in',
                        'image'     => 'staff-veeranjaneyulu.png',
                    ],
                    'Dr. R.S. Padma Priya' => [
                        'full_name' => 'Dr R S Padma Priya',
                        'role'      => 'Associate Professor',
                        'phone'     => '8056582747',
                        'email'     => 'drpprs_ca@vignan.ac.in',
                        'image'     => 'staff-padma-priya.png',
                    ],
                    'Dr. Siva Koteswararao Chinnam' => [
                        'full_name' => 'Dr Siva Koteswararao Chinnam',
                        'role'      => 'Associate Professor',
                        'phone'     => '9440372374',
                        'email'     => 'drchskr_ca@vignan.ac.in',
                        'image'     => 'staff-siva-koteswararao.png',
                    ],
                    'Mrs. R. Swathika' => [
                        'full_name' => 'Mrs R Swathika',
                        'role'      => 'Assistant Professor',
                        'phone'     => '0',
                        'email'     => 'rs_ca@vignan.ac.in',
                        'image'     => 'staff-swathika.png',
                    ],
                    'R. Naga Sirisha' => [
                        'full_name' => 'Mrs R Naga sirisha',
                        'role'      => 'Assistant Professor',
                        'phone'     => '9494852495',
                        'email'     => 'rns_it_tra@vignan.ac.in',
                        'image'     => 'staff-naga-sirisha.png',
                    ],
                ];
            ?>
                <h2 class="section-heading">Staff — Project Guides</h2>
                <p class="sub-heading">Click a card to view assigned students</p>

                <div class="staff-card-grid">
                    <?php foreach ($all_staff as $name):
                        $cq = $conn->prepare("SELECT COUNT(*) AS c FROM student_submissions WHERE guide_name=?");
                        $cq->bind_param("s", $name);
                        $cq->execute();
                        $cnt = $cq->get_result()->fetch_assoc()['c'];
                    ?>
                        <div class="staff-card <?php echo ($selected_guide === $name) ? 'active':''; ?>"
                             onclick="location.href='hod_dashboard.php?view=staff&guide=<?php echo urlencode($name); ?>'">
                            <h4><?php echo htmlspecialchars($name); ?></h4>
                            <div class="count"><?php echo $cnt; ?></div>
                            <div class="label">students assigned</div>

                            <?php if (isset($staff_profiles[$name])): ?>
                                <?php $p = $staff_profiles[$name]; ?>
                                <div class="staff-profile">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['full_name']); ?>">
                                    <div class="profile-title">Profile</div>
                                    <p class="profile-name"><?php echo htmlspecialchars($p['full_name']); ?></p>
                                    <p class="profile-role"><?php echo htmlspecialchars($p['role']); ?></p>
                                    <p class="profile-meta"><strong>PH:</strong> <?php echo htmlspecialchars($p['phone']); ?></p>
                                    <p class="profile-meta"><strong>Email:</strong> <?php echo htmlspecialchars($p['email']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($selected_guide): ?>
                    <h3 style="color:var(--text-dark);margin-top:36px;font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;">
                        Students under <?php echo htmlspecialchars($selected_guide); ?>
                    </h3>
                    <?php
                    $sq = $conn->prepare(
                        "SELECT reg_no, student_name, branch, section, project_title
                         FROM student_submissions WHERE guide_name=? ORDER BY branch, reg_no"
                    );
                    $sq->bind_param("s", $selected_guide);
                    $sq->execute();
                    $allocated = $sq->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <?php if (empty($allocated)): ?>
                        <p class="no-data">No students allocated to this guide yet.</p>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="hod-table">
                            <thead>
                                <tr><th>#</th><th>Reg No</th><th>Student Name</th><th>Course</th><th>Project Title</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocated as $i => $r): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($r['reg_no']); ?></td>
                                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['branch']).' Sec '.$r['section']; ?></td>
                                    <td><?php echo htmlspecialchars($r['project_title']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php
            /* ═══════════════════════════════════════════════════════
               BCA — placeholder
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'bca'):
            ?>
                <h2 class="section-heading">BCA Programme</h2>
                <p class="sub-heading">Student data will appear here once imported.</p>
                <div class="card-grid">
                    <div class="year-card floating"><h3>BCA 1st Year</h3><div class="pill-row"><span class="pill">Section A</span><span class="pill">Section B</span></div></div>
                    <div class="year-card floating"><h3>BCA 2nd Year</h3><div class="pill-row"><span class="pill">Section A</span><span class="pill">Section B</span></div></div>
                    <div class="year-card floating"><h3>BCA 3rd Year</h3><div class="pill-row"><span class="pill">Section A</span><span class="pill">Section B</span></div></div>
                </div>

            <?php
            /* ═══════════════════════════════════════════════════════
               BATCH — year picker (2000 to current year)
               Uses 4-digit year found inside reg_no.
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'batch'):
                $startYear   = 2000;
                $currentYear = (int)date('Y');
                $selectedYear = ($batchYearSelected >= $startYear && $batchYearSelected <= $currentYear) ? $batchYearSelected : 0;
                $defaultDecade = (int)(floor(($selectedYear ?: $currentYear) / 10) * 10);
                $decadeStart = $batchDecadeStart > 0 ? $batchDecadeStart : $defaultDecade;
                if ($decadeStart < $startYear) {
                    $decadeStart = (int)(floor($startYear / 10) * 10);
                }
                if ($decadeStart > $currentYear) {
                    $decadeStart = (int)(floor($currentYear / 10) * 10);
                }
                $prevDecade = $decadeStart - 10;
                $nextDecade = $decadeStart + 10;
            ?>
                <h2 class="section-heading">Batch</h2>
                <p class="sub-heading">Select a batch year to view that year students</p>

                <div class="year-picker-wrap">
                    <div class="year-picker-card">
                        <div class="year-picker-header">
                            <a class="year-nav-btn" href="hod_dashboard.php?view=batch&decade=<?php echo $prevDecade; ?>" aria-label="Previous decade">&#8249;</a>
                            <div class="year-picker-title"><?php echo $decadeStart; ?> - <?php echo $decadeStart + 11; ?></div>
                            <a class="year-nav-btn" href="hod_dashboard.php?view=batch&decade=<?php echo $nextDecade; ?>" aria-label="Next decade">&#8250;</a>
                        </div>
                        <div class="year-grid">
                            <?php for ($yr = $decadeStart; $yr <= $decadeStart + 11; $yr++): ?>
                                <?php $isOutOfRange = ($yr < $startYear || $yr > $currentYear); ?>
                                <a
                                    href="hod_dashboard.php?view=batch&year=<?php echo $yr; ?>&decade=<?php echo $decadeStart; ?>"
                                    class="year-chip <?php echo ($selectedYear === $yr) ? 'active' : ''; ?> <?php echo $isOutOfRange ? 'muted' : ''; ?>"
                                >
                                    <?php echo $yr; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <?php if ($selectedYear): ?>
                    <?php
                    $allRows = $conn->query(
                        "SELECT reg_no, student_name, branch, section, year, semester, project_title, guide_name
                         FROM student_submissions
                         ORDER BY reg_no ASC"
                    );
                    $batchRows = [];

                    if ($allRows) {
                        while ($r = $allRows->fetch_assoc()) {
                            $reg = (string)($r['reg_no'] ?? '');
                            if (preg_match('/\d{4}/', $reg, $m) && (int)$m[0] === $selectedYear) {
                                $batchRows[] = $r;
                            }
                        }
                    }
                    ?>

                    <h3 style="color:var(--text-dark);margin-top:32px;font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;">
                        Batch <?php echo htmlspecialchars((string)$selectedYear); ?>
                    </h3>

                    <?php if (empty($batchRows)): ?>
                        <p class="no-data">No student data found for batch year <?php echo htmlspecialchars((string)$selectedYear); ?>.</p>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="hod-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reg No</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Year/Sem</th>
                                    <th>Project Title</th>
                                    <th>Guide</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batchRows as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['branch']) . ' Sec ' . htmlspecialchars($row['section']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['year']) . ' / ' . htmlspecialchars((string)$row['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['guide_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>