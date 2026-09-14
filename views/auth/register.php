<?php
/**
 * View: Customer Registration
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The sign-up form for new customers.
 */

$pageTitle = "Register - Bus Ticket Management System";
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
        .auth-container { max-width: 480px; width: 100%; margin: 3rem auto; padding: 0 1.5rem; }
        .auth-card { background: white; padding: 2.2rem; border-radius: 10px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
        .auth-header { text-align: center; margin-bottom: 1.75rem; }
        .auth-header h2 { color: #1a365d; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .auth-header p { color: #718096; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.15rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4a5568; }
        .form-control { width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(66,153,225,0.15); }
        .is-invalid { border-color: #e53e3e; }
        .field-error { color: #e53e3e; font-size: 0.82rem; margin-top: 0.25rem; }
        .btn-submit { width: 100%; padding: 0.85rem; background: #38a169; color: white; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 0.5rem; }
        .btn-submit:hover { background: #2f855a; }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.95rem; color: #718096; }
        .auth-footer a { color: #2b6cb0; text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
        .alert { padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
        .brand-logo { font-size: 2.2rem; text-align: center; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<?php } ?>

<div class="auth-container">
    <div class="brand-logo">🚌</div>
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create an Account</h2>
            <p>Sign up to book bus seats across Bangladesh</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="../controllers/AuthController.php?action=register" method="POST">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>" placeholder="e.g. Nripendra Sutradhar" value="<?= htmlspecialchars(isset($name) ? $name : '') ?>" required>
                <?php if (!empty($errors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['name'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" placeholder="e.g. pranto@example.com" value="<?= htmlspecialchars(isset($email) ? $email : '') ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['email'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number (11 digits) *</label>
                <input type="text" id="phone" name="phone" maxlength="11" class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" placeholder="017XXXXXXXX" value="<?= htmlspecialchars(isset($phone) ? $phone : '') ?>" required>
                <?php if (!empty($errors['phone'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['phone'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password (min 6 characters) *</label>
                <input type="password" id="password" name="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" placeholder="••••••••" required>
                <?php if (!empty($errors['password'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['password'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" placeholder="••••••••" required>
                <?php if (!empty($errors['confirm_password'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['confirm_password'][0]) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">Register as Customer</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="../controllers/AuthController.php?action=login">Log in here</a>
            <div style="margin-top: 1rem;">
                <a href="../views/welcomeT.php" style="display: inline-block; padding: 0.45rem 1rem; border: 1px solid #cbd5e0; border-radius: 6px; color: #4a5568; font-size: 0.85rem;">← Welcome page</a> <?php //talha ?>
            </div>
        </div>
    </div>
</div>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
</body>
</html>
<?php } ?>
