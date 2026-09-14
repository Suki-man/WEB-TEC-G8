<?php
/**
 * View: Watchlist List
 * Part A: Nripendra Sutradhar Pranto
 * Screen: Saved routes, each showing how many seats are free right now.
 */

$pageTitle = "My Route Watchlist - Bus Ticket MS";
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
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 1.5rem; }
        .watchlist-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; background: white; transition: box-shadow 0.2s; }
        .watchlist-item:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
        .route-title { font-size: 1.25rem; font-weight: bold; color: #1a365d; margin-bottom: 0.3rem; }
        .route-meta { font-size: 0.9rem; color: #718096; }
        .seat-badge { display: inline-block; padding: 0.35rem 0.8rem; border-radius: 999px; font-weight: bold; font-size: 0.9rem; margin-top: 0.5rem; }
        .seat-avail { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .seat-full { background: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        .btn { display: inline-block; padding: 0.45rem 0.9rem; border-radius: 5px; font-size: 0.9rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-success { background: #38a169; color: white; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-danger { background: #e53e3e; color: white; }
        .actions { display: flex; gap: 0.5rem; align-items: center; }
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
        <a href="../controllers/WatchlistController.php?action=index" style="text-decoration: underline;">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Saved Route Watchlist</h1>
            <p style="color: #718096; font-size: 0.95rem;">Monitor real-time seat availability on your favorite routes</p>
        </div>
        <div>
            <a href="../controllers/WatchlistController.php?action=create" class="btn btn-primary">+ Add Route to Watchlist</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (!empty($watchlist)): ?>
            <?php foreach ($watchlist as $item): ?>
                <div class="watchlist-item">
                    <div>
                        <div class="route-title">
                            📍 <?= htmlspecialchars($item['from_city']) ?> ➔ <?= htmlspecialchars($item['to_city']) ?>
                        </div>
                        <div class="route-meta">
                            <?php if (!empty($item['distance'])): ?>
                                Distance: <?= htmlspecialchars($item['distance']) ?> km |
                            <?php endif; ?>
                            Preferred Date: <strong><?= !empty($item['preferred_date']) ? date('M d, Y', strtotime($item['preferred_date'])) : 'Any upcoming date' ?></strong>
                        </div>

                        <?php if ($item['has_schedule']): ?>
                            <div class="seat-badge <?= $item['free_seats'] > 0 ? 'seat-avail' : 'seat-full' ?>">
                                💺 <?= (int)$item['free_seats'] ?> free seats right now (<?= $item['total_seats'] ?> total)
                            </div>
                            <div style="font-size: 0.85rem; color: #4a5568; margin-top: 0.35rem;">
                                Next bus: <?= htmlspecialchars($item['bus_name']) ?> at <?= date('h:i A', strtotime($item['next_time'])) ?> (<?= date('M d', strtotime($item['next_date'])) ?>) — BDT <?= number_format($item['fare'], 2) ?>
                            </div>
                        <?php else: ?>
                            <div class="seat-badge" style="background: #edf2f7; color: #718096;">
                                No scheduled trips yet for this route
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="actions">
                        <?php if ($item['has_schedule'] && $item['free_seats'] > 0): ?>
                            <a href="../controllers/BookingController.php?action=book&schedule_id=<?= (int)$item['schedule_id'] ?>" class="btn btn-success">
                                Book Now
                            </a>
                        <?php endif; ?>
                        <a href="../controllers/WatchlistController.php?action=edit&id=<?= (int)$item['id'] ?>" class="btn btn-secondary">
                            Edit
                        </a>
                        <a href="../controllers/WatchlistController.php?action=delete&id=<?= (int)$item['id'] ?>" class="btn btn-danger" onclick="return confirm('Remove this route from your watchlist?');">
                            Remove
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <p style="font-size: 1.15rem; color: #4a5568; margin-bottom: 1rem;">Your watchlist is currently empty.</p>
                <p style="color: #718096; margin-bottom: 1.5rem;">Save routes you travel frequently to quickly check seat availability anytime.</p>
                <a href="../controllers/WatchlistController.php?action=create" class="btn btn-primary">+ Add Your First Route</a>
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
