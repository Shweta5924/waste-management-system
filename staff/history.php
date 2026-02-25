<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// Get assigned ward
$stmt = $pdo->prepare("SELECT ward_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$ward_id = $user['ward_id'];

// Reading from VIEW waste_analytics
if ($ward_id) {
    $stmt = $pdo->prepare("SELECT * FROM waste_analytics WHERE ward_id = ? ORDER BY date DESC LIMIT 100");
    $stmt->execute([$ward_id]);
    $entries = $stmt->fetchAll();
} else {
    $entries = [];
}

include '../includes/header.php';
?>

<div class="dashboard-head" style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Collection History</h1>
        <p>Your previous waste entry records.</p>
    </div>
    <a href="entry.php" class="btn btn-primary"><i class='bx bx-plus'></i> Add New Entry</a>
</div>

<?php if (empty($entries)): ?>
    <div class="card" style="text-align: center; padding: 4rem;">
        <i class='bx bx-history' style="font-size: 4rem; color: var(--border);"></i>
        <h3 style="color: var(--text-muted); margin-top: 1rem;">No history found.</h3>
        <p>Once you start reporting daily waste, they will appear here.</p>
    </div>
<?php else: ?>
    <div class="card glass-card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Wet (kg)</th>
                        <th>Dry (kg)</th>
                        <th>Mixed (kg)</th>
                        <th>Total</th>
                        <th>Segregation %</th>
                        <th>Status</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= date('d M Y', strtotime($e['date'])) ?></td>
                        <td><i class='bx bxs-droplet' style="color: #3b82f6;"></i> <?= $e['wet_waste'] ?></td>
                        <td><i class='bx bxs-sun' style="color: #f59e0b;"></i> <?= $e['dry_waste'] ?></td>
                        <td><i class='bx bxs-component' style="color: #ef4444;"></i> <?= $e['mixed_waste'] ?></td>
                        <td><strong><?= number_format($e['total_waste'], 2) ?></strong></td>
                        <td><?= number_format($e['segregation_percentage'], 1) ?>%</td>
                        <td><span class="badge badge-success">COMPLETED</span></td>
                        <td>
                            <span class="badge badge-<?= $e['grade'] === 'A' ? 'success' : ($e['grade'] === 'B' || $e['grade'] === 'C' ? 'info' : 'danger') ?>">
                                <?= $e['grade'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
