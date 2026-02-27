<?php
session_start();
$username = $_SESSION['staff_name'] ?? 'Staff';

// Dummy data for allocated students
$students = [
    [
        'reg_no' => 'BCA101',
        'name' => 'Ravi Kumar',
        'branch' => 'BCA',
        'year' => '1st Year',
        'section' => 'A',
        'project' => 'Student Management System',
        'review' => 'Review 1',
        'marks' => '80'
    ],
    [
        'reg_no' => 'BCA201',
        'name' => 'Sita Devi',
        'branch' => 'BCA',
        'year' => '2nd Year',
        'section' => 'B',
        'project' => 'Online Examination System',
        'review' => 'Review 2',
        'marks' => 'Pending'
    ],
    [
        'reg_no' => 'MCA101',
        'name' => 'Anil Rao',
        'branch' => 'MCA',
        'year' => '1st Year',
        'section' => 'A',
        'project' => 'Library Automation System',
        'review' => 'Review 0',
        'marks' => '75'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Project Management System</title>
    <link rel="stylesheet" href="style.css">
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
                <span class="welcome-text">Welcome <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <main class="dashboard-main full-width">
            <h2 class="section-heading">My Allocated Students</h2>

            <div class="table-container">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>Reg Number</th>
                            <th>Student Name</th>
                            <th>Branch</th>
                            <th>Year</th>
                            <th>Section</th>
                            <th>Project Title</th>
                            <th>Review</th>
                            <th>Marks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                                <td><?php echo htmlspecialchars($row['year']); ?></td>
                                <td><?php echo htmlspecialchars($row['section']); ?></td>
                                <td><?php echo htmlspecialchars($row['project']); ?></td>
                                <td><?php echo htmlspecialchars($row['review']); ?></td>
                                <td><?php echo htmlspecialchars($row['marks']); ?></td>
                                <td>
                                    <button 
                                        class="btn-view btn-update" 
                                        data-reg="<?php echo htmlspecialchars($row['reg_no']); ?>" 
                                        data-name="<?php echo htmlspecialchars($row['name']); ?>">
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

    <!-- Modal for updating review and marks -->
    <div class="modal-overlay" id="updateModal">
        <div class="modal-card floating">
            <h3>Update Review &amp; Marks</h3>
            <form class="form-glass modal-form">
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" id="modalRegNo" readonly>
                </div>
                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" id="modalStudentName" readonly>
                </div>
                <div class="form-group">
                    <label>Enter Review</label>
                    <textarea rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Enter Marks</label>
                    <input type="number" min="0" max="100" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-link modal-close">Cancel</button>
                    <button type="submit" class="btn-gradient">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('updateModal');
        const closeBtns = document.querySelectorAll('.modal-close');
        const updateButtons = document.querySelectorAll('.btn-update');
        const regNoField = document.getElementById('modalRegNo');
        const nameField = document.getElementById('modalStudentName');

        updateButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                regNoField.value = btn.getAttribute('data-reg');
                nameField.value = btn.getAttribute('data-name');
                modal.classList.add('open');
            });
        });

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.remove('open');
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('open');
            }
        });
    </script>
</body>
</html>

