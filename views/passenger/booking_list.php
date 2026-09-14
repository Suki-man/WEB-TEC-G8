<?php
/**
 * View: My Bookings
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The table of my tickets, with the Edit and Cancel buttons.
 * Acceptance criteria:
 * Book a seat -> change to a different seat -> cancel it -> a refund request appears by itself.
 */

$pageTitle = "My Bookings - Bus Ticket Management System";
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
        .container { max-width: 1150px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.8rem; color: #1a365d; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #edf2f7; color: #4a5568; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0.85rem 1rem; border-bottom: 2px solid #cbd5e0; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; vertical-align: middle; }
        tr:hover { background: #f7fafc; }
        .badge { display: inline-block; padding: 0.3rem 0.65rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .badge-confirmed { background: #c6f6d5; color: #22543d; }
        .badge-cancelled { background: #fed7d7; color: #742a2a; }
        .badge-completed { background: #e2e8f0; color: #4a5568; }
        .badge-refunded { background: #bee3f8; color: #2a4365; }
        .refund-note { font-size: 0.8rem; line-height: 1.4; }
        .btn { display: inline-block; padding: 0.45rem 0.85rem; border-radius: 5px; font-size: 0.88rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; transition: 0.2s; }
        .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.82rem; }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-primary:hover { background: #2c5282; }
        .btn-warning { background: #dd6b20; color: white; }
        .btn-warning:hover { background: #c05621; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-danger:hover { background: #c53030; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
        .action-cell { display: flex; gap: 0.4rem; align-items: center; }
        .seat-tag { background: #ebf8ff; color: #2b6cb0; font-weight: bold; padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid #bee3f8; }
    </style>
</head>
<body>
<nav class="navbar">
    <div style="font-size: 1.3rem; font-weight: bold;">🚌 InterCity Bus Ticket MS</div>
    <div>
        <a href="../controllers/HomeController.php">Search Buses</a>
        <a href="../controllers/BookingController.php?action=dashboard">Dashboard</a>
        <a href="../controllers/BookingController.php?action=index" style="text-decoration: underline;">My Bookings</a>
        <a href="../controllers/WatchlistController.php?action=index">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>My Booked Tickets</h1>
            <p style="color: #718096; font-size: 0.95rem;">View tickets, edit your seat choice, or cancel a journey</p>
        </div>
        <div>
            <a href="../controllers/HomeController.php" class="btn btn-primary">+ Book Another Ticket</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (!empty($bookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket Code</th>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Bus</th>
                        <th>Seat</th>
                        <th>Passenger</th>
                        <th>Fare</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($b['booking_code'] ? $b['booking_code'] : 'TKT-' . $b['id']) ?></strong>
                                <div style="font-size: 0.75rem; color: #a0aec0;">ID: #<?= (int)$b['id'] ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['from_city'] ? $b['from_city'] : 'N/A') ?></strong>
                                ➔ <?= htmlspecialchars($b['to_city'] ? $b['to_city'] : 'N/A') ?>
                            </td>
                            <td>
                                <div><?= !empty($b['departure_date']) ? date('M d, Y', strtotime($b['departure_date'])) : 'N/A' ?></div>
                                <div style="font-size: 0.8rem; color: #718096;"><?= !empty($b['departure_time']) ? date('h:i A', strtotime($b['departure_time'])) : '' ?></div>
                            </td>
                            <td>
                                <?= htmlspecialchars($b['bus_name'] ? $b['bus_name'] : 'InterCity') ?>
                                <div style="font-size: 0.8rem; color: #718096;"><?= htmlspecialchars($b['bus_type'] ? $b['bus_type'] : '') ?></div>
                            </td>
                            <td>
                                <span class="seat-tag"><?= htmlspecialchars($b['seat_number']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars(!empty($b['traveller_name']) ? $b['traveller_name'] : $_SESSION['user_name']) ?>
                                <div style="font-size: 0.8rem; color: #a0aec0;">
                                    <?= htmlspecialchars(!empty($b['traveller_phone']) ? $b['traveller_phone'] : (isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '')) ?>
                                </div>
                            </td>
                            <td>
                                <strong>BDT <?= number_format($b['total_fare'], 2) ?></strong>
                            </td>
                            <td>
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="badge badge-confirmed">Confirmed</span>
                                <?php elseif ($b['status'] === 'cancelled' && ($b['refund_status'] ?? '') === 'Settled'): ?>
                                    <span class="badge badge-refunded">Refunded</span>
                                <?php elseif ($b['status'] === 'cancelled'): ?>
                                    <span class="badge badge-cancelled">Cancelled</span>
                                <?php else: ?>
                                    <span class="badge badge-completed"><?= htmlspecialchars($b['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <?php if ($b['status'] === 'confirmed'): ?>
                                        <!-- Edit / Change Seat Button -->
                                        <a href="../controllers/BookingController.php?action=changeSeat&booking_id=<?= (int)$b['id'] ?>" class="btn btn-warning btn-sm" title="Change to another seat">
                                            ✏️ Change Seat
                                        </a>

                                        <!-- Cancel Ticket Form: Frees seat and opens refund -->
                                        <form action="../controllers/BookingController.php?action=cancel" method="POST" onsubmit="return confirm('Are you sure you want to cancel this ticket? Cancelling will immediately free your seat and open a refund request.');" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Cancel ticket and open refund">
                                                ✖ Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($b['status'] === 'cancelled'): ?>
                                        <?php $refundStatus = $b['refund_status'] ?? ''; ?>
                                        <?php if ($refundStatus === 'Settled'): ?>
                                            <span class="refund-note" style="color: #276749;">
                                                Refunded BDT <?= number_format((float) $b['refund_amount'], 2) ?><?= !empty($b['refund_method']) ? ' via ' . htmlspecialchars($b['refund_method']) : '' ?>
                                                <?php if (!empty($b['refund_processed_at'])): ?><br>on <?= date('M d, Y', strtotime($b['refund_processed_at'])) ?><?php endif; ?>
                                            </span>
                                        <?php elseif ($refundStatus === 'Rejected'): ?>
                                            <span class="refund-note" style="color: #9b2c2c;">Refund rejected</span>
                                        <?php elseif ($refundStatus !== ''): ?>
                                            <span class="refund-note" style="color: #718096; font-style: italic;">Refund pending<br>(BDT <?= number_format((float) $b['refund_amount'], 2) ?>)</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: #718096; font-style: italic;">Refund Opened</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: #718096;">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <p style="font-size: 1.15rem; color: #4a5568; margin-bottom: 1rem;">You have not booked any bus tickets yet.</p>
                <a href="../controllers/HomeController.php" class="btn btn-primary">Find Buses and Book a Seat</a>
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
