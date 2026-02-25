<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="waste_performance_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Ward Number', 'Area', 'Wet Waste', 'Dry Waste', 'Mixed Waste', 'Total Waste', 'Segregation %', 'Grade']);

// Querying the VIEW directly
$stmt = $pdo->query("SELECT date, ward_number, area_name, wet_waste, dry_waste, mixed_waste, total_waste, segregation_percentage, grade 
                     FROM waste_analytics 
                     ORDER BY date DESC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
