<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, these would be in a 'settings' table. 
    // For now, we'll simulate saving system configurations.
    $message = "System settings updated successfully!";
}

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem;">
        <h1>System Settings</h1>
        <p>Configure global application parameters and thresholds.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?> fade-in" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="card glass-card">
            <h3><i class='bx bx-cog'></i> General Configuration</h3>
            <form method="POST" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Municipal Corporation Name</label>
                    <input type="text" class="form-control" value="City Waste Management Authority">
                </div>
                
                <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Target Segregation % (Grade A)</label>
                        <input type="number" class="form-control" value="90">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Critical Alert % (Grade D)</label>
                        <input type="number" class="form-control" value="60">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Admin Email for Alerts</label>
                    <input type="email" class="form-control" value="admin@waste.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Daily Reporting Deadline</label>
                    <input type="time" class="form-control" value="20:00">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Update Settings</button>
            </form>
        </div>

        <div class="card glass-card">
            <h3><i class='bx bx-info-circle'></i> System Info</h3>
            <ul style="list-style: none; padding: 0; margin-top: 1.5rem;">
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
                    <span>Version</span>
                    <span class="badge badge-info">v2.1.0-stable</span>
                </li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
                    <span>PHP Version</span>
                    <span><?= phpversion() ?></span>
                </li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
                    <span>DB Engine</span>
                    <span>MySQL / InnoDB</span>
                </li>
                <li style="padding: 10px 0; display: flex; justify-content: space-between;">
                    <span>Server Time</span>
                    <span><?= date('H:i:s') ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
