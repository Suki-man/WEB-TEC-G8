<?php
/**
 * View: Login Form
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The login form.
 */

$pageTitle = "Log In - Bus Ticket Management System";
$hasLayout = false; // use this page's own styled header, not layouts/header.php
if ($hasLayout) {
    include __DIR__ . '/../layouts/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background: #f7fafc; color: #2d3748; min-height: 100vh; display: flex; flex-direction: column; }
        .auth-container { max-width: 420px; width: 100%; margin: 4rem auto; padding: 0 1.5rem; }
        .auth-card { background: white; padding: 2.2rem; border-radius: 10px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-header h2 { color: #1a365d; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .auth-header p { color: #718096; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4a5568; }
        .form-control { width: 100%; padding: 0.75rem 0.9rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(66,153,225,0.15); }
        .btn-submit { width: 100%; padding: 0.85rem; background: #2b6cb0; color: white; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: #2c5282; }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.95rem; color: #718096; }
        .auth-footer a { color: #2b6cb0; text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
        .alert { padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
        .brand-logo { font-size: 2.2rem; text-align: center; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<?php } ?>

<div class="auth-container">
    <div class="brand-logo">🚌</div>
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Log in to access your bookings & tickets</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>

        <form action="../controllers/AuthController.php?action=login" method="POST">
            <div class="form-group">
                <label for="email">Email or Username</label>
                <input type="text" id="email" name="email" class="form-control" placeholder="user@example.com or admin" value="<?= htmlspecialchars(isset($email) ? $email : '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="../controllers/AuthController.php?action=register">Register here</a>
            <div style="margin-top: 1rem;">
                <a href="../views/welcomeT.php" style="display: inline-block; padding: 0.45rem 1rem; border: 1px solid #cbd5e0; border-radius: 6px; color: #4a5568; font-size: 0.85rem;">← Welcome page</a> <?php //talha ?>
                <a href="../controllers/AuthController.php?action=guest" style="display: inline-block; padding: 0.45rem 1rem; margin-left: 0.4rem; border: 1px solid #cbd5e0; border-radius: 6px; color: #4a5568; font-size: 0.85rem;">Continue as guest</a> <?php //talha ?>
            </div>
        </div>
    </div>
</div>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
</body>
</html>
<?php } ?>
