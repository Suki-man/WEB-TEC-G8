<?php
/**
 * View: Co-Traveller List
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The list of saved co-travellers.
 */

$pageTitle = "My Co-Travellers - Bus Ticket MS";
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
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.8rem; color: #1a365d; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #edf2f7; color: #4a5568; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.85rem 1rem; border-bottom: 2px solid #cbd5e0; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f7fafc; }
        .btn { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 5px; font-size: 0.88rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-danger { background: #e53e3e; color: white; }
        .actions { display: flex; gap: 0.4rem; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
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
    <div class="page-header">
        <div>
            <h1>Saved Co-Travellers</h1>
            <p style="color: #718096; font-size: 0.95rem;">Save family & friends details for quick one-click ticket booking</p>
        </div>
        <div>
            <a href="../controllers/TravellerController.php?action=create" class="btn btn-primary">+ Add New Co-Traveller</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (!empty($travellers)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone (11 Digits)</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Relation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($travellers as $t): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                            <td><?= htmlspecialchars($t['phone']) ?></td>
                            <td><?= htmlspecialchars(!empty($t['email']) ? $t['email'] : '—') ?></td>
                            <td><?= htmlspecialchars(!empty($t['gender']) ? ucfirst($t['gender']) : '—') ?></td>
                            <td><?= htmlspecialchars(!empty($t['age']) ? $t['age'] : '—') ?></td>
                            <td><?= htmlspecialchars(!empty($t['relation']) ? ucfirst($t['relation']) : '—') ?></td>
                            <td>
                                <div class="actions">
                                    <a href="../controllers/TravellerController.php?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-secondary">
                                        Edit
                                    </a>
                                    <a href="../controllers/TravellerController.php?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this co-traveller?');">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <p style="font-size: 1.15rem; color: #4a5568; margin-bottom: 1rem;">No saved co-travellers found.</p>
                <p style="color: #718096; margin-bottom: 1.5rem;">Add family members or friends to easily select them during ticket purchase.</p>
                <a href="../controllers/TravellerController.php?action=create" class="btn btn-primary">+ Add Co-Traveller</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
<footer style="background: #1a365d; color: #cbd5e0; text-align: center; padding: 1.5rem; margin-top: 3rem; font-size: 0.9rem;">
    <p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System. Part A by Nripendra Sutradhar Pranto (23-51909-2).</p>
</footer>
</body>
</html>
<?php } ?>
