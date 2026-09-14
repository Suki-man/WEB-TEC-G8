<?php
/**
 * View: Seat Map & Booking Form
 * Part A: Nripendra Sutradhar Pranto
 * Screen: The seat map. Click a free seat, choose who is travelling, confirm.
 * Supports:
 * 1. Booking a new seat
 * 2. Changing an existing seat
 */

$isEdit = !empty($isSeatChangeMode);
$pageTitle = $isEdit ? "Change Seat - Bus Ticket MS" : "Pick a Seat & Book - Bus Ticket MS";

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
        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.8rem; color: #1a365d; }
        
        .layout-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; }
        @media (max-width: 850px) { .layout-grid { grid-template-columns: 1fr; } }
        
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 1.75rem; }
        
        /* Bus Seat Map */
        .bus-container { background: #f7fafc; border: 2px solid #cbd5e0; border-radius: 20px; padding: 1.5rem; max-width: 380px; margin: 0 auto; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
        .bus-driver { display: flex; justify-content: flex-end; margin-bottom: 1.5rem; border-bottom: 2px dashed #cbd5e0; padding-bottom: 0.75rem; }
        .driver-wheel { background: #4a5568; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: bold; }
        .seat-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; }
        .seat-pair { display: flex; gap: 0.5rem; }
        .aisle { width: 30px; }
        
        .seat-btn { width: 42px; height: 42px; border: 2px solid #cbd5e0; border-radius: 8px; background: white; color: #2d3748; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; user-select: none; }
        .seat-btn:hover:not(.occupied):not(.current) { border-color: #3182ce; background: #ebf8ff; color: #2b6cb0; }
        .seat-btn.occupied { background: #e2e8f0; color: #a0aec0; border-color: #cbd5e0; cursor: not-allowed; }
        .seat-btn.current { background: #fefcbf; color: #744210; border-color: #d69e2e; font-weight: bold; cursor: default; }
        .seat-btn.selected { background: #3182ce !important; color: white !important; border-color: #2b6cb0 !important; transform: scale(1.08); box-shadow: 0 3px 6px rgba(49,130,206,0.3); }
        
        .legend { display: flex; justify-content: center; gap: 1.25rem; margin-top: 1.25rem; font-size: 0.85rem; }
        .legend-item { display: flex; align-items: center; gap: 0.4rem; }
        .legend-box { width: 18px; height: 18px; border-radius: 4px; border: 1px solid #cbd5e0; }
        
        /* Form & Summary */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; color: #4a5568; }
        .form-control { width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 0.95rem; }
        .summary-box { background: #edf2f7; padding: 1.25rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .summary-row.total { font-weight: bold; font-size: 1.15rem; color: #1a365d; border-top: 1px solid #cbd5e0; padding-top: 0.5rem; margin-top: 0.5rem; }
        .btn-confirm { width: 100%; padding: 0.9rem; font-size: 1.1rem; font-weight: bold; color: white; background: #38a169; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .btn-confirm:hover { background: #2f855a; }
        .btn-confirm:disabled { background: #cbd5e0; cursor: not-allowed; }
        .alert { padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
    </style>
</head>
<body>
<nav class="navbar">
    <div style="font-size: 1.3rem; font-weight: bold;">🚌 InterCity Bus Ticket MS</div>
    <div>
        <a href="../controllers/HomeController.php">Search Buses</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="../controllers/BookingController.php?action=dashboard">Dashboard</a>
        <a href="../controllers/BookingController.php?action=index">My Bookings</a>
        <a href="../controllers/WatchlistController.php?action=index">Watchlist</a>
        <a href="../controllers/TravellerController.php?action=index">Co-travellers</a>
        <a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
        <?php else: ?>
        <a href="../controllers/AuthController.php?action=login">Log in</a>
        <a href="../controllers/AuthController.php?action=register">Register</a>
        <?php endif; ?>
    </div>
</nav>
<?php } ?>

<div class="container">
    <div class="page-header">
        <h1><?= $isEdit ? 'Change Your Seat' : 'Pick a Seat & Confirm Booking' ?></h1>
        <p style="color: #718096; font-size: 0.95rem;">
            <?= htmlspecialchars($schedule['from_city']) ?> ➔ <?= htmlspecialchars($schedule['to_city']) ?> | 
            <?= date('D, M d, Y', strtotime($schedule['departure_date'])) ?> at <?= date('h:i A', strtotime($schedule['departure_time'])) ?> |
            <?= htmlspecialchars($schedule['bus_name']) ?> (<?= htmlspecialchars($schedule['bus_type']) ?>)
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($isGuest)): ?>
        <div class="alert" style="background: #ebf8ff; color: #2a4365; border-left: 4px solid #3182ce;">You are browsing as a guest. Pick a seat, then log in or register to book it. You will come back to this trip right after.</div>
    <?php endif; ?>

    <div class="layout-grid">
        <!-- Seat Map Column -->
        <div class="card">
            <h3 style="margin-bottom: 1rem; color: #1a365d; text-align: center;">Interactive Seat Map</h3>
            <p style="text-align: center; color: #718096; font-size: 0.85rem; margin-bottom: 1rem;">Click on any available (white) seat to select it.</p>

            <div class="bus-container">
                <div class="bus-driver">
                    <div class="driver-wheel">☸ Driver</div>
                </div>

                <!-- 10 rows: A to J, 4 seats per row (2 left, 2 right) -->
                <?php 
                $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                $occupied = !empty($occupiedSeats) ? $occupiedSeats : [];
                $current = isset($currentSeat) ? strtoupper(trim($currentSeat)) : '';
                ?>

                <?php foreach ($rows as $rowLetter): ?>
                    <div class="seat-row">
                        <!-- Left Pair -->
                        <div class="seat-pair">
                            <?php for ($num = 1; $num <= 2; $num++): 
                                $seatId = $rowLetter . $num;
                                $isOccupied = in_array($seatId, $occupied);
                                $isCurrent = ($seatId === $current);
                            ?>
                                <button type="button" 
                                        class="seat-btn <?= $isCurrent ? 'current' : ($isOccupied ? 'occupied' : '') ?>" 
                                        data-seat="<?= $seatId ?>" 
                                        <?= ($isOccupied && !$isCurrent) ? 'disabled' : '' ?>
                                        onclick="selectSeat('<?= $seatId ?>', <?= $isOccupied ? 'true' : 'false' ?>, <?= $isCurrent ? 'true' : 'false' ?>)">
                                    <?= $seatId ?>
                                </button>
                            <?php endfor; ?>
                        </div>

                        <!-- Aisle -->
                        <div class="aisle"></div>

                        <!-- Right Pair -->
                        <div class="seat-pair">
                            <?php for ($num = 3; $num <= 4; $num++): 
                                $seatId = $rowLetter . $num;
                                $isOccupied = in_array($seatId, $occupied);
                                $isCurrent = ($seatId === $current);
                            ?>
                                <button type="button" 
                                        class="seat-btn <?= $isCurrent ? 'current' : ($isOccupied ? 'occupied' : '') ?>" 
                                        data-seat="<?= $seatId ?>" 
                                        <?= ($isOccupied && !$isCurrent) ? 'disabled' : '' ?>
                                        onclick="selectSeat('<?= $seatId ?>', <?= $isOccupied ? 'true' : 'false' ?>, <?= $isCurrent ? 'true' : 'false' ?>)">
                                    <?= $seatId ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-box" style="background: white;"></div> Free
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background: #3182ce;"></div> Selected
                </div>
                <div class="legend-item">
                    <div class="legend-box" style="background: #e2e8f0;"></div> Booked
                </div>
                <?php if ($isEdit): ?>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #fefcbf; border-color: #d69e2e;"></div> Current
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form & Summary Column -->
        <div class="card">
            <h3 style="margin-bottom: 1.25rem; color: #1a365d;">
                <?= $isEdit ? 'Seat Change Confirmation' : 'Booking Confirmation' ?>
            </h3>

            <?php if ($isEdit): ?>
                <!-- Form for changing seat -->
                <form action="../controllers/BookingController.php?action=changeSeat" method="POST" id="bookingForm">
                    <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                    <input type="hidden" name="new_seat_number" id="selected_seat_input" value="">

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Route:</span>
                            <strong><?= htmlspecialchars($schedule['from_city']) ?> ➔ <?= htmlspecialchars($schedule['to_city']) ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Current Seat:</span>
                            <strong style="color: #d69e2e;"><?= htmlspecialchars($currentSeat) ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>New Selected Seat:</span>
                            <strong id="display_seat" style="color: #2b6cb0; font-size: 1.2rem;">None selected</strong>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm" id="submitBtn" disabled>
                        Confirm Seat Change
                    </button>
                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="../controllers/BookingController.php?action=index" style="color: #718096; text-decoration: none; font-size: 0.9rem;">← Cancel and go back</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- Form for new seat booking -->
                <form action="../controllers/BookingController.php?action=book" method="POST" id="bookingForm">
                    <input type="hidden" name="schedule_id" value="<?= (int)$schedule['id'] ?>">
                    <input type="hidden" name="seat_number" id="selected_seat_input" value="">

                    <?php if (empty($isGuest)): ?>
                    <div class="form-group">
                        <label for="traveller_id">Who is Travelling?</label>
                        <select name="traveller_id" id="traveller_id" class="form-control">
                            <option value="">Myself (<?= htmlspecialchars($_SESSION['user_name']) ?>)</option>
                            <?php if (!empty($travellers)): ?>
                                <optgroup label="Saved Co-travellers">
                                    <?php foreach ($travellers as $tr): ?>
                                        <option value="<?= (int)$tr['id'] ?>">
                                            <?= htmlspecialchars($tr['name']) ?> (<?= htmlspecialchars($tr['phone']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <div style="font-size: 0.82rem; margin-top: 0.35rem;">
                            <a href="../controllers/TravellerController.php?action=create" target="_blank" style="color: #2b6cb0; text-decoration: none;">+ Add a new co-traveller</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Selected Seat:</span>
                            <strong id="display_seat" style="color: #2b6cb0; font-size: 1.2rem;">None selected</strong>
                        </div>
                        <div class="summary-row">
                            <span>Ticket Fare:</span>
                            <span>BDT <?= number_format($schedule['fare'], 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Payable:</span>
                            <span>BDT <?= number_format($schedule['fare'], 2) ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm" id="submitBtn" disabled>
                        <?= !empty($isGuest) ? 'Log in to Book This Seat' : 'Confirm & Book Seat' ?>
                    </button>
                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="../controllers/HomeController.php" style="color: #718096; text-decoration: none; font-size: 0.9rem;">← Cancel and go back</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    let selectedSeat = null;

    function selectSeat(seatNumber, isOccupied, isCurrent) {
        if (isOccupied && !isCurrent) {
            alert('Seat ' + seatNumber + ' is already taken.');
            return;
        }
        if (isCurrent) {
            alert('Seat ' + seatNumber + ' is your currently booked seat. Please choose a different seat.');
            return;
        }

        // Deselect previous
        document.querySelectorAll('.seat-btn').forEach(btn => {
            btn.classList.remove('selected');
        });

        // Select clicked seat
        const btn = document.querySelector('[data-seat="' + seatNumber + '"]');
        if (btn) {
            btn.classList.add('selected');
        }

        selectedSeat = seatNumber;
        document.getElementById('selected_seat_input').value = seatNumber;
        document.getElementById('display_seat').innerText = seatNumber;
        document.getElementById('submitBtn').disabled = false;
    }

    <?php if (!empty($preselectSeat)): ?>
    selectSeat('<?= $preselectSeat ?>', false, false);
    <?php endif; ?>
</script>

<?php if ($hasLayout) { include __DIR__ . '/../layouts/footer.php'; } else { ?>
<footer style="background: #1a365d; color: #cbd5e0; text-align: center; padding: 1.5rem; margin-top: 3rem; font-size: 0.9rem;">
    <p>&copy; <?= date('Y') ?> InterCity Bus Ticket Management System. Part A by Nripendra Sutradhar Pranto (23-51909-2).</p>
</footer>
</body>
</html>
<?php } ?>
