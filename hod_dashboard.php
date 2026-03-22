<?php
session_start();
$username = $_SESSION['username'] ?? 'HOD';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | Project Management System</title>
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
                <span class="welcome-text">Welcome HOD<?php echo $username ? ', ' . htmlspecialchars($username) : ''; ?></span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="sidebar">
                <div class="sidebar-title">Menu</div>
                <a href="hod_dashboard.php?view=bca" class="sidebar-link">BCA</a>
                <a href="hod_dashboard.php?view=mca" class="sidebar-link">MCA</a>
                <a href="#" class="sidebar-link">View Staff</a>
                <a href="#" class="sidebar-link">Download Reports</a>
            </aside>

            <main class="dashboard-main">
                <?php
                $view = $_GET['view'] ?? 'overview';
                if ($view === 'bca'):
                ?>
                    <h2 class="section-heading">BCA Programme</h2>
                    <div class="card-grid">
                        <div class="year-card floating">
                            <h3>BCA 1st Year</h3>
                            <p>Sections:</p>
                            <div class="pill-row">
                                <span class="pill">Section A</span>
                                <span class="pill">Section B</span>
                            </div>
                        </div>
                        <div class="year-card floating">
                            <h3>BCA 2nd Year</h3>
                            <p>Sections:</p>
                            <div class="pill-row">
                                <span class="pill">Section A</span>
                                <span class="pill">Section B</span>
                            </div>
                        </div>
                        <div class="year-card floating">
                            <h3>BCA 3rd Year</h3>
                            <p>Sections:</p>
                            <div class="pill-row">
                                <span class="pill">Section A</span>
                                <span class="pill">Section B</span>
                            </div>
                        </div>
                    </div>
                <?php elseif ($view === 'mca'): ?>
                    <h2 class="section-heading">MCA Programme</h2>
                    <div class="card-grid">
                        <div class="year-card floating">
                            <h3>MCA 1st Year</h3>
                            <p>Sections:</p>
                            <div class="pill-row">
                                <span class="pill">Section A</span>
                                <span class="pill">Section B</span>
                            </div>
                        </div>
                        <div class="year-card floating">
                            <h3>MCA 2nd Year</h3>
                            <p>Sections:</p>
                            <div class="pill-row">
                                <span class="pill">Section A</span>
                                <span class="pill">Section B</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <h2 class="section-heading">HOD Overview</h2>
                    <p class="muted-text">Use the left menu to navigate between BCA, MCA, staff, students, and reports.</p>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>

