<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$type = $_GET['type'] ?? 'daily';
$month = $_GET['month'] ?? date('Y-m');

if ($type === 'monthly') {
    $sql = "SELECT ward_id, ward_number, area_name, 
                   SUM(wet_waste) as total_wet, 
                   SUM(dry_waste) as total_dry, 
                   SUM(mixed_waste) as total_mixed,
                   AVG(segregation_percentage) as avg_seg
            FROM waste_analytics 
            WHERE DATE_FORMAT(date, '%Y-%m') = ? 
            GROUP BY ward_id 
            ORDER BY ward_number ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$month]);
} else {
    $date = $_GET['date'] ?? date('Y-m-d');
    $sql = "SELECT * FROM waste_analytics WHERE date = ? ORDER BY ward_number ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
}

$report_data = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1>Performance Reports</h1>
            <p>Analyze waste collection data by date or month.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="?type=daily" class="btn <?= $type === 'daily' ? 'btn-primary' : 'btn-outline' ?>">Daily</a>
            <a href="?type=monthly" class="btn <?= $type === 'monthly' ? 'btn-primary' : 'btn-outline' ?>">Monthly</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card glass-card" style="margin-bottom: 2rem;">
        <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
            <input type="hidden" name="type" value="<?= $type ?>">
            <?php if ($type === 'monthly'): ?>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Select Month</label>
                    <input type="month" name="month" class="form-control" value="<?= $month ?>" onchange="this.form.submit()">
                </div>
            <?php else: ?>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Select Date</label>
                    <input type="date" name="date" class="form-control" value="<?= $date ?? date('Y-m-d') ?>" onchange="this.form.submit()">
                </div>
            <?php endif; ?>
            <a href="export.php?type=<?= $type ?>&val=<?= $type === 'monthly' ? $month : ($date ?? date('Y-m-d')) ?>" class="btn btn-success">
                <i class='bx bxs-file-export'></i> Export PDF
            </a>
        </form>
    </div>

    <div class="card glass-card" style="padding: 0;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ward</th>
                        <th>Wet (kg)</th>
                        <th>Dry (kg)</th>
                        <th>Mixed (kg)</th>
                        <th>Total (kg)</th>
                        <th>Avg Segregation</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($report_data as $row): 
                        $total = ($type === 'monthly') ? ($row['total_wet'] + $row['total_dry'] + $row['total_mixed']) : $row['total_waste'];
                        $grand_total += $total;
                        $seg = $row['avg_seg'] ?? $row['segregation_percentage'];
                    ?>
                    <tr>
                        <td><strong><?= $row['ward_number'] ?></strong><br><small><?= $row['area_name'] ?></small></td>
                        <td><?= number_format($row['total_wet'] ?? $row['wet_waste'], 1) ?></td>
                        <td><?= number_format($row['total_dry'] ?? $row['dry_waste'], 1) ?></td>
                        <td><?= number_format($row['total_mixed'] ?? $row['mixed_waste'], 1) ?></td>
                        <td style="font-weight: 700;"><?= number_format($total, 1) ?></td>
                        <td><?= number_format($seg, 1) ?>%</td>
                        <td>
                            <?php 
                                $grade = 'D';
                                if ($seg >= 90) $grade = 'A';
                                elseif ($seg >= 75) $grade = 'B';
                                elseif ($seg >= 60) $grade = 'C';
                            ?>
                            <span class="badge badge-<?= $grade === 'A' ? 'success' : ($grade === 'B' ? 'info' : ($grade === 'C' ? 'warning' : 'danger')) ?>">
                                <?= $grade ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($report_data)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 3rem;">No data available for this period.</td></tr>
                    <?php else: ?>
                        <tr style="background: var(--border); font-weight: 800;">
                            <td colspan="4" style="text-align: right;">GRAND TOTAL:</td>
                            <td><?= number_format($grand_total, 1) ?> kg</td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
