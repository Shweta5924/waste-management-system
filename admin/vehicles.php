<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$msg_type = 'success';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Vehicle removed from fleet!";
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number = $_POST['vehicle_number'];
    $driver_name = $_POST['driver_name'];
    $driver_phone = $_POST['driver_phone'];
    $ward_id = $_POST['ward_id'] ?: null;
    $status = $_POST['status'];
    $id = $_POST['vehicle_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE vehicles SET vehicle_number=?, driver_name=?, driver_phone=?, ward_id=?, status=? WHERE id=?");
        $params = [$vehicle_number, $driver_name, $driver_phone, $ward_id, $status, $id];
    } else {
        $stmt = $pdo->prepare("INSERT INTO vehicles (vehicle_number, driver_name, driver_phone, ward_id, status) VALUES (?, ?, ?, ?, ?)");
        $params = [$vehicle_number, $driver_name, $driver_phone, $ward_id, $status];
    }

    try {
        if ($stmt->execute($params)) {
            $message = $id ? "Vehicle updated successfully!" : "Vehicle added successfully!";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = 'danger';
    }
}

// Fetch Data
$vehicles = $pdo->query("SELECT v.*, w.ward_number, w.area_name 
                         FROM vehicles v 
                         LEFT JOIN wards w ON v.ward_id = w.id 
                         ORDER BY v.id DESC")->fetchAll();
$wards = $pdo->query("SELECT * FROM wards ORDER BY ward_number ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem;">
        <h1>Waste Collection Fleet</h1>
        <p>Monitor vehicle status and manage driver assignments.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?> fade-in" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add/Edit Form -->
        <div class="card glass-card">
            <h3 id="form-title" style="margin-bottom: 1.5rem;"><i class='bx bx-bus'></i> Add/Edit Vehicle</h3>
            <form method="POST" id="vehicle-form">
                <input type="hidden" name="vehicle_id" id="vehicle_id">
                
                <div class="form-group">
                    <label class="form-label">Vehicle Number</label>
                    <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" required placeholder="TN-01-AX-9999">
                </div>

                <div class="form-group">
                    <label class="form-label">Driver Name</label>
                    <input type="text" name="driver_name" id="driver_name" class="form-control" required placeholder="Driver Name">
                </div>

                <div class="form-group">
                    <label class="form-label">Driver Contact</label>
                    <input type="text" name="driver_phone" id="driver_phone" class="form-control" required placeholder="9876543210">
                </div>

                <div class="form-group">
                    <label class="form-label">Assign to Ward</label>
                    <select name="ward_id" id="ward_id" class="form-control">
                        <option value="">-- No Ward (Standby) --</option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= $w['ward_number'] ?> - <?= $w['area_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Vehicle Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active">Active (On Road)</option>
                        <option value="maintenance">Maintenance (Breakdown)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Vehicle</button>
                    <button type="reset" class="btn btn-outline" onclick="resetForm()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Vehicles List -->
        <div class="card glass-card" style="padding: 0;">
            <div style="padding: 1.5rem;">
                <h3><i class='bx bx-list-ul'></i> Fleet Overview</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Jurisdiction</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($v['vehicle_number'] ?? '') ?></td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($v['driver_name'] ?? '') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($v['driver_phone'] ?? '') ?></small>
                            </td>
                            <td>
                                <?php if ($v['ward_number']): ?>
                                    <small><?= $v['ward_number'] ?> - <?= $v['area_name'] ?></small>
                                <?php else: ?>
                                    <span class="badge badge-warning">Standby</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $v['status'] === 'active' ? 'success' : 'danger' ?>">
                                    <?= strtoupper($v['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-outline" style="padding: 2px 6px;" onclick="editVehicle(<?= htmlspecialchars(json_encode($v)) ?>)">
                                        <i class='bx bx-edit-alt'></i>
                                    </button>
                                    <a href="?delete=<?= $v['id'] ?>" class="btn btn-sm btn-outline" style="padding: 2px 6px; color: var(--danger);" onclick="return confirm('Remove vehicle?')">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehicles)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem;">No vehicles in fleet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editVehicle(vehicle) {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-edit'></i> Edit Vehicle";
    document.getElementById('vehicle_id').value = vehicle.id;
    document.getElementById('vehicle_number').value = vehicle.vehicle_number;
    document.getElementById('driver_name').value = vehicle.driver_name;
    document.getElementById('driver_phone').value = vehicle.driver_phone;
    document.getElementById('ward_id').value = vehicle.ward_id || "";
    document.getElementById('status').value = vehicle.status;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-bus'></i> Add Vehicle";
    document.getElementById('vehicle_id').value = "";
}
</script>

<?php include '../includes/footer.php'; ?>
