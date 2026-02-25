<?php
session_start();
require_once 'config/db.php';

$message = '';
$msg_type = '';

// Handle Complaint Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $citizen_name = $_POST['name'] ?? ($_SESSION['name'] ?? 'Anonymous');
    $user_id = $_SESSION['user_id'] ?? null;
    $ward_id = $_POST['ward_id'] ?? null;
    $complaint_text = $_POST['complaint'] ?? '';
    
    $image_path = null;
    
    // Handle Image Upload
    if (isset($_FILES['complaint_image']) && $_FILES['complaint_image']['error'] === 0) {
        $upload_dir = 'assets/uploads/complaints/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['complaint_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('complaint_', true) . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['complaint_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    if ($ward_id && $complaint_text) {
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, citizen_name, ward_id, complaint_text, image_path, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        if ($stmt->execute([$user_id, $citizen_name, $ward_id, $complaint_text, $image_path])) {
            $message = "Complaint registered successfully! We will look into it.";
            $msg_type = "success";
        } else {
            $message = "Error registering complaint. Please try again.";
            $msg_type = "danger";
        }
    }
}

// Fetch Wards for Form
$wards = $pdo->query("SELECT * FROM wards")->fetchAll();

// Fetch Latest Ward Segregation Analytics
$analytics = $pdo->query("SELECT * FROM waste_analytics ORDER BY date DESC LIMIT 6")->fetchAll();

// Fetch User's Complaints if logged in
$user_complaints = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.*, w.ward_number, w.area_name 
        FROM complaints c 
        JOIN wards w ON c.ward_id = w.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_complaints = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waste Segregation Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar glass-card" style="margin: 1rem auto; max-width: 95%; border-radius: 1rem; top: 1rem;">
        <div class="container nav-content">
            <a href="index.php" class="nav-brand">
                <i class='bx bxs-trash-alt' style="color: var(--primary); font-size: 1.5rem;"></i>
                <span class="text-gradient">WasteMonitor</span>
            </a>
            <div class="nav-links">
                <a href="#stats" class="nav-link">Ward Stats</a>
                <a href="#report" class="nav-link">Report</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="#my-complaints" class="nav-link">My Trackings</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/index.php" class="nav-link"><i class='bx bxs-dashboard'></i> Admin</a>
                    <?php elseif ($_SESSION['role'] === 'supervisor'): ?>
                        <a href="supervisor/index.php" class="nav-link"><i class='bx bxs-dashboard'></i> Supervisor</a>
                    <?php elseif ($_SESSION['role'] === 'staff'): ?>
                        <a href="staff/entry.php" class="nav-link"><i class='bx bxs-pencil'></i> Staff</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-sm btn-danger"><i class='bx bx-log-out'></i></a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="container fade-in">
            <span class="badge badge-success mb-2" style="font-size: 0.9rem;">Clean City, Green Future</span>
            <h1>Cleaner Cities Start With <span style="color: var(--primary);">You</span></h1>
            <p>Monitor waste segregation, track performance, and report issues in your neighborhood efficiently.</p>
            
            <div style="margin-top: 2rem;">
                <a href="#report" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                    <i class='bx bxs-megaphone'></i> Report an Issue
                </a>
                <a href="#stats" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 1.1rem; margin-left: 1rem;">
                    <i class='bx bxs-bar-chart-alt-2'></i> View Stats
                </a>
            </div>
        </div>
    </header>

    <!-- Ward Segregation Stats -->
    <section id="stats" class="container" style="margin-top: 4rem;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h2 class="text-gradient">Ward Segregation Performance</h2>
            <p>Transparency in waste management. See how your ward is performing.</p>
        </div>

        <div class="dashboard-grid">
            <?php foreach ($analytics as $row): ?>
                <div class="stat-card fade-in">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <span class="stat-label">Ward <?= htmlspecialchars($row['ward_number']) ?></span>
                            <h3 style="margin-top: 0.25rem;"><?= htmlspecialchars($row['area_name']) ?></h3>
                        </div>
                        <span class="badge badge-<?= $row['grade'] === 'A' ? 'success' : ($row['grade'] === 'B' ? 'info' : ($row['grade'] === 'C' ? 'warning' : 'danger')) ?>">
                            Grade <?= $row['grade'] ?>
                        </span>
                    </div>
                    <div class="stat-value" style="font-size: 2rem;"><?= number_format($row['segregation_percentage'], 1) ?>%</div>
                    <p style="font-size: 0.85rem; margin-bottom: 1rem;">Segregation Rate (<?= date('M d, Y', strtotime($row['date'])) ?>)</p>
                    
                    <div style="height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
                        <div style="width: <?= $row['segregation_percentage'] ?>%; height: 100%; background: var(--secondary); border-radius: 4px;"></div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem; font-size: 0.8rem;">
                        <span><i class='bx bxs-droplet' style="color: #3b82f6;"></i> Wet: <?= $row['wet_waste'] ?>kg</span>
                        <span><i class='bx bxs-sun' style="color: #f59e0b;"></i> Dry: <?= $row['dry_waste'] ?>kg</span>
                        <span><i class='bx bxs-component' style="color: #ef4444;"></i> Mixed: <?= $row['mixed_waste'] ?>kg</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Complaint Submission Section -->
    <main class="container" id="report" style="margin-top: 6rem; margin-bottom: 6rem;">
        <div class="glass-card fade-in" style="max-width: 900px; margin: 0 auto; border: 1px solid var(--border);">
            <div style="text-align: center; margin-bottom: 2.5rem;">
                <div style="display: inline-flex; width: 64px; height: 64px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class='bx bxs-megaphone' style="font-size: 32px; color: var(--primary);"></i>
                </div>
                <h2>Report an Issue</h2>
                <p>Spotted garbage pileup or uncleared bins? Report it with a photo.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert badge-<?= $msg_type ?>" style="display: block; text-align: center; padding: 1rem; font-size: 1rem; margin-bottom: 2rem; border-radius: 0.5rem;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="submit_complaint" value="1">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <div style="position: relative;">
                            <i class='bx bxs-user' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); z-index: 1;"></i>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" style="padding-left: 2.75rem;">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group" style="<?= isset($_SESSION['user_id']) ? 'grid-column: span 2;' : '' ?>">
                        <label class="form-label">Location / Ward</label>
                        <div style="position: relative;">
                            <i class='bx bxs-map' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); z-index: 1;"></i>
                            <select name="ward_id" class="form-control" required style="padding-left: 2.75rem;">
                                <option value="">-- Select Ward --</option>
                                <?php foreach ($wards as $ward): ?>
                                    <option value="<?= $ward['id'] ?>"><?= htmlspecialchars($ward['ward_number'] . ' - ' . $ward['area_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Complaint Details</label>
                    <textarea name="complaint" class="form-control" rows="4" required placeholder="Describe the issue..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Upload Evidence Image (Optional)</label>
                    <div style="border: 2px dashed var(--border); padding: 2rem; border-radius: var(--radius-md); text-align: center; transition: 0.3s; cursor: pointer;" onclick="document.getElementById('complaint_image').click()">
                        <i class='bx bx-cloud-upload' style="font-size: 40px; color: var(--text-muted);"></i>
                        <p style="margin-top: 10px; margin-bottom: 0;">Click to upload or drag and drop</p>
                        <p style="font-size: 0.8rem; color: var(--text-muted);">JPG, PNG or GIF (Max 5MB)</p>
                        <input type="file" id="complaint_image" name="complaint_image" accept="image/*" style="display: none;" onchange="this.parentElement.style.borderColor='var(--secondary)'; this.parentElement.querySelector('p').innerText=this.files[0].name">
                    </div>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; width: 100%; font-size: 1.1rem;">
                        <i class='bx bxs-send'></i> Submit Complaint
                    </button>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <p style="margin-top: 1rem; font-size: 0.9rem;">
                            <i class='bx bx-info-circle'></i> <a href="login.php">Login</a> to track your complaint status.
                        </p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>

    <!-- Track Complaints Section -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <section id="my-complaints" class="container" style="margin-bottom: 6rem;">
        <div style="margin-bottom: 2.5rem;">
            <h2 class="text-gradient">Track Your Complaints</h2>
            <p>Monitor the progress of issues you've reported.</p>
        </div>

        <?php if (empty($user_complaints)): ?>
            <div class="card" style="text-align: center; padding: 4rem;">
                <i class='bx bx-list-check' style="font-size: 4rem; color: var(--border);"></i>
                <h3 style="color: var(--text-muted); margin-top: 1rem;">No complaints found.</h3>
                <p>Everything looks clean! Use the form above to report an issue.</p>
            </div>
        <?php else: ?>
            <div class="table-container fade-in">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Ward</th>
                            <th>Issue</th>
                            <th>Evidence</th>
                            <th>Status</th>
                            <th>Resolution Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_complaints as $c): ?>
                            <tr>
                                <td style="font-weight: 600;">#<?= $c['id'] ?></td>
                                <td style="font-size: 0.9rem;"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <span style="display: block; font-weight: 500;"><?= htmlspecialchars($c['ward_number']) ?></span>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($c['area_name']) ?></span>
                                </td>
                                <td style="max-width: 300px; white-space: normal; font-size: 0.9rem;">
                                    <?= htmlspecialchars($c['complaint_text']) ?>
                                </td>
                                <td>
                                    <?php if ($c['image_path']): ?>
                                        <a href="<?= htmlspecialchars($c['image_path']) ?>" target="_blank" class="badge badge-info" style="gap: 4px;">
                                            <i class='bx bx-image'></i> View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = match($c['status']) {
                                        'Resolved' => 'success',
                                        'In Progress' => 'info',
                                        'Pending' => 'warning',
                                        'Rejected' => 'danger',
                                        default => 'info'
                                    };
                                    ?>
                                    <span class="badge badge-<?= $status_class ?>"><?= $c['status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($c['resolution_image_path']): ?>
                                        <a href="<?= htmlspecialchars($c['resolution_image_path']) ?>" target="_blank" class="badge badge-success" style="gap: 4px;">
                                            <i class='bx bx-check-double'></i> View Proof
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.8rem;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <footer style="margin-top: auto; padding: 4rem 0; background: white; border-top: 1px solid var(--border);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
            <div>
                <a href="index.php" class="nav-brand" style="margin-bottom: 1rem;">
                    <i class='bx bxs-trash-alt' style="color: var(--primary);"></i>
                    <span class="text-gradient">WasteMonitor</span>
                </a>
                <p style="max-width: 300px; font-size: 0.9rem;">Empowering citizens to build cleaner, greener, and smarter cities through real-time waste monitoring.</p>
            </div>
            <div style="display: flex; gap: 3rem;">
                <div>
                    <h4 style="font-size: 1rem; margin-bottom: 1.5rem;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                        <li style="margin-bottom: 0.5rem;"><a href="#stats" style="color: var(--text-muted);">Ward Statistics</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#report" style="color: var(--text-muted);">Report Issue</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="login.php" style="color: var(--text-muted);">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size: 1rem; margin-bottom: 1.5rem;">Support</h4>
                    <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                        <li style="margin-bottom: 0.5rem;"><a href="#" style="color: var(--text-muted);">Contact Us</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#" style="color: var(--text-muted);">FAQ</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="#" style="color: var(--text-muted);">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 0.85rem;">
            <p>&copy; <?= date('Y') ?> Waste Segregation Monitoring System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
