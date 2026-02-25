<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db.php';

// Handle Status Update
if (isset($_GET['resolve'])) {
    $id = $_GET['resolve'];
    $stmt = $pdo->prepare("UPDATE complaints SET status = 'Resolved' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: complaints.php');
    exit;
}

$complaints = $pdo->query("SELECT c.*, w.ward_number, w.area_name 
                           FROM complaints c 
                           JOIN wards w ON c.ward_id = w.id 
                           ORDER BY c.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="card fade-in">
    <h2 style="margin-bottom: 2rem;"><i class='bx bxs-megaphone' style="color: var(--danger);"></i> Citizen Complaints</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Citizen</th>
                    <th>Ward</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $c): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    <td><?= htmlspecialchars($c['citizen_name']) ?></td>
                    <td><?= htmlspecialchars($c['ward_number'] . ' - ' . $c['area_name']) ?></td>
                    <td><?= htmlspecialchars($c['complaint_text']) ?></td>
                    <td>
                        <span class="badge badge-<?= $c['status'] === 'Resolved' ? 'success' : 'danger' ?>">
                            <?= $c['status'] ?>
                        </span>
                        <?php if ($c['image_path']): ?>
                            <br><a href="../<?= $c['image_path'] ?>" target="_blank" style="font-size: 0.7rem;"><i class='bx bx-image'></i> Evidence</a>
                        <?php endif; ?>
                        <?php if ($c['resolution_image_path']): ?>
                            <br><a href="../<?= $c['resolution_image_path'] ?>" target="_blank" style="font-size: 0.7rem; color: var(--success);"><i class='bx bx-check-double'></i> Resolution</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['status'] === 'Pending'): ?>
                            <a href="?resolve=<?= $c['id'] ?>" class="btn btn-sm btn-primary">
                                <i class='bx bx-check-double'></i> Mark Resolved
                            </a>
                        <?php else: ?>
                            <span style="color: var(--success); font-weight: 500;"><i class='bx bx-check'></i> Resolved</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
