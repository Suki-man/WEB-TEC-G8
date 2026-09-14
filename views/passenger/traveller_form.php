<?php
/**
 * View: Co-Traveller Form
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The form to add or edit a co-traveller.
 */

$isEdit = !empty($isEdit);
$pageTitle = $isEdit ? "Edit Co-Traveller - Bus Ticket MS" : "Add Co-Traveller - Bus Ticket MS";

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
        body { background: #f4f6f9; color: #2d3748; line-height: 1.6; }
        .navbar { background: #1a365d; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 1rem; font-weight: 500; font-size: 0.95rem; }
        .navbar a:hover { text-decoration: underline; }
        .container { max-width: 600px; margin: 3rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 2rem; }
        .card h2 { color: #1a365d; margin-bottom: 0.5rem; font-size: 1.6rem; }
        .card p { color: #718096; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4a5568; }
        .form-control { width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(66,153,225,0.15); }
        .is-invalid { border-color: #e53e3e; }
        .field-error { color: #e53e3e; font-size: 0.82rem; margin-top: 0.25rem; }
        .btn-submit { width: 100%; padding: 0.85rem; background: #2b6cb0; color: white; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 0.5rem; }
        .btn-submit:hover { background: #2c5282; }
        .alert { padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
    </style>
</head>
<body>
<nav class="navbar">
    <div style="font-size: 1.3rem; font-weight: bold;">🚌 InterCity Bus Ticket MS</div>
    <div>
        <a href="../controllers/HomeController.php">Search Buses</a>
        <a href="../controllers/BookingController.php?action=dashboard">Dashboard</a>
        <a href="../controllers/BookingController.php?action=index">My Bookings</a>
        <a href="../controllers/WatchlistController.php?action=index">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index" style="text-decoration: underline;">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="card">
        <h2><?= $isEdit ? 'Edit Co-Traveller' : 'Add New Co-Traveller' ?></h2>
        <p><?= $isEdit ? 'Update details of your saved travel companion.' : 'Enter details for family or friends who travel with you.' ?></p>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="../controllers/TravellerController.php?action=<?= $isEdit ? 'edit' : 'create' ?>" method="POST">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" name="name" id="name" class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>" placeholder="e.g. Joy Sutradhar" value="<?= htmlspecialchars(isset($data['name']) ? $data['name'] : '') ?>" required>
                <?php if (!empty($errors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['name'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number (11 Digits) *</label>
                <input type="text" name="phone" id="phone" maxlength="11" class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars(isset($data['phone']) ? $data['phone'] : '') ?>" required>
                <?php if (!empty($errors['phone'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['phone'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address (Optional)</label>
                <input type="email" name="email" id="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" placeholder="companion@example.com" value="<?= htmlspecialchars(isset($data['email']) ? $data['email'] : '') ?>">
                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['email'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="male" <?= (isset($data['gender']) && strtolower($data['gender']) === 'male') ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= (isset($data['gender']) && strtolower($data['gender']) === 'female') ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= (isset($data['gender']) && strtolower($data['gender']) === 'other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" min="1" max="120" class="form-control" placeholder="e.g. 24" value="<?= htmlspecialchars(isset($data['age']) ? $data['age'] : '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="relation">Relationship</label>
                <select name="relation" id="relation" class="form-control">
                    <option value="">-- Select Relationship --</option>
                    <?php 
                    $relations = ['Friend', 'Family', 'Spouse', 'Child', 'Parent', 'Colleague', 'Other'];
                    $currentRel = isset($data['relation']) ? ucfirst(strtolower($data['relation'])) : '';
                    foreach ($relations as $rel): ?>
                        <option value="<?= strtolower($rel) ?>" <?= ($currentRel === $rel) ? 'selected' : '' ?>><?= $rel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit">
                <?= $isEdit ? 'Update Co-Traveller' : 'Save Co-Traveller' ?>
            </button>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="../controllers/TravellerController.php?action=index" style="color: #718096; text-decoration: none; font-size: 0.9rem;">← Cancel and go back</a>
            </div>
        </form>
    </div>
</div>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
<footer style="background: #1a365d; color: #cbd5e0; text-align: center; padding: 1.5rem; margin-top: 3rem; font-size: 0.9rem;">
    <p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System. Part A by Nripendra Sutradhar Pranto (23-51909-2).</p>
</footer>
</body>
</html>
<?php } ?>
