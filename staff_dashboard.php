<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include('db.php');

if (!isset($_SESSION['staff_name'])) {
    header('Location: index.php?view=staff');
    exit;
}

$username = $_SESSION['staff_name'];
$view     = $_GET['view'] ?? 'overview';

// ── SAVE MARKS & NOTES ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_marks'])) {
    $reg_no    = $_POST['reg_no'];
    $semester  = $_POST['semester_filter'] ?? '';
    $phase     = $_POST['review_phase'];
    $marks     = $_POST['marks'];
    $notes     = $_POST['notes'];
    $marks_col = $phase . '_marks';
    $notes_col = $phase . '_notes';
    $sql  = "UPDATE student_submissions SET $marks_col = ?, $notes_col = ? WHERE reg_no = ? AND semester = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $marks, $notes, $reg_no, $semester);
    $stmt->execute();
    header("Location: staff_dashboard.php?view=$view");
    exit;
}

// ── FETCH: student_submissions for this guide ─────────────────────────
// Submissions are keyed by guide_name
if ($view === 'bca' || $view === 'mca') {
    $stmt = $conn->prepare(
        "SELECT * FROM student_submissions
         WHERE guide_name = ? AND branch = ?
         ORDER BY reg_no, year, semester"
    );
    
    // Assign to a variable first
    $branch = strtoupper($view); 
    
    // Pass the variable
    $stmt->bind_param('ss', $username, $branch);
}else {
    $stmt = $conn->prepare(
        "SELECT * FROM student_submissions
         WHERE guide_name = ?
         ORDER BY reg_no, year, semester"
    );
    $stmt->bind_param('s', $username);
}
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── FETCH: Pending approval requests for this guide's students ────────
$conn->query("CREATE TABLE IF NOT EXISTS update_requests (
    id INT AUTO_INCREMENT PRIMARY KEY, reg_no VARCHAR(50) NOT NULL,
    request_type ENUM('files','details') NOT NULL, reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    guide_remark TEXT DEFAULT NULL, requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    actioned_at DATETIME DEFAULT NULL, used TINYINT(1) DEFAULT 0,
    INDEX (reg_no), INDEX (status)
)");

// ── HANDLE: Approve / Reject a request ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_request'])) {
    $reqId   = (int)$_POST['req_id'];
    $action  = $_POST['req_action']; // 'approved' or 'rejected'
    $remark  = trim($_POST['guide_remark'] ?? '');
    if (in_array($action, ['approved','rejected'])) {
        $upd = $conn->prepare("UPDATE update_requests SET status=?, guide_remark=?, actioned_at=NOW() WHERE id=?");
        $upd->bind_param('ssi', $action, $remark, $reqId);
        $upd->execute();
    }
    header("Location: staff_dashboard.php?view=$view");
    exit;
}

// Get student reg_nos for this guide
$myRegNos = array_column($submissions, 'reg_no');
$pendingRequests = [];
if (!empty($myRegNos)) {
    $placeholders = implode(',', array_fill(0, count($myRegNos), '?'));
    $types = str_repeat('s', count($myRegNos));
    $rStmt = $conn->prepare("SELECT r.*, s.student_name FROM update_requests r
        LEFT JOIN student_submissions s ON s.reg_no = r.reg_no
        WHERE r.reg_no IN ($placeholders) AND r.status='pending' AND r.used=0
        ORDER BY r.requested_at ASC");
    $rStmt->bind_param($types, ...$myRegNos);
    $rStmt->execute();
    $pendingRequests = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── FETCH: ALL uploads from student_uploads, keyed by reg_no ──────────
// We query ALL uploads — no guide filter — match by reg_no
$uploadMap = [];  // reg_no => latest upload row
$uRes = $conn->query(
    "SELECT id, reg_no, semester, document_name, ppt_name, code_name
     FROM student_uploads
     ORDER BY id DESC"
);
while ($uRow = $uRes->fetch_assoc()) {
    // Keep the latest upload per reg_no (first one seen since ORDER BY id DESC)
    if (!isset($uploadMap[$uRow['reg_no']])) {
        $uploadMap[$uRow['reg_no']] = $uRow;
    }
}

// ── MERGE: attach upload info into each submission row ────────────────
foreach ($submissions as &$s) {
    $reg = $s['reg_no'];
    if (isset($uploadMap[$reg])) {
        $s['upload_id']     = $uploadMap[$reg]['id'];
        $s['document_name'] = $uploadMap[$reg]['document_name'];
        $s['ppt_name']      = $uploadMap[$reg]['ppt_name'];
        $s['code_name']     = $uploadMap[$reg]['code_name'];
    } else {
        $s['upload_id']     = null;
        $s['document_name'] = null;
        $s['ppt_name']      = null;
        $s['code_name']     = null;
    }
}
unset($s);

$semLabels = [
    'I'   => 'Sem I',
    'II'  => 'Sem II',
    '1-1' => 'Year 1 Sem 1', '1-2' => 'Year 1 Sem 2',
    '2-1' => 'Year 2 Sem 1', '2-2' => 'Year 2 Sem 2',
    'I-I' => 'Year I Sem I', 'I-II'=> 'Year I Sem II',
    'II-I'=> 'Year II Sem I','II-II'=> 'Year II Sem II',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Project Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Student card — modern layout ── */
        .student-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .student-card:hover { border-color: var(--border-strong); box-shadow: var(--shadow-md); }
        /* Red left border so no-upload students are impossible to miss */
        .student-card.no-upload { border-left: 4px solid #e53e3e; }

        /* Alert strip at top of no-upload cards */
        .upload-alert-strip {
            background: #fff5f5;
            border-bottom: 1px solid var(--red-bd);
            padding: 5px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--red-txt);
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.01em;
        }

        /* Info row: Reg | Name | Project | Upload status */
        .student-card-header {
            display: grid;
            grid-template-columns: 160px 1fr 1fr 130px;
            align-items: stretch;
            border-bottom: 1px solid var(--border-light);
        }
        .sc-col {
            padding: 13px 15px;
            border-right: 1px solid var(--border-light);
        }
        .sc-col:last-child {
            border-right: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sc-label {
            font-size: 0.67rem;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--text-faint);
            margin-bottom: 5px;
        }
        .sc-value {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.35;
        }
        .sc-reg {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: 0.02em;
        }
        .sc-sub {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Domain chip */
        .domain-chip {
            display: inline-flex;
            align-items: center;
            padding: 2px 9px;
            border-radius: 5px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 5px;
        }
        .domain-chip.no-domain { background: #f1f5fe; color: #3b5bdb; border: 1px solid #c5d2fb; }
        .domain-chip.has-domain { background: var(--green-bg); color: var(--green-txt); border: 1px solid var(--green-bd); }

        /* Upload status tags */
        .tag-upload-ok { font-size: 0.78rem; font-weight: 700; padding: 5px 12px; border-radius: 7px; background: var(--green-bg); color: var(--green-txt); border: 1px solid var(--green-bd); }
        .tag-upload-no { font-size: 0.78rem; font-weight: 700; padding: 5px 12px; border-radius: 7px; background: var(--red-bg); color: var(--red-txt); border: 1px solid var(--red-bd); }

        /* Reviews row — timeline style */
        .reviews-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr) 110px 110px;
            align-items: center;
            background: #fafbfd;
            border-top: 1px solid var(--border-light);
        }
        .review-cell {
            padding: 10px 10px;
            border-right: 1px solid var(--border-light);
            text-align: center;
        }
        .review-cell:last-child { border-right: none; }
        .review-cell-label {
            font-size: 0.63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-faint);
            margin-bottom: 6px;
        }

        /* Review score bubble */
        .mark-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 0.84rem;
            font-weight: 700;
            border: 2px solid;
        }
        .mark-badge.done { background: var(--green-bg); color: var(--green-txt); border-color: var(--green-bd); }
        .mark-badge.miss { background: var(--red-bg);   color: var(--red-txt);   border-color: var(--red-bd); }

        .mark-notes {
            font-size: 0.65rem;
            color: var(--text-muted);
            margin-top: 4px;
            max-width: 80px;
            margin-left: auto; margin-right: auto;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* Action cells — always visible, solid buttons */
        .action-cell {
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
            justify-content: center;
            border-right: 1px solid var(--border-light);
        }
        .action-cell:last-child { border-right: none; }

        /* Tab bar */
        .tab-bar {
            display: flex;
            gap: 4px;
            margin-bottom: 18px;
            background: var(--border-light);
            border-radius: 9px;
            padding: 4px;
            width: fit-content;
        }
        .tab-btn {
            padding: 7px 18px;
            border-radius: 7px;
            font-size: 0.84rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            transition: all 0.15s;
        }
        .tab-btn.active { background: #fff; color: var(--navy); font-weight: 600; box-shadow: var(--shadow-sm); }
        .tab-btn .tab-count {
            display: inline-flex; align-items: center; justify-content: center;
            background: #fee2e2; color: #dc2626; border-radius: 100px;
            font-size: 0.68rem; font-weight: 700; padding: 1px 6px; margin-left: 5px;
        }

        /* Pending export bar */
        .pending-export-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--amber-bg); border: 1px solid var(--amber-bd);
            border-radius: var(--radius-md); padding: 11px 16px; margin-bottom: 12px;
        }
        .pending-export-bar p { font-size: 0.84rem; color: var(--amber-txt); font-weight: 600; margin: 0; }
        .pending-export-btns { display: flex; gap: 8px; }
        .exp-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px; border-radius: 7px; font-size: 0.81rem;
            font-weight: 600; text-decoration: none; border: 1px solid;
            font-family: 'DM Sans', sans-serif; transition: opacity 0.15s;
        }
        .exp-btn:hover { opacity: 0.75; }
        .exp-btn.pdf   { background: #fff; color: var(--red-txt); border-color: var(--red-bd); }
        .exp-btn.excel { background: #fff; color: var(--green-txt); border-color: var(--green-bd); }
    </style>
</head>
<body>
<div class="background-overlay"></div>
<div class="water-animation"></div>

<div class="dashboard-wrapper">
    <header class="top-nav">
        <div class="top-nav-left"><span class="brand-title">Department of Computer Applications</span></div>
        <div class="top-nav-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="btn-link nav-logout">Logout</a>
        </div>
    </header>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-title">Students</div>
            <a href="staff_dashboard.php?view=mca"      class="sidebar-link <?php echo $view==='mca'     ?'active':''; ?>">MCA Students</a>
            <a href="staff_dashboard.php?view=bca"      class="sidebar-link <?php echo $view==='bca'     ?'active':''; ?>">BCA Students</a>
            <a href="staff_dashboard.php?view=approvals" class="sidebar-link <?php echo $view==='approvals'?'active':''; ?>"
               style="<?php echo count($pendingRequests)>0?'color:#fca5a5;font-weight:600;':''; ?>">
                Approvals
                <?php if(count($pendingRequests)>0): ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;background:#dc2626;color:#fff;border-radius:100px;font-size:0.65rem;font-weight:700;padding:1px 6px;margin-left:5px;"><?php echo count($pendingRequests); ?></span>
                <?php endif; ?>
            </a>
            <div style="margin-top:40px;border-top:1px solid rgba(255,255,255,0.10);padding-top:16px;">
                <p style="font-size:0.68em;color:rgba(255,255,255,0.35);padding-left:18px;letter-spacing:0.12em;text-transform:uppercase;margin-bottom:6px;">Export All</p>
                <?php $rp = ($view==='overview')?'ALL':strtoupper($view); ?>
                <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=pdf"   target="_blank" class="sidebar-link">📄 PDF Report</a>
                <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=excel" target="_blank" class="sidebar-link">📊 Excel Sheet</a>
            </div>
        </aside>

        <main class="dashboard-main">

            <?php
            $no_upload = array_values(array_filter($submissions, fn($r) => empty($r['upload_id'])));
            $no_review = array_values(array_filter($submissions, fn($r) =>
                !$r['r1_marks'] && !$r['r2_marks'] && !$r['r3_marks'] && !$r['r4_marks'] && !$r['r5_marks']
            ));
            $active_tab = $_GET['tab'] ?? 'all';
            ?>

            <!-- Heading -->
            <h2 class="section-heading" style="margin-bottom:2px;">
                <?php echo $view==='overview' ? 'My Students' : strtoupper($view).' Students'; ?>
            </h2>
            <p class="sub-heading"><?php echo count($submissions); ?> students assigned to you</p>

            <!-- Stats summary -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-num"><?php echo count($submissions); ?></div>
                    <div class="stat-card-lbl">Total Students</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-card-num"><?php echo count($no_upload); ?></div>
                    <div class="stat-card-lbl">Pending Upload</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-card-num"><?php echo count($no_review); ?></div>
                    <div class="stat-card-lbl">No Review Yet</div>
                </div>
            </div>

            <!-- Tab bar -->
            <div class="tab-bar">
                <button class="tab-btn <?php echo $active_tab==='all'?'active':''; ?>" onclick="switchTab('all')">
                    All Students
                </button>
                <button class="tab-btn <?php echo $active_tab==='no_upload'?'active':''; ?>" onclick="switchTab('no_upload')">
                    No Upload
                    <?php if(count($no_upload)>0): ?>
                    <span class="tab-count"><?php echo count($no_upload); ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn <?php echo $active_tab==='no_review'?'active':''; ?>" onclick="switchTab('no_review')">
                    No Review
                    <?php if(count($no_review)>0): ?>
                    <span class="tab-count"><?php echo count($no_review); ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <?php if($view === 'approvals'): ?>
            <!-- ══ APPROVALS VIEW ══ -->
            <h2 class="section-heading" style="margin-bottom:2px;">Student Update Requests</h2>
            <p class="sub-heading"><?php echo count($pendingRequests); ?> pending approval<?php echo count($pendingRequests)!==1?'s':''; ?></p>

            <?php if(empty($pendingRequests)): ?>
            <div style="text-align:center;padding:56px;background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-sm);">
                <div style="font-size:2.5rem;margin-bottom:12px;"></div>
                <div style="font-weight:600;color:var(--text-dark);font-size:1rem;">No pending requests</div>
                <div style="color:var(--text-muted);font-size:0.86rem;margin-top:4px;">All student requests have been actioned.</div>
            </div>
            <?php else: ?>
            <?php foreach($pendingRequests as $req): ?>
            <div style="background:#fff;border:1px solid var(--border);border-left:4px solid #f59e0b;border-radius:12px;padding:18px 20px;margin-bottom:14px;box-shadow:var(--shadow-sm);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
                    <div>
                        <div style="font-weight:700;color:var(--text-dark);font-size:0.95rem;"><?php echo htmlspecialchars($req['student_name'] ?? $req['reg_no']); ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;"><?php echo htmlspecialchars($req['reg_no']); ?> &nbsp;·&nbsp;
                            <?php echo date('d M Y, h:i A', strtotime($req['requested_at'])); ?>
                            <?php if(!empty($req['semester_key'])): ?>
                            &nbsp;·&nbsp; <strong style="color:var(--green-dark);">Sem: <?php echo htmlspecialchars($req['semester_key']); ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:100px;font-size:0.76rem;font-weight:700;border:1px solid;
                        <?php echo $req['request_type']==='files'?'background:#eff6ff;color:#1d4ed8;border-color:#93c5fd;':'background:#f0fdf4;color:#166534;border-color:#86efac;'; ?>">
                        <?php echo $req['request_type']==='files' ? '📁 File Resubmission' : '📝 Details Update'; ?>
                    </span>
                </div>
                <div style="background:#fafbfc;border:1px solid var(--border-light);border-radius:8px;padding:10px 14px;margin-bottom:14px;">
                    <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-faint);margin-bottom:4px;">Student's Reason</div>
                    <div style="font-size:0.88rem;color:var(--text-body);"><?php echo htmlspecialchars($req['reason']); ?></div>
                </div>
                <form method="POST" action="staff_dashboard.php?view=approvals" style="display:flex;flex-direction:column;gap:10px;">
                    <input type="hidden" name="action_request" value="1">
                    <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.82rem;font-weight:600;color:var(--text-body);margin-bottom:5px;display:block;">Remark (optional — shown to student)</label>
                        <input type="text" name="guide_remark" placeholder="e.g. Approved, update title carefully / Rejected, changes not allowed at this stage"
                               style="width:100%;padding:8px 12px;border-radius:7px;border:1.5px solid var(--border);font-size:0.88rem;font-family:'Inter',sans-serif;outline:none;">
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" name="req_action" value="approved"
                                style="flex:1;padding:9px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-weight:700;font-size:0.88rem;cursor:pointer;font-family:'Inter',sans-serif;transition:opacity 0.15s;"
                                onmouseover="this.style.opacity=0.85" onmouseout="this.style.opacity=1">
                            Approve
                        </button>
                        <button type="submit" name="req_action" value="rejected"
                                style="flex:1;padding:9px;border-radius:8px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-weight:700;font-size:0.88rem;cursor:pointer;font-family:'Inter',sans-serif;transition:opacity 0.15s;"
                                onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php else: ?>
            <div id="tab-all" class="tab-section" style="display:<?php echo $active_tab==='all'?'block':'none'; ?>;">
                <?php foreach ($submissions as $row): ?>
                <div class="student-card <?php echo empty($row['upload_id']) ? 'no-upload' : ''; ?>">
                    <?php if(empty($row['upload_id'])): ?>
                    <div class="upload-alert-strip">⚠ Document not uploaded yet</div>
                    <?php endif; ?>
                    <!-- Header: student info -->
                    <div class="student-card-header">
                        <div class="sc-col">
                            <div class="sc-label">Reg No</div>
                            <div class="sc-reg"><?php echo htmlspecialchars($row['reg_no']); ?></div>
                            <div class="sc-sub"><?php echo htmlspecialchars($row['branch']); ?> &nbsp;·&nbsp; Sec <?php echo htmlspecialchars($row['section'] ?? '—'); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Student Name</div>
                            <div class="sc-value"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div class="sc-sub">Year <?php echo htmlspecialchars($row['year'] ?? '?'); ?> &nbsp;/&nbsp; Sem <?php echo htmlspecialchars($row['semester']); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Project</div>
                            <div class="domain-chip <?php echo empty($row['domain']) ? 'no-domain' : 'has-domain'; ?>">
                                <?php echo htmlspecialchars($row['domain'] ?? 'No Domain'); ?>
                            </div>
                            <div class="sc-value" style="font-size:0.82rem;font-weight:500;color:var(--slate);"><?php echo htmlspecialchars($row['project_title']); ?></div>
                        </div>
                        <div class="sc-col">
                            <?php if(!empty($row['upload_id'])): ?>
                                <span class="tag-upload-ok">✓ Uploaded</span>
                            <?php else: ?>
                                <span class="tag-upload-no">✗ No Upload</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Reviews row -->
                    <div class="reviews-row">
                        <?php foreach(['r1'=>'Review 1','r2'=>'Review 2','r3'=>'Review 3','r4'=>'Review 4','r5'=>'Review 5'] as $rk=>$rl): ?>
                        <div class="review-cell">
                            <div class="review-cell-label"><?php echo $rl; ?></div>
                            <?php if($row[$rk.'_marks']): ?>
                                <div class="mark-badge done" title="<?php echo htmlspecialchars($row[$rk.'_notes']??''); ?>"><?php echo $row[$rk.'_marks']; ?></div>
                                <?php if(!empty($row[$rk.'_notes'])): ?>
                                <div class="mark-notes" title="<?php echo htmlspecialchars($row[$rk.'_notes']); ?>"><?php echo htmlspecialchars($row[$rk.'_notes']); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="mark-badge miss">—</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <!-- Files action -->
                        <div class="action-cell">
                            <div class="review-cell-label">Files</div>
                            <?php if(!empty($row['upload_id'])): ?>
                            <button class="view-btn" onclick="openFilesModal('<?php echo $row['upload_id']; ?>','<?php echo htmlspecialchars(addslashes($row['reg_no'])); ?>','<?php echo htmlspecialchars(addslashes($row['student_name'])); ?>',<?php echo $row['document_name']?1:0; ?>,<?php echo $row['ppt_name']?1:0; ?>,<?php echo $row['code_name']?1:0; ?>)">📁 View Files</button>
                            <?php else: ?>
                            <span style="font-size:0.72rem;color:var(--text-faint);">None</span>
                            <?php endif; ?>
                        </div>
                        <!-- Update action -->
                        <div class="action-cell" style="border-right:none;">
                            <div class="review-cell-label">Marks</div>
                            <button class="btn-update"
                                data-reg="<?php echo htmlspecialchars($row['reg_no']); ?>"
                                data-sem="<?php echo htmlspecialchars($row['semester']); ?>"
                                data-year="<?php echo htmlspecialchars($row['year'] ?? ''); ?>"
                                data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                data-domain="<?php echo htmlspecialchars($row['domain'] ?? '—'); ?>"
                                data-project="<?php echo htmlspecialchars($row['project_title']); ?>">
                                Update
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($submissions)): ?>
                <div style="text-align:center;padding:40px;color:var(--text-muted);">No students assigned yet.</div>
                <?php endif; ?>
            </div>

            <!-- ══ NO UPLOAD TAB ══ -->
            <div id="tab-no_upload" class="tab-section" style="display:<?php echo $active_tab==='no_upload'?'block':'none'; ?>;">
                <?php if(!empty($no_upload)): ?>
                <div class="pending-export-bar">
                    <p>⚠️ <?php echo count($no_upload); ?> students have not uploaded their documents yet.</p>
                    <div class="pending-export-btns">
                        <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=pdf&pending=upload"   target="_blank" class="exp-btn pdf">📄 Export PDF</a>
                        <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=excel&pending=upload" target="_blank" class="exp-btn excel">📊 Export Excel</a>
                    </div>
                </div>
                <?php foreach($no_upload as $row): ?>
                <div class="student-card no-upload">
                    <div class="upload-alert-strip">⚠ Document not uploaded yet</div>
                    <div class="student-card-header">
                        <div class="sc-col">
                            <div class="sc-label">Reg No</div>
                            <div class="sc-reg"><?php echo htmlspecialchars($row['reg_no']); ?></div>
                            <div class="sc-sub"><?php echo htmlspecialchars($row['branch']); ?> &nbsp;·&nbsp; Sec <?php echo htmlspecialchars($row['section'] ?? '—'); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Student Name</div>
                            <div class="sc-value"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div class="sc-sub">Year <?php echo htmlspecialchars($row['year'] ?? '?'); ?> &nbsp;/&nbsp; Sem <?php echo htmlspecialchars($row['semester']); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Project</div>
                            <div class="domain-chip <?php echo empty($row['domain']) ? 'no-domain' : 'has-domain'; ?>">
                                <?php echo htmlspecialchars($row['domain'] ?? 'No Domain'); ?>
                            </div>
                            <div class="sc-value" style="font-size:0.82rem;font-weight:500;color:var(--slate);"><?php echo htmlspecialchars($row['project_title']); ?></div>
                        </div>
                        <div class="sc-col">
                            <span class="tag-upload-no">✗ No Upload</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center;padding:48px;color:var(--text-muted);">
                    <div style="font-size:2rem;margin-bottom:10px;">✅</div>
                    All students have uploaded their documents.
                </div>
                <?php endif; ?>
            </div>

            <!-- ══ NO REVIEW TAB ══ -->
            <div id="tab-no_review" class="tab-section" style="display:<?php echo $active_tab==='no_review'?'block':'none'; ?>;">
                <?php if(!empty($no_review)): ?>
                <div class="pending-export-bar">
                    <p>⚠️ <?php echo count($no_review); ?> students have received no review marks yet.</p>
                    <div class="pending-export-btns">
                        <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=pdf&pending=review"   target="_blank" class="exp-btn pdf">📄 Export PDF</a>
                        <a href="pdf_generator.php?course=<?php echo $rp; ?>&format=excel&pending=review" target="_blank" class="exp-btn excel">📊 Export Excel</a>
                    </div>
                </div>
                <?php foreach($no_review as $row): ?>
                <div class="student-card <?php echo empty($row['upload_id']) ? 'no-upload' : ''; ?>">
                    <?php if(empty($row['upload_id'])): ?>
                    <div class="upload-alert-strip">⚠ Document not uploaded yet</div>
                    <?php endif; ?>
                    <div class="student-card-header">
                        <div class="sc-col">
                            <div class="sc-label">Reg No</div>
                            <div class="sc-reg"><?php echo htmlspecialchars($row['reg_no']); ?></div>
                            <div class="sc-sub"><?php echo htmlspecialchars($row['branch']); ?> &nbsp;·&nbsp; Sec <?php echo htmlspecialchars($row['section'] ?? '—'); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Student Name</div>
                            <div class="sc-value"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div class="sc-sub">Year <?php echo htmlspecialchars($row['year'] ?? '?'); ?> &nbsp;/&nbsp; Sem <?php echo htmlspecialchars($row['semester']); ?></div>
                        </div>
                        <div class="sc-col">
                            <div class="sc-label">Project</div>
                            <div class="domain-chip <?php echo empty($row['domain']) ? 'no-domain' : 'has-domain'; ?>">
                                <?php echo htmlspecialchars($row['domain'] ?? 'No Domain'); ?>
                            </div>
                            <div class="sc-value" style="font-size:0.82rem;font-weight:500;color:var(--slate);"><?php echo htmlspecialchars($row['project_title']); ?></div>
                        </div>
                        <div class="sc-col" style="text-align:center;min-width:90px;">
                            <button class="btn-update"
                                data-reg="<?php echo htmlspecialchars($row['reg_no']); ?>"
                                data-sem="<?php echo htmlspecialchars($row['semester']); ?>"
                                data-year="<?php echo htmlspecialchars($row['year'] ?? ''); ?>"
                                data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                data-domain="<?php echo htmlspecialchars($row['domain'] ?? '—'); ?>"
                                data-project="<?php echo htmlspecialchars($row['project_title']); ?>">
                                + Add Review
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center;padding:48px;color:var(--text-muted);">
                    <div style="font-size:2rem;margin-bottom:10px;">✅</div>
                    All students have received at least one review.
                </div>
                <?php endif; ?>
            </div>

            <?php endif; // end approvals vs students view ?>

        </main>
    </div>
</div>

<!-- ── UPDATE MODAL ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="updateModal">
    <div class="modal-card floating">
        <h3>Update Marks & Remarks</h3>
        <form method="POST" action="staff_dashboard.php?view=<?php echo $view; ?>" class="form-glass modal-form">
            <input type="hidden" name="reg_no"          id="modalRegNo">
            <input type="hidden" name="semester_filter" id="modalSem">
            <div class="form-group">
                <p style="margin:0;font-size:0.9em;color:var(--text-muted);">Student: <span id="dispName" style="color:var(--text-dark);font-weight:600;font-family:'Playfair Display',serif;"></span></p>
                <p style="margin:2px 0;font-size:0.78em;color:var(--gold);font-weight:600;">Domain: <span id="dispDomain"></span></p>
                <p style="margin:4px 0;font-size:0.78em;color:var(--text-muted);">Year / Sem: <span id="dispSem" style="color:var(--green);font-weight:500;"></span></p>
                <p style="margin:4px 0 14px;font-size:0.83em;color:var(--green-dark);font-weight:500;">Project: <span id="dispProject"></span></p>
            </div>
            <div class="form-group">
                <label>Review Phase</label>
                <select name="review_phase" required>
                    <option value="r1">Review 1</option>
                    <option value="r2">Review 2</option>
                    <option value="r3">Review 3</option>
                    <option value="r4">Review 4</option>
                    <option value="r5">Review 5</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marks (0–100)</label>
                <input type="number" name="marks" min="0" max="100" required>
            </div>
            <div class="form-group">
                <label>Faculty Remarks</label>
                <textarea name="notes" rows="3" placeholder="Enter review feedback..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-close" style="padding:9px 20px;border-radius:7px;border:2px solid var(--border-strong, #94a3b8);background:#f1f5f9;color:#334155;font-weight:600;font-size:0.9rem;cursor:pointer;font-family:inherit;transition:background 0.15s;">Cancel</button>
                <button type="submit" name="update_marks" class="btn-gradient">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ── FILES MODAL ────────────────────────────────────────────────── -->
<div class="modal-overlay" id="filesModal">
    <div class="modal-card floating" style="max-width:400px;">
        <h3 style="margin-bottom:4px;">📁 Student Files</h3>
        <p id="fmStudent" style="color:var(--text-muted);font-size:0.85em;margin:0 0 16px;"></p>
        <div id="fmButtons"></div>
        <div style="margin-top:16px;text-align:right;">
            <button type="button" id="filesModalClose" style="background:none;border:1px solid var(--border);border-radius:100px;color:var(--text-muted);cursor:pointer;font-size:0.85em;padding:6px 16px;font-family:'Inter',sans-serif;">Close</button>
        </div>
    </div>
</div>

<script>
// ── Tab switching ─────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    event.currentTarget.classList.add('active');
}

// ── Update modal ─────────────────────────────────────────────────────
const updateModal = document.getElementById('updateModal');
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', () => {
        const sem  = btn.getAttribute('data-sem');
        const year = btn.getAttribute('data-year');
        document.getElementById('modalRegNo').value      = btn.getAttribute('data-reg');
        document.getElementById('modalSem').value        = sem;
        document.getElementById('dispName').innerText    = btn.getAttribute('data-name');
        document.getElementById('dispDomain').innerText  = btn.getAttribute('data-domain');
        document.getElementById('dispProject').innerText = btn.getAttribute('data-project');
        document.getElementById('dispSem').innerText     = 'Year ' + year + ' / Sem ' + sem;
        updateModal.classList.add('open');
    });
});
document.querySelectorAll('.modal-close').forEach(b =>
    b.addEventListener('click', () => updateModal.classList.remove('open'))
);
window.addEventListener('click', e => {
    if (e.target === updateModal) updateModal.classList.remove('open');
});

// ── Files modal ──────────────────────────────────────────────────────
const filesModal = document.getElementById('filesModal');

function openFilesModal(id, reg, name, hasDoc, hasPpt, hasCode) {
    document.getElementById('fmStudent').innerText = reg + '  —  ' + name;

    const container = document.getElementById('fmButtons');
    container.innerHTML = '';

    if (parseInt(hasDoc)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=document';
        a.className = 'fm-btn fm-doc';
        a.innerHTML = '📄 &nbsp;Download Document';
        container.appendChild(a);
    }
    if (parseInt(hasPpt)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=ppt';
        a.className = 'fm-btn fm-ppt';
        a.innerHTML = '📊 &nbsp;Download Presentation';
        container.appendChild(a);
    }
    if (parseInt(hasCode)) {
        const a = document.createElement('a');
        a.href      = 'download_file.php?id=' + id + '&type=code';
        a.className = 'fm-btn fm-code';
        a.innerHTML = '💻 &nbsp;Download Source Code';
        container.appendChild(a);
    }

    // ZIP — always shown
    const zip = document.createElement('a');
    zip.href      = 'download_file.php?id=' + id + '&type=zip&reg=' + encodeURIComponent(reg);
    zip.className = 'fm-btn fm-zip';
    zip.innerHTML = '⬇ &nbsp;Download All as ZIP';
    container.appendChild(zip);

    filesModal.classList.add('open');
}

document.getElementById('filesModalClose').addEventListener('click', () =>
    filesModal.classList.remove('open')
);
window.addEventListener('click', e => {
    if (e.target === filesModal) filesModal.classList.remove('open');
});
</script>
</body>
</html>