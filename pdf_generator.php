<?php
// PDF Generator for CA Project Management System
require_once __DIR__ . '/db.php';

// Get parameters
$course = $_GET['course'] ?? '';
$year = $_GET['year'] ?? '';

if (!$course || !$year) {
    die('Invalid parameters');
}

// Fetch report data
$sql = "
    SELECT 
        s.reg_no,
        s.student_name,
        s.section,
        COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed_reviews
    FROM students s
    LEFT JOIN projects p ON p.student_id = s.student_id
    LEFT JOIN reviews r ON r.project_id = p.project_id
    WHERE s.course = ? AND s.year = ?
    GROUP BY s.student_id, s.reg_no, s.student_name, s.section
    ORDER BY s.section, s.reg_no
";

$reportData = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('si', $course, $year);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $reportData[] = $row;
        }
    }
    $stmt->close();
}

// Generate PDF using simple HTML to PDF approach
// For better PDF generation, you can install TCPDF or FPDF library
// This uses a simple approach that works with most browsers

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report - <?php echo htmlspecialchars($course); ?> Year <?php echo htmlspecialchars($year); ?></title>
    <style>
        @media print {
            @page {
                margin: 20mm;
                size: A4;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0066ff;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0066ff;
            margin: 0;
            font-size: 28px;
        }
        .header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        .report-info {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-left: 4px solid #0066ff;
        }
        .report-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(135deg, #0066ff, #00d4ff);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0052cc;
        }
        td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #f0f7ff;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #00ff88;
            color: #000;
        }
        .badge-pending {
            background: #ff4444;
            color: #fff;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 2px solid #eee;
            padding-top: 15px;
        }
    </style>
    <script>
        // Auto-trigger print dialog for PDF download
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 250);
        }
        
        // Handle print dialog close
        window.onafterprint = function() {
            // Optional: close window after printing (uncomment if needed)
            // window.close();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>CA – Department of Computer Applications</h1>
        <h2>Vignan University</h2>
        <h2>BCA & MCA Project Management System</h2>
    </div>
    
    <div class="report-info">
        <p><strong>Course:</strong> <?php echo htmlspecialchars($course); ?></p>
        <p><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
        <p><strong>Generated Date:</strong> <?php echo date('d F Y, h:i A'); ?></p>
        <p><strong>Total Students:</strong> <?php echo count($reportData); ?></p>
    </div>
    
    <?php if (!empty($reportData)): ?>
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Registration Number</th>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Completed Reviews</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                foreach ($reportData as $row): 
                ?>
                    <tr>
                        <td><?php echo $sno++; ?></td>
                        <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['section']); ?></td>
                        <td>
                            <span class="badge <?php echo (int)$row['completed_reviews'] > 0 ? 'badge-success' : 'badge-pending'; ?>">
                                <?php echo (int)$row['completed_reviews']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: #999; margin-top: 40px;">No data available for the selected criteria.</p>
    <?php endif; ?>
    
    <div class="footer">
        <p>This is a computer-generated report from CA Project Management System</p>
        <p>Vignan University - Department of Computer Applications</p>
    </div>
</body>
</html>
