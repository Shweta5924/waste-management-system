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
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "User deleted successfully!";
        }
    } else {
        $message = "You cannot delete yourself!";
        $msg_type = 'danger';
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $ward_id = $_POST['ward_id'] ?: null;
    $id = $_POST['user_id'] ?? null;

    if ($id) {
        // Update
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=?, ward_id=? WHERE id=?");
            $params = [$name, $email, $password, $role, $ward_id, $id];
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, ward_id=? WHERE id=?");
            $params = [$name, $email, $role, $ward_id, $id];
        }
    } else {
        // Add
        $password = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, ward_id) VALUES (?, ?, ?, ?, ?)");
        $params = [$name, $email, $password, $role, $ward_id];
    }

    try {
        if ($stmt->execute($params)) {
            $message = $id ? "User updated successfully!" : "User added successfully!";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $msg_type = 'danger';
    }
}

$wards = $pdo->query("SELECT * FROM wards")->fetchAll();
$users = $pdo->query("SELECT u.*, w.ward_number, w.area_name FROM users u LEFT JOIN wards w ON u.ward_id = w.id ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="fade-in">
    <div class="dashboard-head" style="margin-bottom: 2rem;">
        <h1>User Management</h1>
        <p>Manage system access for Admins, Supervisors, and Staff.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert badge-<?= $msg_type ?> fade-in" style="display: block; text-align: center; padding: 1rem; margin-bottom: 2rem;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add/Edit Form -->
        <div class="card glass-card">
            <h3 id="form-title" style="margin-bottom: 1.5rem;"><i class='bx bx-user-plus'></i> Add/Edit User</h3>
            <form method="POST" id="user-form">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label">User Role</label>
                    <select name="role" id="role" class="form-control" required onchange="toggleWard()">
                        <option value="citizen">Citizen</option>
                        <option value="staff">Staff (Worker)</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group" id="ward-group" style="display: none;">
                    <label class="form-label">Assign to Ward <small>(For Staff)</small></label>
                    <select name="ward_id" id="ward_id_select" class="form-control">
                        <option value="">-- No Ward --</option>
                        <?php foreach ($wards as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= $w['ward_number'] ?> - <?= $w['area_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save User</button>
                    <button type="reset" class="btn btn-outline" onclick="resetForm()" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Users List -->
        <div class="card glass-card" style="padding: 0;">
            <div style="padding: 1.5rem;">
                <h3><i class='bx bx-group'></i> System Users</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Assignment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; background: var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-muted);">
                                        <?= substr($u['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($u['name'] ?? '') ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'supervisor' ? 'warning' : 'info') ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'staff'): ?>
                                    <small><?= $u['ward_number'] ?: 'Unassigned' ?></small>
                                <?php elseif ($u['role'] === 'supervisor'): ?>
                                    <small>See Wards</small>
                                <?php else: ?>
                                    <small>-</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-success">Active</span></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-sm btn-outline" style="padding: 2px 6px;" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                        <i class='bx bx-edit-alt'></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline" style="padding: 2px 6px; color: var(--danger);" onclick="return confirm('Delete user?')">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleWard() {
    const role = document.getElementById('role').value;
    const wardGroup = document.getElementById('ward-group');
    wardGroup.style.display = (role === 'staff') ? 'block' : 'none';
}

function editUser(user) {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-edit'></i> Edit User";
    document.getElementById('user_id').value = user.id;
    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('ward_id_select').value = user.ward_id || "";
    document.getElementById('password').placeholder = "New password (leave blank to keep)";
    toggleWard();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerHTML = "<i class='bx bx-user-plus'></i> Add User";
    document.getElementById('user_id').value = "";
    document.getElementById('password').placeholder = "••••••••";
    setTimeout(toggleWard, 10);
}
</script>

<?php include '../includes/footer.php'; ?>
