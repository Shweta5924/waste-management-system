<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

// Stats Logic - Reading from VIEW 'waste_analytics' now
$today = date('Y-m-d');
$total_waste_today = $pdo->query("SELECT SUM(total_waste) FROM waste_analytics WHERE date = '$today'")->fetchColumn() ?: 0;
$avg_segregation = $pdo->query("SELECT AVG(segregation_percentage) FROM waste_analytics")->fetchColumn() ?: 0;
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();

// Best Performing Ward
$best_ward = $pdo->query("SELECT area_name, AVG(segregation_percentage) as avg_seg 
                          FROM waste_analytics 
                          GROUP BY ward_id 
                          ORDER BY avg_seg DESC LIMIT 1")->fetch();

// Worst Performing Ward
$worst_ward = $pdo->query("SELECT area_name, AVG(segregation_percentage) as avg_seg 
                           FROM waste_analytics 
                           GROUP BY ward_id 
                           ORDER BY avg_seg ASC LIMIT 1")->fetch();

// Chart Data (Last 7 Days)
$chart_sql = "SELECT date, SUM(total_waste) as daily_total FROM waste_analytics 
              WHERE date >= DATE(NOW()) - INTERVAL 7 DAY 
              GROUP BY date ORDER BY date ASC";
$chart_data = $pdo->query($chart_sql)->fetchAll();

// Ward Wise Analytics
$ward_analytics = $pdo->query("SELECT area_name, SUM(total_waste) as total_kg, AVG(segregation_percentage) as avg_seg 
                               FROM waste_analytics 
                               GROUP BY ward_id 
                               ORDER BY total_kg DESC")->fetchAll();

$dates = json_encode(array_column($chart_data, 'date'));
$totals = json_encode(array_column($chart_data, 'daily_total'));

include '../includes/header.php';
?>

<div class="dashboard-head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; margin-top: 2rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem;"><i class='bx bxs-dashboard' style="color: var(--primary);"></i> Admin Dashboard</h2>
        <p style="margin-bottom: 0;">Overview of waste management performance.</p>
    </div>
    <span class="badge badge-success" style="font-size: 0.9rem; padding: 0.5rem 1rem;"><i class='bx bxs-calendar'></i> <?= date('l, d F Y') ?></span>
</div>

<!-- Stats Grid -->
<div class="dashboard-grid fade-in">
    <div class="stat-card">
        <span class="stat-label">Total Waste Today</span>
        <div class="stat-value"><?= number_format($total_waste_today, 1) ?> kg</div>
    </div>
    <div class="stat-card">
        <span class="stat-label">Avg Segregation %</span>
        <div class="stat-value"><?= number_format($avg_segregation, 1) ?>%</div>
    </div>
    <div class="stat-card">
        <span class="stat-label">Pending Complaints</span>
        <div class="stat-value" style="color: var(--danger);"><?= $pending_complaints ?></div>
    </div>
</div>

<div class="dashboard-grid fade-in" style="animation-delay: 0.1s;">
    <div class="card">
        <h3 style="margin-bottom: 1rem; color: var(--primary);">🏆 Top Performer</h3>
        <?php if ($best_ward): ?>
            <p class="stat-value" style="font-size: 1.75rem; color: var(--success);"><?= htmlspecialchars($best_ward['area_name']) ?></p>
            <span class="badge badge-success"><?= number_format($best_ward['avg_seg'], 1) ?>% Segregation</span>
        <?php else: ?>
            <p>No data yet.</p>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 1rem; color: var(--danger);">⚠️ Needs Attention</h3>
        <?php if ($worst_ward): ?>
            <p class="stat-value" style="font-size: 1.75rem; color: var(--danger);"><?= htmlspecialchars($worst_ward['area_name']) ?></p>
            <span class="badge badge-danger"><?= number_format($worst_ward['avg_seg'], 1) ?>% Segregation</span>
            <br><br>
            <?php if ($worst_ward['avg_seg'] < 60): ?>
                <div class="alert badge-warning" style="margin-top: 1rem; text-align: center;">
                    <strong>Suggestion:</strong><br>Conduct Awareness Campaign in <?= htmlspecialchars($worst_ward['area_name']) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No data yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<div class="card fade-in" style="margin-bottom: 2rem; animation-delay: 0.2s;">
    <h3>Weekly Waste Trend</h3>
    <div class="chart-container">
        <canvas id="wasteChart"></canvas>
    </div>
</div>

<div class="card fade-in" style="margin-bottom: 2rem; animation-delay: 0.25s;">
    <h3><i class='bx bxs-bar-chart-alt-2' style="color: var(--info);"></i> Ward-wise Waste Records</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Ward Name</th>
                    <th>Total Waste Collected (kg)</th>
                    <th>Avg Segregation %</th>
                    <th>Performance Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ward_analytics as $wa): ?>
                <tr>
                    <td><?= htmlspecialchars($wa['area_name']) ?></td>
                    <td style="font-weight: 600;"><?= number_format($wa['total_kg'], 1) ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 50px; height: 6px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?= $wa['avg_seg'] ?>%; height: 100%; background: var(--<?= $wa['avg_seg'] > 75 ? 'success' : ($wa['avg_seg'] > 50 ? 'warning' : 'danger') ?>);"></div>
                            </div>
                            <span><?= number_format($wa['avg_seg'], 1) ?>%</span>
                        </div>
                    </td>
                    <td>
                        <?php 
                            $grade = 'D';
                            if ($wa['avg_seg'] >= 90) $grade = 'A';
                            elseif ($wa['avg_seg'] >= 75) $grade = 'B';
                            elseif ($wa['avg_seg'] >= 60) $grade = 'C';
                        ?>
                        <span class="badge badge-<?= $grade === 'A' ? 'success' : ($grade === 'B' ? 'info' : ($grade === 'C' ? 'warning' : 'danger')) ?>">
                            Grade <?= $grade ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="dashboard-grid fade-in" style="animation-delay: 0.3s; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <a href="wards.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem;">
        <i class='bx bxs-map' style="font-size: 2rem;"></i><span>Manage Wards</span>
    </a>
    <a href="users.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem;">
        <i class='bx bxs-user-detail' style="font-size: 2rem;"></i><span>Manage Users</span>
    </a>
    <a href="vehicles.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem;">
        <i class='bx bxs-truck' style="font-size: 2rem;"></i><span>Fleet Status</span>
    </a>
    <a href="reports.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem; background: var(--secondary);">
        <i class='bx bxs-report' style="font-size: 2rem;"></i><span>Performance</span>
    </a>
    <a href="complaints.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem; background: #d97706;">
        <i class='bx bxs-megaphone' style="font-size: 2rem;"></i><span>Complaints</span>
    </a>
    <a href="settings.php" class="btn btn-primary" style="text-align: center; display: flex; flex-direction: column; align-items: center; padding: 1.5rem; gap: 0.5rem; background: #4b5563;">
        <i class='bx bxs-cog' style="font-size: 2rem;"></i><span>Settings</span>
    </a>
</div>

<script>
    const ctx = document.getElementById('wasteChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $dates ?>,
            datasets: [{
                label: 'Total Waste (kg)',
                data: <?= $totals ?>,
                borderColor: '#4F46E5',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(79, 70, 229, 0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
