<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$msg_type = 'success';
$user_id = $_SESSION['user_id'];

// Get assigned ward and vehicle
$stmt = $pdo->prepare("SELECT u.ward_id, w.ward_number, w.area_name FROM users u LEFT JOIN wards w ON u.ward_id = w.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$assigned_ward = $stmt->fetch();

$assigned_vehicle = null;
if ($assigned_ward && $assigned_ward['ward_id']) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE ward_id = ? LIMIT 1");
    $stmt->execute([$assigned_ward['ward_id']]);
    $assigned_vehicle = $stmt->fetch();
}

// Handle Vehicle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $status = $_POST['vehicle_status'];
    $vid = $_POST['vehicle_id'];
    $stmt = $pdo->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $vid])) {
        $message = "Vehicle status updated to $status.";
        $assigned_vehicle['status'] = $status; // Update local state
    }
}

// Handle Waste Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
    $ward_id = $_POST['ward_id'];
    $date = $_POST['date'];
    $wet = $_POST['wet'];
    $dry = $_POST['dry'];
    $mixed = $_POST['mixed'];
    $status = 'Completed';

    try {
        $stmt = $pdo->prepare("INSERT INTO waste_entries (ward_id, date, wet_waste, dry_waste, mixed_waste, collection_status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$ward_id, $date, $wet, $dry, $mixed, $status])) {
            $message = "Daily collection record submitted successfully!";
        }
    } catch (PDOException $e) {
        $msg_type = 'danger';
        if ($e->getCode() == 23000) {
            $message = "Error: Data for this ward on the selected date already exists.";
        } else {
            $message = "Error: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem;">
        <h1>Staff Portal</h1>
        <p>Field Operations Management</p>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?>" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Left: Ward & Vehicle Info -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div class="card glass-card">
                <h3 style="margin-bottom: 1.5rem;"><i class='bx bxs-map-pin' style="color: var(--primary);"></i> Assigned Ward</h3>
                <?php if ($assigned_ward && $assigned_ward['ward_id']): ?>
                    <div style="padding: 1rem; background: rgba(79, 70, 229, 0.05); border-radius: 0.5rem; border: 1px solid var(--border);">
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Ward Number</p>
                        <p style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;"><?= htmlspecialchars($assigned_ward['ward_number']) ?></p>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Area</p>
                        <p style="font-size: 1rem; color: var(--text-main); margin: 0;"><?= htmlspecialchars($assigned_ward['area_name']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="badge badge-danger">Not Assigned to any Ward</div>
                <?php endif; ?>
            </div>

            <div class="card glass-card">
                <h3 style="margin-bottom: 1.5rem;"><i class='bx bxs-truck' style="color: var(--secondary);"></i> Vehicle Status</h3>
                <?php if ($assigned_vehicle): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <p style="margin: 0; font-size: 0.85rem; font-weight: 600;">Vehicle: <span style="font-weight: 800;"><?= htmlspecialchars($assigned_vehicle['vehicle_number']) ?></span></p>
                        <div style="margin-top: 0.5rem;">
                             <span class="badge badge-<?= $assigned_vehicle['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= strtoupper($assigned_vehicle['status']) ?>
                             </span>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="update_vehicle" value="1">
                        <input type="hidden" name="vehicle_id" value="<?= $assigned_vehicle['id'] ?>">
                        <div class="form-group">
                            <label class="form-label">Update Status</label>
                            <select name="vehicle_status" class="form-control" onchange="this.form.submit()">
                                <option value="active" <?= $assigned_vehicle['status'] === 'active' ? 'selected' : '' ?>>Active / Ready</option>
                                <option value="maintenance" <?= $assigned_vehicle['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance / Breakdown</option>
                            </select>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No vehicle assigned to your ward.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Daily Entry Form -->
        <div class="card glass-card">
            <h3 style="margin-bottom: 1.5rem;"><i class='bx bxs-edit' style="color: var(--accent);"></i> Daily Waste Collection Report</h3>
            
            <?php if (!$assigned_ward || !$assigned_ward['ward_id']): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class='bx bx-lock-alt' style="font-size: 3rem; color: var(--border);"></i>
                    <p>You must be assigned to a ward by the administrator to enter waste data.</p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="submit_entry" value="1">
                    <input type="hidden" name="ward_id" value="<?= $assigned_ward['ward_id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Collection Date</label>
                        <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Wet Waste (kg)</label>
                            <div style="position: relative;">
                                <i class='bx bxs-droplet' style="position: absolute; left: 1rem; top: 1rem; color: #3b82f6;"></i>
                                <input type="number" step="0.01" name="wet" class="form-control" required placeholder="0.00" style="padding-left: 2.5rem;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dry Waste (kg)</label>
                            <div style="position: relative;">
                                <i class='bx bxs-sun' style="position: absolute; left: 1rem; top: 1rem; color: #f59e0b;"></i>
                                <input type="number" step="0.01" name="dry" class="form-control" required placeholder="0.00" style="padding-left: 2.5rem;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mixed Waste (kg)</label>
                            <div style="position: relative;">
                                <i class='bx bxs-component' style="position: absolute; left: 1rem; top: 1rem; color: #ef4444;"></i>
                                <input type="number" step="0.01" name="mixed" class="form-control" required placeholder="0.00" style="padding-left: 2.5rem;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; padding: 1.5rem; border: 1px dashed var(--border); border-radius: 0.5rem; text-align: center;">
                        <p style="font-size: 0.9rem; margin-bottom: 1rem;">By submitting this data, you mark the collection for ward <strong><?= $assigned_ward['ward_number'] ?></strong> as <strong>COMPLETED</strong> for today.</p>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            <i class='bx bx-check-double'></i> Submit & Mark Completed
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom: Quick History -->
    <div style="margin-top: 4rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Recent Submissions</h2>
            <a href="history.php" class="btn btn-outline btn-sm">View All History</a>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Wet (kg)</th>
                        <th>Dry (kg)</th>
                        <th>Mixed (kg)</th>
                        <th>Status</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($assigned_ward) {
                        $stmt = $pdo->prepare("SELECT * FROM waste_analytics WHERE ward_id = ? ORDER BY date DESC LIMIT 5");
                        $stmt->execute([$assigned_ward['ward_id']]);
                        $recent = $stmt->fetchAll();
                        foreach ($recent as $r):
                    ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
                            <td><?= $r['wet_waste'] ?></td>
                            <td><?= $r['dry_waste'] ?></td>
                            <td><?= $r['mixed_waste'] ?></td>
                            <td><span class="badge badge-success">COMPLETED</span></td>
                            <td><span class="badge badge-info"><?= $r['grade'] ?></span></td>
                        </tr>
                    <?php 
                        endforeach; 
                        if (empty($recent)) echo '<tr><td colspan="6" style="text-align:center;">No submissions yet.</td></tr>';
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center;">Assign a ward to see history.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
