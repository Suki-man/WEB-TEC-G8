<?php
/**
 * View: Passenger Dashboard
 * Part A: Nripendra Sutradhar Pranto
 * Screen: What a customer sees straight after logging in: next trip, totals and notices.
 */

$pageTitle = "My Dashboard - Passenger Portal";
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
        .welcome-header { margin-bottom: 2rem; }
        .welcome-header h1 { font-size: 1.85rem; color: #1a365d; }
        .welcome-header p { color: #718096; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); border-top: 4px solid #2b6cb0; }
        .stat-card h4 { font-size: 0.85rem; text-transform: uppercase; color: #718096; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: bold; color: #1a365d; }

        /* Next Trip Card */
        .trip-box { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 1.75rem; margin-bottom: 2rem; border-left: 6px solid #38a169; }
        .trip-box h3 { font-size: 1.25rem; color: #1a365d; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; }
        .trip-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.2rem; margin-bottom: 1.2rem; }
        .detail-item strong { display: block; font-size: 0.8rem; text-transform: uppercase; color: #718096; margin-bottom: 0.25rem; }
        .detail-item span { font-size: 1.1rem; font-weight: 600; color: #2d3748; }
        
        /* Notice Board */
        .notice-board { background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 1.5rem; margin-bottom: 2rem; }
        .notice-board h3 { font-size: 1.2rem; color: #1a365d; margin-bottom: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 0.5rem; }
        .notice-item { padding: 0.75rem 0; border-bottom: 1px solid #edf2f7; }
        .notice-item:last-child { border-bottom: none; }
        .notice-title { font-weight: 600; color: #2b6cb0; margin-bottom: 0.2rem; }
        .notice-text { font-size: 0.9rem; color: #4a5568; }

        .btn { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-success { background: #38a169; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
        .quick-links { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
    </style>
</head>
<body>
<nav class="navbar">
    <div style="font-size: 1.3rem; font-weight: bold;">🚌 InterCity Bus Ticket MS</div>
    <div>
        <a href="../controllers/HomeController.php">Search Buses</a>
        <a href="../controllers/BookingController.php?action=dashboard" style="text-decoration: underline;">Dashboard</a>
        <a href="../controllers/BookingController.php?action=index">My Bookings</a>
        <a href="../controllers/WatchlistController.php?action=index">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="welcome-header">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        <p>Manage your upcoming bus journeys, tickets, watchlists, and travel companions.</p>
    </div>

    <!-- Quick action links -->
    <div class="quick-links">
        <a href="../controllers/HomeController.php" class="btn btn-primary">🔍 Book New Bus Ticket</a>
        <a href="../controllers/BookingController.php?action=index" class="btn" style="background: white; border: 1px solid #cbd5e0; color: #2d3748;">🎟️ View My Tickets</a>
        <a href="../controllers/WatchlistController.php?action=index" class="btn" style="background: white; border: 1px solid #cbd5e0; color: #2d3748;">👁️ Saved Routes</a>
        <a href="../controllers/TravellerController.php?action=index" class="btn" style="background: white; border: 1px solid #cbd5e0; color: #2d3748;">👥 My Co-travellers</a>
    </div>

    <!-- Stats Section -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Total Bookings</h4>
            <div class="stat-value"><?= isset($stats['total_bookings']) ? $stats['total_bookings'] : 0 ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #38a169;">
            <h4>Active Tickets</h4>
            <div class="stat-value"><?= isset($stats['active_bookings']) ? $stats['active_bookings'] : 0 ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #d69e2e;">
            <h4>Saved Routes</h4>
            <div class="stat-value"><?= isset($watchlistCount) ? $watchlistCount : 0 ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #805ad5;">
            <h4>Saved Co-travellers</h4>
            <div class="stat-value"><?= isset($travellerCount) ? $travellerCount : 0 ?></div>
        </div>
    </div>

    <!-- Next Trip Section -->
    <?php if (!empty($nextTrip)): ?>
        <div class="trip-box">
            <h3>
                <span>🚍 Your Next Upcoming Journey</span>
                <span style="font-size: 0.9rem; background: #c6f6d5; color: #22543d; padding: 0.3rem 0.8rem; border-radius: 999px;">Confirmed</span>
            </h3>
            <div class="trip-details">
                <div class="detail-item">
                    <strong>Route</strong>
                    <span><?= htmlspecialchars($nextTrip['from_city']) ?> ➔ <?= htmlspecialchars($nextTrip['to_city']) ?></span>
                </div>
                <div class="detail-item">
                    <strong>Date & Time</strong>
                    <span><?= date('D, M d, Y', strtotime($nextTrip['departure_date'])) ?> at <?= date('h:i A', strtotime($nextTrip['departure_time'])) ?></span>
                </div>
                <div class="detail-item">
                    <strong>Bus Service</strong>
                    <span><?= htmlspecialchars($nextTrip['bus_name']) ?> (<?= htmlspecialchars($nextTrip['bus_type']) ?>)</span>
                </div>
                <div class="detail-item">
                    <strong>Seat Number</strong>
                    <span style="color: #2b6cb0; font-size: 1.3rem;"><?= htmlspecialchars($nextTrip['seat_number']) ?></span>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                <a href="../controllers/BookingController.php?action=index" class="btn btn-primary">Manage This Ticket</a>
                <a href="../controllers/BookingController.php?action=changeSeat&booking_id=<?= (int)$nextTrip['id'] ?>" class="btn" style="background: #edf2f7; color: #2d3748;">Change Seat</a>
            </div>
        </div>
    <?php else: ?>
        <div class="trip-box" style="border-left-color: #a0aec0; text-align: center; padding: 2.5rem;">
            <p style="font-size: 1.15rem; color: #4a5568; margin-bottom: 0.75rem;">You don't have any upcoming bus journeys booked right now.</p>
            <a href="../controllers/HomeController.php" class="btn btn-success">Search & Book a Bus Ticket Now</a>
        </div>
    <?php endif; ?>

    <!-- Notices & Announcements Section -->
    <div class="notice-board">
        <h3>📢 Latest Travel Notices & Announcements</h3>
        <?php if (!empty($notices)): ?>
            <?php foreach ($notices as $notice): ?>
                <div class="notice-item">
                    <div class="notice-title"><?= htmlspecialchars($notice['title']) ?></div>
                    <div class="notice-text"><?= nl2br(htmlspecialchars($notice['content'])) ?></div>
                    <div style="font-size: 0.8rem; color: #a0aec0; margin-top: 0.25rem;">
                        Posted on <?= date('M d, Y', strtotime($notice['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #718096; font-size: 0.95rem; padding: 0.5rem 0;">No new notices at this time. Safe travels!</p>
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
