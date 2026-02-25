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
    $stmt = $pdo->prepare("DELETE FROM wards WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Ward deleted successfully!";
    } else {
        $message = "Error deleting ward (it might have linked entries).";
        $msg_type = 'danger';
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ward_number = $_POST['ward_number'];
    $area_name = $_POST['area_name'];
    $population = $_POST['population'];
    $supervisor_id = $_POST['supervisor_id'] ?: null;
    $id = $_POST['ward_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE wards SET ward_number=?, area_name=?, population=?, supervisor_id=? WHERE id=?");
        $params = [$ward_number, $area_name, $population, $supervisor_id, $id];
    } else {
        $stmt = $pdo->prepare("INSERT INTO wards (ward_number, area_name, population, supervisor_id) VALUES (?, ?, ?, ?)");
        $params = [$ward_number, $area_name, $population, $supervisor_id];
    }

    if ($stmt->execute($params)) {
        $message = $id ? "Ward updated successfully!" : "Ward added successfully!";
    } else {
        $message = "Error saving ward data.";
        $msg_type = 'danger';
    }
}

// Fetch Data
$supervisors = $pdo->query("SELECT * FROM users WHERE role = 'supervisor'")->fetchAll();
$wards = $pdo->query("SELECT w.*, u.name as supervisor_name FROM wards w LEFT JOIN users u ON w.supervisor_id = u.id ORDER BY w.ward_number ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem;">
        <h1>Ward Jurisdictions</h1>
        <p>Define administrative boundaries and assign supervisors.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?> fade-in" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add/Edit Form -->
        <div class="card glass-card">
            <h3 id="form-title" style="margin-bottom: 1.5rem;"><i class='bx bx-map-alt'></i> Add/Edit Ward</h3>
            <form method="POST" id="ward-form">
                <input type="hidden" name="ward_id" id="ward_id">
                
                <div class="form-group">
                    <label class="form-label">Ward Number</label>
                    <input type="text" name="ward_number" id="ward_number" class="form-control" required placeholder="W-01">
                </div>

                <div class="form-group">
                    <label class="form-label">Area Name</label>
                    <input type="text" name="area_name" id="area_name" class="form-control" required placeholder="Downtown Central">
                </div>

                <div class="form-group">
                    <label class="form-label">Population Estimate</label>
                    <input type="number" name="population" id="population" class="form-control" required placeholder="12500">
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Supervisor</label>
                    <select name="supervisor_id" id="supervisor_id" class="form-control">
                        <option value="">-- No Supervisor --</option>
                        <?php foreach ($supervisors as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= $sup['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Ward</button>
                    <button type="reset" class="btn btn-outline" onclick="resetForm()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Wards List -->
        <div class="card glass-card" style="padding: 0;">
            <div style="padding: 1.5rem;">
                <h3><i class='bx bx-list-ul'></i> Ward Registry</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ward #</th>
                            <th>Area Name</th>
                            <th>Population</th>
                            <th>Supervisor</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wards as $w): ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--primary);"><?= htmlspecialchars($w['ward_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($w['area_name'] ?? '') ?></td>
                            <td><?= number_format($w['population']) ?></td>
                            <td>
                                <?php if ($w['supervisor_name']): ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($w['supervisor_name'] ?? '') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-outline" style="padding: 2px 6px;" onclick="editWard(<?= htmlspecialchars(json_encode($w)) ?>)">
                                        <i class='bx bx-edit-alt'></i>
                                    </button>
                                    <a href="?delete=<?= $w['id'] ?>" class="btn btn-sm btn-outline" style="padding: 2px 6px; color: var(--danger);" onclick="return confirm('Delete ward?')">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($wards)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem;">No wards defined.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editWard(ward) {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-edit'></i> Edit Ward";
    document.getElementById('ward_id').value = ward.id;
    document.getElementById('ward_number').value = ward.ward_number;
    document.getElementById('area_name').value = ward.area_name;
    document.getElementById('population').value = ward.population;
    document.getElementById('supervisor_id').value = ward.supervisor_id || "";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-map-alt'></i> Add Ward";
    document.getElementById('ward_id').value = "";
}
</script>

<?php include '../includes/footer.php'; ?>
