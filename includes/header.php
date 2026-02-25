<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waste Segregation Monitoring System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar glass-card" style="margin: 1rem auto; max-width: 95%; border-radius: 1rem; top: 1rem;">
        <div class="container nav-content">
            <a href="/index.php" class="nav-brand">
                <i class='bx bxs-trash-alt' style="color: var(--primary);"></i>
                <span>WasteMonitor</span>
            </a>
            <div class="nav-links">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/admin/index.php" class="nav-link"><i class='bx bxs-dashboard'></i> Dashboard</a>
                    <a href="/admin/wards.php" class="nav-link"><i class='bx bxs-map'></i> Wards</a>
                    <a href="/admin/vehicles.php" class="nav-link"><i class='bx bxs-truck'></i> Vehicles</a>
                <?php elseif ($_SESSION['role'] === 'staff'): ?>
                    <a href="/staff/entry.php" class="nav-link"><i class='bx bxs-pencil'></i> Daily Entry</a>
                <?php elseif ($_SESSION['role'] === 'citizen'): ?>
                    <!-- Citizen Specific Links -->
                <?php endif; ?>
                
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: 1rem; padding-left: 1rem; border-left: 1px solid var(--border);">
                    <div style="text-align: right; line-height: 1.2;">
                         <span style="display: block; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($_SESSION['name']) ?></span>
                         <span style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;"><?= $_SESSION['role'] ?></span>
                    </div>
                    <a href="/logout.php" class="btn btn-sm btn-danger" style="padding: 0.4rem 0.6rem;"><i class='bx bx-log-out'></i></a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="container">
