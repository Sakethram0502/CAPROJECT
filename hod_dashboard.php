<?php
session_start();
include('db.php');
$username = $_SESSION['username'] ?? 'HOD';
$view     = $_GET['view'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | Project Management System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .hod-table { width:100%; border-collapse:collapse; margin-top:16px; }
        .hod-table th { background:rgba(0,212,255,0.15); color:#00d4ff; padding:10px 14px;
                        text-align:left; font-size:0.85em; letter-spacing:1px; border-bottom:1px solid rgba(0,212,255,0.3); }
        .hod-table td { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,0.07);
                        font-size:0.88em; color:#e0e0e0; vertical-align:top; }
        .hod-table tr:hover td { background:rgba(255,255,255,0.04); }
        .staff-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; margin-top:16px; }
        .staff-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
                      border-radius:12px; padding:18px 20px; transition:0.3s; cursor:pointer; }
        .staff-card:hover, .staff-card.active { border-color:#00d4ff; background:rgba(0,212,255,0.1); }
        .staff-card h4 { margin:0 0 6px; color:#fff; font-size:0.95em; }
        .staff-card .count { font-size:1.6em; font-weight:700; color:#00d4ff; }
        .staff-card .label { font-size:0.75em; color:#aaa; margin-top:2px; }
        .section-heading { color:#fff; margin-bottom:4px; }
        .sub-heading { color:#aaa; font-size:0.85em; margin:0 0 16px; }
        .no-data { color:#888; font-size:0.9em; padding:20px 0; }
        .back-link { display:inline-block; margin-bottom:15px; color:#00d4ff; text-decoration:none; font-size:0.9em; }
        .pill-link { text-decoration:none; color:inherit; }
        .pill-link:hover .pill { background:rgba(0,212,255,0.4); border-color:#00d4ff; }
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
                        <div style="font-size:2.2em;font-weight:700;color:#00d4ff;"><?php echo $total; ?></div>
                        <div style="color:#aaa;font-size:0.85em;margin-top:4px;">Total Students</div>
                    </div>
                    <div class="year-card floating" style="text-align:center;">
                        <div style="font-size:2.2em;font-weight:700;color:#00ff88;"><?php echo $staff_count; ?></div>
                        <div style="color:#aaa;font-size:0.85em;margin-top:4px;">Project Guides</div>
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
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($selected_guide): ?>
                    <h3 style="color:#fff;margin-top:36px;">
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

            <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>