<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            // Register
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'citizen')");
            if ($stmt->execute([$name, $email, $hashed_password, $phone])) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header('Location: login.php');
                exit;
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Waste Monitor</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-container">
    <div class="card glass-card fade-in" style="width: 100%; max-width: 500px; padding: 3rem 2rem;">
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class='bx bxs-user-plus' style="font-size: 32px; color: var(--secondary);"></i>
            </div>
            <h2 style="color: var(--text-main); font-size: 1.8rem; margin-bottom: 0.5rem;">Join the Community</h2>
            <p style="color: var(--text-muted);">Create your account to start tracking.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert badge-danger" style="display: block; text-align: center; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div style="position: relative;">
                    <i class='bx bxs-face' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="text" name="name" class="form-control" required placeholder="John Doe" style="padding-left: 3rem;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div style="position: relative;">
                    <i class='bx bxs-envelope' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="email" name="email" class="form-control" required placeholder="john@example.com" style="padding-left: 3rem;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number (Optional)</label>
                <div style="position: relative;">
                    <i class='bx bxs-phone' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="text" name="phone" class="form-control" placeholder="9876543210" style="padding-left: 3rem;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <i class='bx bxs-lock-alt' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••" style="padding-left: 3rem;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">Create Account</button>
        </form>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); text-align: center;">
            <p style="font-size: 0.95rem;">
                Already have an account? <a href="login.php" style="font-weight: 600;">Sign In</a>
            </p>
            <a href="index.php" style="color: var(--text-muted); font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                <i class='bx bx-arrow-back'></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
