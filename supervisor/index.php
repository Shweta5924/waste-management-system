<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$supervisor_id = $_SESSION['user_id'];
$message = '';
$msg_type = 'success';

// Handle Waste Entry Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_entry_status'])) {
    $entry_id = $_POST['entry_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE waste_entries SET approval_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $entry_id])) {
        $message = "Entry #$entry_id marked as $status.";
    }
}

// Handle Complaint Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $resolution_image = null;

    if ($status === 'Resolved' && isset($_FILES['resolution_image']) && $_FILES['resolution_image']['error'] === 0) {
        $upload_dir = '../assets/uploads/resolutions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['resolution_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('res_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['resolution_image']['tmp_name'], $target_file)) {
            // Store relative path for frontend access
            $resolution_image = 'assets/uploads/resolutions/' . $file_name;
        }
    }

    if ($resolution_image) {
        $stmt = $pdo->prepare("UPDATE complaints SET status = ?, resolution_image_path = ? WHERE id = ?");
        $success = $stmt->execute([$status, $resolution_image, $complaint_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $complaint_id]);
    }

    if ($success) {
        $message = "Complaint #$complaint_id status updated to $status.";
    }
}

// Get Assigned Wards
$wards_stmt = $pdo->prepare("SELECT * FROM wards WHERE supervisor_id = ?");
$wards_stmt->execute([$supervisor_id]);
$my_wards = $wards_stmt->fetchAll();
$ward_ids = array_column($my_wards, 'id');

$entries = [];
$complaints = [];
$vehicles = [];
$stats = ['avg_seg' => 0, 'total_waste' => 0];

if (!empty($ward_ids)) {
    $placeholders = str_repeat('?,', count($ward_ids) - 1) . '?';
    
    // Recent Entries
    $entries = $pdo->prepare("SELECT * FROM waste_analytics WHERE ward_id IN ($placeholders) ORDER BY date DESC LIMIT 20");
    $entries->execute($ward_ids);
    $entries = $entries->fetchAll();

    // Complaints for My Wards
    $complaints = $pdo->prepare("SELECT c.*, w.ward_number FROM complaints c JOIN wards w ON c.ward_id = w.id WHERE c.ward_id IN ($placeholders) ORDER BY c.created_at DESC");
    $complaints->execute($ward_ids);
    $complaints = $complaints->fetchAll();

    // Vehicles for My Wards
    $vehicles = $pdo->prepare("SELECT * FROM vehicles WHERE ward_id IN ($placeholders)");
    $vehicles->execute($ward_ids);
    $vehicles = $vehicles->fetchAll();

    // Calc Stats
    if (!empty($entries)) {
        $stats['avg_seg'] = array_sum(array_column($entries, 'segregation_percentage')) / count($entries);
        $stats['total_waste'] = array_sum(array_column($entries, 'total_waste'));
    }
}

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 class="text-gradient">Supervisor Control Center</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
        </div>
        <div style="text-align: right;">
            <span class="badge badge-info"><?= date('l, M d, Y') ?></span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?> fade-in" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <i class='bx bx-check-circle'></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Overview Stats -->
    <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2.5rem;">
        <div class="stat-card">
            <span class="stat-label">My Wards</span>
            <div class="stat-value" style="font-size: 2rem;"><?= count($my_wards) ?></div>
            <i class='bx bxs-map' style="position: absolute; right: 1.5rem; bottom: 1.5rem; font-size: 2.5rem; opacity: 0.1;"></i>
        </div>
        <div class="stat-card">
            <span class="stat-label">Avg Segregation</span>
            <div class="stat-value" style="font-size: 2rem; color: var(--secondary);"><?= number_format($stats['avg_seg'], 1) ?>%</div>
            <i class='bx bxs-pie-chart-alt-2' style="position: absolute; right: 1.5rem; bottom: 1.5rem; font-size: 2.5rem; opacity: 0.1;"></i>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total Collected</span>
            <div class="stat-value" style="font-size: 2rem;"><?= number_format($stats['total_waste'], 0) ?> <small>kg</small></div>
            <i class='bx bxs-box' style="position: absolute; right: 1.5rem; bottom: 1.5rem; font-size: 2.5rem; opacity: 0.1;"></i>
        </div>
        <div class="stat-card">
            <span class="stat-label">Pending Complaints</span>
            <?php 
            $pending_c = count(array_filter($complaints, fn($c) => $c['status'] === 'Pending'));
            ?>
            <div class="stat-value" style="font-size: 2rem; color: <?= $pending_c > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;"><?= $pending_c ?></div>
            <i class='bx bxs-megaphone' style="position: absolute; right: 1.5rem; bottom: 1.5rem; font-size: 2.5rem; opacity: 0.1;"></i>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left: Waste Entries & Complaints -->
        <div style="display: flex; flex-direction: column; gap: 2.5rem;">
            
            <!-- Waste Approvals -->
            <section>
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class='bx bx-list-check' style="color: var(--primary);"></i> Daily Waste Validations
                </h3>
                <div class="table-container card glass-card" style="padding: 0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Ward</th>
                                <th>Segregation</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $e): ?>
                            <tr>
                                <td><?= date('M d', strtotime($e['date'])) ?></td>
                                <td style="font-weight: 600;"><?= $e['ward_number'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="badge badge-<?= $e['grade'] === 'A' ? 'success' : 'warning' ?>" style="padding: 2px 6px;"><?= $e['grade'] ?></span>
                                        <small><?= number_format($e['segregation_percentage'], 0) ?>%</small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $app_class = match($e['approval_status']) {
                                        'Approved' => 'success',
                                        'Rejected' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge badge-<?= $app_class ?>"><?= $e['approval_status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($e['approval_status'] === 'Pending'): ?>
                                        <div style="display: flex; gap: 5px;">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="update_entry_status" value="1">
                                                <input type="hidden" name="entry_id" value="<?= $e['id'] ?>">
                                                <input type="hidden" name="status" value="Approved">
                                                <button type="submit" class="btn btn-sm btn-success" style="padding: 4px 8px;"><i class='bx bx-check'></i></button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="update_entry_status" value="1">
                                                <input type="hidden" name="entry_id" value="<?= $e['id'] ?>">
                                                <input type="hidden" name="status" value="Rejected">
                                                <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px 8px;"><i class='bx bx-x'></i></button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">No actions</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($entries)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem;">No entries found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Complaints Tracking -->
            <section>
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class='bx bx-megaphone' style="color: var(--danger);"></i> Citizen Complaints
                </h3>
                <div class="dashboard-grid" style="grid-template-columns: 1fr; gap: 1rem;">
                    <?php foreach ($complaints as $c): ?>
                    <div class="card glass-card fade-in" style="padding: 1.5rem; border-left: 4px solid <?= $c['status'] === 'Pending' ? 'var(--danger)' : ($c['status'] === 'Resolved' ? 'var(--success)' : 'var(--info)') ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div>
                                <span class="badge badge-info" style="margin-bottom: 0.5rem;">Ward <?= $c['ward_number'] ?></span>
                                <h4 style="margin: 0;"><?= htmlspecialchars($c['citizen_name']) ?></h4>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($c['created_at'])) ?></small>
                            </div>
                            <form method="POST" enctype="multipart/form-data" id="statusForm_<?= $c['id'] ?>">
    <input type="hidden" name="update_complaint_status" value="1">
    <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
        <select name="status" class="form-control" style="font-size: 0.8rem; padding: 4px 8px; width: auto;" 
                onchange="handleStatusChange(this, <?= $c['id'] ?>)">
            <option value="Pending" <?= $c['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Resolved" <?= $c['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="Rejected" <?= $c['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <div id="resolution_upload_<?= $c['id'] ?>" style="display: none; text-align: right;">
            <label style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-bottom: 4px;">Upload Proof (Photo)</label>
            <input type="file" name="resolution_image" accept="image/*" class="form-control" style="font-size: 0.75rem; padding: 2px 5px;">
            <button type="submit" class="btn btn-sm btn-success" style="margin-top: 5px; padding: 4px 12px; font-size: 0.75rem;">Confirm Resolve</button>
        </div>
    </div>
</form>
</div>
<p style="margin-bottom: 1rem; color: var(--text-main);"><?= htmlspecialchars($c['complaint_text']) ?></p>
<div style="display: flex; gap: 1.5rem; align-items: center;">
    <?php if ($c['image_path']): ?>
        <a href="../<?= $c['image_path'] ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--primary); font-weight: 600;">
            <i class='bx bx-image'></i> Evidence
        </a>
    <?php endif; ?>
    <?php if ($c['resolution_image_path']): ?>
        <a href="../<?= $c['resolution_image_path'] ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--secondary); font-weight: 600;">
            <i class='bx bx-check-double'></i> Resolution Proof
        </a>
    <?php endif; ?>
</div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($complaints)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">No complaints in your wards.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Right: Ward Info & Vehicles -->
        <div style="display: flex; flex-direction: column; gap: 2.5rem;">
            
            <!-- Ward Summary -->
            <section>
                <h3 style="margin-bottom: 1.5rem;">My Jurisdictions</h3>
                <div class="card glass-card">
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($my_wards as $w): ?>
                        <li style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <span style="font-weight: 800; color: var(--primary);"><?= $w['ward_number'] ?></span>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--text-main);"><?= $w['area_name'] ?></p>
                            </div>
                            <div style="text-align: right;">
                                <small class="text-muted">Pop.</small>
                                <p style="margin:0; font-weight: 600;"><?= number_format($w['population']) ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <!-- Vehicles Fleet -->
            <section>
                <h3 style="margin-bottom: 1.5rem;">Fleet Status</h3>
                <div class="dashboard-grid" style="grid-template-columns: 1fr; gap: 1rem;">
                    <?php foreach ($vehicles as $v): ?>
                    <div class="card glass-card" style="padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0; font-size: 1rem;"><?= $v['vehicle_number'] ?></h4>
                                <small class="text-muted"><?= htmlspecialchars($v['driver_name']) ?></small>
                            </div>
                            <span class="badge badge-<?= $v['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= strtoupper($v['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($vehicles)): ?>
                    <p class="text-muted">No vehicles tracked in your wards.</p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
function handleStatusChange(select, id) {
    const uploadDiv = document.getElementById('resolution_upload_' + id);
    if (select.value === 'Resolved') {
        uploadDiv.style.display = 'block';
    } else {
        uploadDiv.style.display = 'none';
        select.form.submit();
    }
}
</script>
<?php include '../includes/footer.php'; ?>
