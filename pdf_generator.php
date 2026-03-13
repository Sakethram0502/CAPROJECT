<?php
require_once __DIR__ . '/db.php';
session_start();

$course = $_GET['course'] ?? ''; // BCA, MCA, or ALL
$format = $_GET['format'] ?? 'pdf';
$staff_name = $_SESSION['staff_name'] ?? '';

if (!$course || !$staff_name) { die('Unauthorized Access'); }

// --- DYNAMIC SQL LOGIC ---
if ($course === 'ALL') {
    // Fetch everything for this staff member
    $sql = "SELECT * FROM student_submissions WHERE guide_name = ? ORDER BY branch, section, reg_no";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $staff_name);
} else {
    // Fetch specific branch only
    $sql = "SELECT * FROM student_submissions WHERE branch = ? AND guide_name = ? ORDER BY section, reg_no";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $course, $staff_name);
}

$stmt->execute();
$result = $stmt->get_result();
$reportData = [];
while ($row = $result->fetch_assoc()) { $reportData[] = $row; }
$stmt->close();

// --- EXCEL DOWNLOAD ---
if ($format === 'excel') {
    $filename = "Master_Project_Report_" . date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    echo "Branch\tReg No\tName\tDomain\tR1 Marks\tR1 Remarks\tR2 Marks\tR2 Remarks\tR3 Marks\tR3 Remarks\tR4 Marks\tR4 Remarks\tR5 Marks\tR5 Remarks\n";
    foreach ($reportData as $row) {
        echo $row['branch']."\t".$row['reg_no']."\t".$row['student_name']."\t".$row['domain']."\t".
             ($row['r1_marks']??'0')."\t".($row['r1_notes']??'-')."\t".
             ($row['r2_marks']??'0')."\t".($row['r2_notes']??'-')."\t".
             ($row['r3_marks']??'0')."\t".($row['r3_notes']??'-')."\t".
             ($row['r4_marks']??'0')."\t".($row['r4_notes']??'-')."\t".
             ($row['r5_marks']??'0')."\t".($row['r5_notes']??'-')."\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Master_Project_Report_<?php echo date('d-m-Y'); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 15px; }
        .header { text-align: center; border-bottom: 2px solid #0066ff; padding-bottom: 10px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #0066ff; color: white; padding: 6px; border: 1px solid #ddd; }
        td { padding: 5px; border: 1px solid #ddd; vertical-align: top; word-wrap: break-word; }
        .notes { font-size: 8px; color: #777; font-style: italic; display: block; border-top: 1px solid #eee; margin-top: 2px; }
        .branch-tag { font-weight: bold; color: #0066ff; font-size: 9px; }
        @media print { @page { size: landscape; margin: 5mm; } }
    </style>
    <script>
        window.onload = function() {
            window.print();
            setTimeout(function() { window.close(); }, 1500);
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>VFSTR - Department of Computer Applications</h1>
        <h2>Master Project Progress Report (BCA & MCA)</h2>
        <p>Faculty Guide: <?php echo htmlspecialchars($staff_name); ?> | Generated: <?php echo date('d-M-Y H:i'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">Branch</th>
                <th style="width: 70px;">Reg No</th>
                <th style="width: 90px;">Student Name</th>
                <th>Domain & Title</th>
                <th>R1 (M/R)</th><th>R2 (M/R)</th><th>R3 (M/R)</th><th>R4 (M/R)</th><th>R5 (M/R)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportData as $row): ?>
            <tr>
                <td class="branch-tag"><?php echo $row['branch']; ?></td>
                <td><strong><?php echo $row['reg_no']; ?></strong></td>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['domain']); ?></strong>: <?php echo htmlspecialchars($row['project_title']); ?></td>
                <td><?php echo $row['r1_marks']?:'0'; ?><span class="notes"><?php echo htmlspecialchars($row['r1_notes']?:'-'); ?></span></td>
                <td><?php echo $row['r2_marks']?:'0'; ?><span class="notes"><?php echo htmlspecialchars($row['r2_notes']?:'-'); ?></span></td>
                <td><?php echo $row['r3_marks']?:'0'; ?><span class="notes"><?php echo htmlspecialchars($row['r3_notes']?:'-'); ?></span></td>
                <td><?php echo $row['r4_marks']?:'0'; ?><span class="notes"><?php echo htmlspecialchars($row['r4_notes']?:'-'); ?></span></td>
                <td><?php echo $row['r5_marks']?:'0'; ?><span class="notes"><?php echo htmlspecialchars($row['r5_notes']?:'-'); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>