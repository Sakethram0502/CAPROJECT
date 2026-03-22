<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('db.php'); 

// Accessing staff name from session
$username = $_SESSION['staff_name'] ?? 'Staff';
$view = $_GET['view'] ?? 'overview';

// 1. HANDLE THE UPDATED 5-REVIEW & NOTES LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_marks'])) {
    $reg_no = $_POST['reg_no'];
    $phase = $_POST['review_phase']; 
    $marks = $_POST['marks'];
    $notes = $_POST['notes'];

    // Dynamically targeting the specific review marks and notes columns
    $marks_col = $phase . "_marks"; 
    $notes_col = $phase . "_notes"; 
    
    // Updating both marks and remarks in one query
    $update_sql = "UPDATE student_submissions SET $marks_col = ?, $notes_col = ? WHERE reg_no = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("iss", $marks, $notes, $reg_no);
    
    if($stmt->execute()){
        $stmt->close();
        // Redirect to maintain the current view (BCA/MCA/Overview)
        header("Location: staff_dashboard.php?view=$view");
        exit();
    }
}
// Ensure this is exactly how your query looks:
$base_query = "SELECT s.*, u.id AS upload_id, u.document_name, u.ppt_name, u.code_name 
               FROM student_submissions s 
               LEFT JOIN student_uploads u ON s.reg_no = u.reg_no 
               WHERE s.guide_name = ?";
               
if ($view === 'bca' || $view === 'mca') {
    $query = $base_query . " AND s.branch = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $view);
} else {
    $stmt = $conn->prepare($base_query);
    $stmt->bind_param("s", $username);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Project Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Tooltip style for notes */
        td[title] { cursor: help; border-bottom: 1px dashed rgba(255,255,255,0.3); }
        .project-subtext { display: block; font-size: 0.85em; color: #00d4ff; margin-top: 4px; }
        .domain-tag { display: block; font-size: 0.75em; color: #ffcc00; text-transform: uppercase; margin-top: 2px; }
        textarea { resize: none; width: 100%; padding: 10px; border-radius: 5px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); }
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
    <div class="sidebar-title">Menu</div>
    <a href="staff_dashboard.php?view=overview" class="sidebar-link <?php echo ($view === 'overview') ? 'active' : ''; ?>">Overview</a>
    <a href="staff_dashboard.php?view=bca" class="sidebar-link <?php echo ($view === 'bca') ? 'active' : ''; ?>">BCA Students</a>
    <a href="staff_dashboard.php?view=mca" class="sidebar-link <?php echo ($view === 'mca') ? 'active' : ''; ?>">MCA Students</a>
    
    <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <p style="font-size: 0.7em; color: #888; margin-bottom: 10px; padding-left: 15px; letter-spacing: 1px;">EXPORTS</p>
        
        <?php 
            // Default to 'ALL' for overview, otherwise use the branch name
            $report_param = ($view === 'overview') ? 'ALL' : strtoupper($view); 
            $display_label = ($view === 'overview') ? 'Master' : $report_param;
        ?>
        
        <a href="pdf_generator.php?course=<?php echo $report_param; ?>&format=pdf" 
           target="_blank" 
           class="sidebar-link" 
           style="color: #00d4ff;">
           📄 <?php echo $display_label; ?> PDF Report
        </a>

        <a href="pdf_generator.php?course=<?php echo $report_param; ?>&format=excel" 
           class="sidebar-link" 
           style="color: #00ff88;">
           📊 <?php echo $display_label; ?> Excel Sheet
        </a>
    </div>
</aside>
            <main class="dashboard-main">
                <h2 class="section-heading"><?php echo ($view === 'overview') ? "My Students" : strtoupper($view) . " Students"; ?></h2>

                <div class="table-container">
                    <table class="glass-table">
                        <thead>
                            <tr>
                                <th>Reg No</th>
<th>Student & Domain</th>
<th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th>
<th>Submission</th> <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                        <span class="domain-tag">Domain: <?php echo htmlspecialchars($row['domain'] ?? 'General'); ?></span>
                                        <span class="project-subtext"><?php echo htmlspecialchars($row['project_title']); ?></span>
                                    </td>
                                    <td title="Feedback: <?php echo htmlspecialchars($row['r1_notes'] ?? 'None'); ?>"><?php echo $row['r1_marks'] ?: '-'; ?></td>
                                    <td title="Feedback: <?php echo htmlspecialchars($row['r2_notes'] ?? 'None'); ?>"><?php echo $row['r2_marks'] ?: '-'; ?></td>
                                    <td title="Feedback: <?php echo htmlspecialchars($row['r3_notes'] ?? 'None'); ?>"><?php echo $row['r3_marks'] ?: '-'; ?></td>
                                    <td title="Feedback: <?php echo htmlspecialchars($row['r4_notes'] ?? 'None'); ?>"><?php echo $row['r4_marks'] ?: '-'; ?></td>
                                    <td title="Feedback: <?php echo htmlspecialchars($row['r5_notes'] ?? 'None'); ?>"><?php echo $row['r5_marks'] ?: '-'; ?></td>

<td>
    <?php if (!empty($row['upload_id'])): ?>
        <button type="button" class="btn-view" 
                style="background: rgba(0, 212, 255, 0.2); color: #00d4ff; border: 1px solid #00d4ff; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8em;"
                onclick="downloadPrompt('<?php echo $row['upload_id']; ?>', '<?php echo $row['document_name'] ? 1 : 0; ?>', '<?php echo $row['ppt_name'] ? 1 : 0; ?>', '<?php echo $row['code_name'] ? 1 : 0; ?>')">
            View Files
        </button>
    <?php else: ?>
        <span style="opacity: 0.4; font-size: 0.8em;">Pending</span>
    <?php endif; ?>
</td>
<td>
   
                                        <button class="btn-view btn-update" 
                                            data-reg="<?php echo $row['reg_no']; ?>" 
                                            data-name="<?php echo $row['student_name']; ?>"
                                            data-domain="<?php echo htmlspecialchars($row['domain'] ?? 'General'); ?>"
                                            data-project="<?php echo $row['project_title']; ?>">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="updateModal">
        <div class="modal-card floating">
            <h3>Update Marks & Remarks</h3>
            <form method="POST" action="staff_dashboard.php?view=<?php echo $view; ?>" class="form-glass modal-form">
                <input type="hidden" name="reg_no" id="modalRegNo">
                
                <div class="form-group">
                    <p style="margin: 0; font-size: 0.9em; color: #ccc;">Student: <span id="dispName" style="color: white; font-weight: bold;"></span></p>
                    <p style="margin: 2px 0; font-size: 0.8em; color: #ffcc00;">Domain: <span id="dispDomain"></span></p>
                    <p style="margin: 5px 0 15px 0; font-size: 0.85em; color: #00d4ff;">Project: <span id="dispProject"></span></p>
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
                    <label>Marks (0-100)</label>
                    <input type="number" name="marks" min="0" max="100" required>
                </div>

                <div class="form-group">
                    <label>Faculty Remarks</label>
                    <textarea name="notes" rows="3" placeholder="Enter review feedback..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-link modal-close">Cancel</button>
                    <button type="submit" name="update_marks" class="btn-gradient">Save Progress</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('updateModal');
        const updateButtons = document.querySelectorAll('.btn-update');

        updateButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('modalRegNo').value = btn.getAttribute('data-reg');
                document.getElementById('dispName').innerText = btn.getAttribute('data-name');
                document.getElementById('dispDomain').innerText = btn.getAttribute('data-domain');
                document.getElementById('dispProject').innerText = btn.getAttribute('data-project');
                modal.classList.add('open');
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.classList.remove('open'));
        });

        window.onclick = (e) => { if (e.target == modal) modal.classList.remove('open'); }
        function downloadPrompt(id, hasDoc, hasPpt, hasCode) {
    let msg = "Select a file to download:\n";
    if(parseInt(hasDoc)) msg += "1. Documentation\n";
    if(parseInt(hasPpt)) msg += "2. PPT\n";
    if(parseInt(hasCode)) msg += "3. Source Code\n";
    
    const choice = prompt(msg + "\nEnter file number:");
    
    // Change "download.php" to "download_file.php" here
    const scriptName = "download_file.php"; 
    
    if (choice === "1" && parseInt(hasDoc)) {
        window.location.href = scriptName + "?id=" + id + "&type=document";
    } else if (choice === "2" && parseInt(hasPpt)) {
        window.location.href = scriptName + "?id=" + id + "&type=ppt";
    } else if (choice === "3" && parseInt(hasCode)) {
        window.location.href = scriptName + "?id=" + id + "&type=code";
    } else if (choice !== null) {
        alert("Invalid selection or file not available.");
    }
}
    </script>
</body>
</html>