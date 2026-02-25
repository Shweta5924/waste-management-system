<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Role-based redirection
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/index.php');
                    break;
                case 'supervisor':
                    header('Location: supervisor/index.php');
                    break;
                case 'staff':
                    header('Location: staff/entry.php');
                    break;
                case 'citizen':
                    header('Location: index.php');
                    break;
                default:
                    header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Waste Monitor</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-container">
    <div class="card glass-card fade-in" style="width: 100%; max-width: 420px; padding: 3rem 2rem;">
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <div style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class='bx bxs-user-circle' style="font-size: 32px; color: var(--primary);"></i>
            </div>
            <h2 style="color: var(--text-main); font-size: 1.8rem; margin-bottom: 0.5rem;">Welcome Back</h2>
            <p style="color: var(--text-muted);">Please enter your details to sign in.</p>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert badge-success" style="display: block; text-align: center; margin-bottom: 1.5rem;">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert badge-danger" style="display: block; text-align: center; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div style="position: relative;">
                    <i class='bx bxs-envelope' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="email" name="email" class="form-control" required placeholder="name@company.com" style="padding-left: 3rem;">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <i class='bx bxs-lock-alt' style="position: absolute; left: 1rem; top: 1rem; color: var(--text-muted); pointer-events: none;"></i>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••" style="padding-left: 3rem;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">Sign In</button>
        </form>
        
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); text-align: center;">
            <p style="font-size: 0.95rem; margin-bottom: 0.5rem;">
                Don't have an account? <a href="register.php" style="font-weight: 600;">Register Now</a>
            </p>
            <a href="index.php" style="color: var(--text-muted); font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                <i class='bx bx-arrow-back'></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>
