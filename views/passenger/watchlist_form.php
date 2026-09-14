<?php
/**
 * View: Watchlist Form
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The form to add or edit a saved route.
 */

$isEdit = !empty($isEdit);
$pageTitle = $isEdit ? "Edit Watched Route - Bus Ticket MS" : "Add Route to Watchlist - Bus Ticket MS";

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
        .btn-submit { width: 100%; padding: 0.85rem; background: #2b6cb0; color: white; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
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
        <a href="../controllers/WatchlistController.php?action=index" style="text-decoration: underline;">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="card">
        <h2><?= $isEdit ? 'Edit Saved Route' : 'Add Route to Watchlist' ?></h2>
        <p><?= $isEdit ? 'Update your route preference or travel date.' : 'Choose a route to keep an eye on real-time seat availability.' ?></p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="../controllers/WatchlistController.php?action=<?= $isEdit ? 'edit' : 'create' ?>" method="POST">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$watchItem['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="route_id">Select Route *</label>
                <select name="route_id" id="route_id" class="form-control" required>
                    <option value="">-- Choose a Route --</option>
                    <?php if (!empty($routes)): ?>
                        <?php foreach ($routes as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= ((int)$routeId === (int)$r['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['from_city']) ?> ➔ <?= htmlspecialchars($r['to_city']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="preferred_date">Preferred Date (Optional)</label>
                <input type="date" name="preferred_date" id="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars(!empty($preferredDate) ? $preferredDate : '') ?>">
                <small style="color: #718096; font-size: 0.82rem;">Leave empty to monitor any upcoming departures.</small>
            </div>

            <button type="submit" class="btn-submit">
                <?= $isEdit ? 'Update Watchlist' : 'Save to Watchlist' ?>
            </button>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="../controllers/WatchlistController.php?action=index" style="color: #718096; text-decoration: none; font-size: 0.9rem;">← Cancel and go back</a>
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
