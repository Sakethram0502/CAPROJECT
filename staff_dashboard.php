<?php
session_start();
include('db.php'); 

$username = $_SESSION['staff_name'] ?? 'Staff';
$view = $_GET['view'] ?? 'overview';

// 1. HANDLE THE UPDATED 5-REVIEW & NOTES LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_marks'])) {
    $reg_no = $_POST['reg_no'];
    $phase = $_POST['review_phase']; 
    $marks = $_POST['marks'];
    $notes = $_POST['notes'];

    // Dynamically targeting the specific review and notes columns
    $marks_col = $phase . "_marks"; 
    $notes_col = $phase . "_notes"; 
    
    $update_sql = "UPDATE student_submissions SET $marks_col = ?, $notes_col = ? WHERE reg_no = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("iss", $marks, $notes, $reg_no);
    
    if($stmt->execute()){
        $stmt->close();
        header("Location: staff_dashboard.php?view=$view");
        exit();
    }
}

// 2. Fetch Students based on Sidebar Selection
if ($view === 'bca' || $view === 'mca') {
    $query = "SELECT * FROM student_submissions WHERE guide_name = ? AND branch = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $view);
} else {
    $query = "SELECT * FROM student_submissions WHERE guide_name = ?";
    $stmt = $conn->prepare($query);
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
        textarea { resize: none; }
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
                <a href="staff_dashboard.php?view=overview" class="sidebar-link <?php echo $view === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="staff_dashboard.php?view=bca" class="sidebar-link <?php echo $view === 'bca' ? 'active' : ''; ?>">BCA Students</a>
                <a href="staff_dashboard.php?view=mca" class="sidebar-link <?php echo $view === 'mca' ? 'active' : ''; ?>">MCA Students</a>
                <a href="pdf_generator.php?course=<?php echo strtoupper($view); ?>" class="sidebar-link" style="color: #00d4ff;">Download Report</a>
            </aside>

            <main class="dashboard-main">
                <h2 class="section-heading"><?php echo ($view === 'overview') ? "My Students" : strtoupper($view) . " Students"; ?></h2>

                <div class="table-container">
                    <table class="glass-table">
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Student & Project</th>
                                <th>R1</th>
                                <th>R2</th>
                                <th>R3</th>
                                <th>R4</th>
                                <th>R5</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                                        <span class="project-subtext"><?php echo htmlspecialchars($row['project_title']); ?></span>
                                    </td>
                                    <td title="Notes: <?php echo htmlspecialchars($row['r1_notes'] ?? 'None'); ?>"><?php echo $row['r1_marks'] ?: '-'; ?></td>
                                    <td title="Notes: <?php echo htmlspecialchars($row['r2_notes'] ?? 'None'); ?>"><?php echo $row['r2_marks'] ?: '-'; ?></td>
                                    <td title="Notes: <?php echo htmlspecialchars($row['r3_notes'] ?? 'None'); ?>"><?php echo $row['r3_marks'] ?: '-'; ?></td>
                                    <td title="Notes: <?php echo htmlspecialchars($row['r4_notes'] ?? 'None'); ?>"><?php echo $row['r4_marks'] ?: '-'; ?></td>
                                    <td title="Notes: <?php echo htmlspecialchars($row['r5_notes'] ?? 'None'); ?>"><?php echo $row['r5_marks'] ?: '-'; ?></td>
                                    <td>
                                        <button class="btn-view btn-update" 
                                            data-reg="<?php echo $row['reg_no']; ?>" 
                                            data-name="<?php echo $row['student_name']; ?>"
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
            <h3>Update Progress</h3>
            <form method="POST" action="staff_dashboard.php?view=<?php echo $view; ?>" class="form-glass modal-form">
                <input type="hidden" name="reg_no" id="modalRegNo">
                
                <div class="form-group">
                    <p style="margin: 0; font-size: 0.9em; color: #ccc;">Student: <span id="dispName" style="color: white; font-weight: bold;"></span></p>
                    <p style="margin: 5px 0 15px 0; font-size: 0.85em; color: #00d4ff;">Project: <span id="dispProject"></span></p>
                </div>
                
                <div class="form-group">
                    <label>Review Phase</label>
                    <select name="review_phase" required>
                        <option value="r1">Review 1 </option>
                        <option value="r2">Review 2 </option>
                        <option value="r3">Review 3 </option>
                        <option value="r4">Review 4 </option>
                        <option value="r5">Review 5 </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Marks (0-100)</label>
                    <input type="number" name="marks" min="0" max="100" required>
                </div>

                <div class="form-group">
                    <label>Remarks / Feedback</label>
                    <textarea name="notes" rows="3" placeholder="Enter feedback for this phase..."></textarea>
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
                document.getElementById('dispProject').innerText = btn.getAttribute('data-project');
                modal.classList.add('open');
            });
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => modal.classList.remove('open'));
        });

        window.onclick = (e) => { if (e.target == modal) modal.classList.remove('open'); }
    </script>
</body>
</html>