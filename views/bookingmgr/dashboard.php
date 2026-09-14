<?php
/**
 * View: Booking Manager Dashboard
 * Part C: Booking Manager (counter staff)
 * Screen: reservations, seats and refunds at a glance, plus the counter
 * form with the seat map. Opened through ManagerControllerT.php.
 */

if (!isset($stats)) {
	header("Location: ../../controllers/ManagerControllerT.php?action=dashboard");
	exit;
}

$userName = $_SESSION['user_name'] ?? 'Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Booking Overview & Seat Management - RoadLine</title>
	<style>
		* { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
		body { background-color: #f4f6f8; color: #333; display: flex; min-height: 100vh; }

		/* Sidebar */
		.sidebar { width: 240px; flex-shrink: 0; background: #fff; border-right: 1px solid #e2e8f0; padding: 24px 16px; display: flex; flex-direction: column; gap: 20px; }
		.brand { font-size: 20px; font-weight: bold; color: #1e293b; display: flex; align-items: center; gap: 8px; }
		.nav-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
		.nav-item a { text-decoration: none; color: #64748b; padding: 10px 14px; display: block; border-radius: 8px; font-weight: 500; }
		.nav-item.active a, .nav-item a:hover { background-color: #fff1ec; color: #ff5722; font-weight: bold; }

		/* Main area */
		.main-content { flex: 1; min-width: 0; padding: 32px; overflow-y: auto; }
		.header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
		.profile-avatar { width: 40px; height: 40px; background: #ff5722; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

		/* Number cards */
		.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
		.card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
		.card-val { font-size: 28px; font-weight: bold; margin-bottom: 4px; }
		.card-lbl { color: #64748b; font-size: 14px; }

		/* Tables */
		.two-column { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px; margin-bottom: 24px; }
		.panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }
		.panel-title { font-weight: bold; font-size: 16px; }
		.action-link { color: #2563eb; text-decoration: none; font-size: 14px; font-weight: 500; }
		.table-wrap { overflow-x: auto; }
		table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
		th, td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
		th { color: #64748b; font-weight: 600; }
		.muted { color: #94a3b8; font-size: 12px; }
		.empty { color: #64748b; padding: 12px 0; }

		/* Badges */
		.badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
		.badge-green  { background: #dcfce7; color: #15803d; }
		.badge-yellow { background: #fef9c3; color: #a16207; }
		.badge-blue   { background: #e0e7ff; color: #3730a3; }
		.badge-red    { background: #fee2e2; color: #b91c1c; }
		.badge-grey   { background: #f1f5f9; color: #475569; }

		/* Buttons */
		.btn { padding: 8px 16px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; font-size: 14px; }
		.btn-orange { background: #ff5722; color: white; }
		.btn-orange:hover { background: #e64a19; }
		.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #334155; }
		.btn-outline:hover { background: #f8fafc; }

		/* Counter form + seat map */
		.seatmap-container { display: flex; flex-wrap: wrap; gap: 24px; }
		.seatmap-container > div { flex: 1 1 280px; }
		.field { margin-bottom: 16px; }
		.field label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 6px; }
		.field input, .field select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: white; }
		.field input[readonly] { background: #f8fafc; font-weight: bold; }
		.warn { color: red; font-size: 12px; }
		.seat-grid { display: grid; grid-template-columns: repeat(2, 48px) 20px repeat(2, 48px); gap: 12px; margin-top: 16px; }
		.seat-item { width: 48px; height: 48px; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; background: white; user-select: none; }
		.seat-item.taken { background: #cbd5e1; color: #64748b; cursor: not-allowed; }
		.seat-item.counter { background: #fed7aa; border-color: #fdba74; color: #9a3412; }
		.seat-item.selected { background: #ff5722; color: white; border-color: #ff5722; }
		.aisle { width: 20px; }
		.legend { display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; }
		.legend i { display: inline-block; width: 12px; height: 12px; border: 1px solid #cbd5e1; vertical-align: middle; margin-right: 4px; border-radius: 3px; }

		.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
		.alert-success { background: #dcfce7; color: #15803d; }
		.alert-danger { background: #fee2e2; color: #b91c1c; }
	</style>
</head>
<body>

	<div class="sidebar">
		<div class="brand">🚌 RoadLine</div>
		<ul class="nav-list">
			<li class="nav-item active"><a href="../controllers/ManagerControllerT.php?action=dashboard">Dashboard</a></li>
			<li class="nav-item"><a href="#reservations">Reservations</a></li>
			<li class="nav-item"><a href="#seatmap-section">Seat Allocation</a></li>
			<li class="nav-item"><a href="../controllers/ManagerControllerT.php?action=refunds">Cancellations & Refunds</a></li>
			<li class="nav-item"><a href="../controllers/AnnouncementControllerT.php?action=index">Announcements</a></li>
			<li class="nav-item"><a href="../controllers/AuthController.php?action=logout">Log out</a></li>
		</ul>
	</div>

	<div class="main-content">
		<div class="header-bar">
			<div>
				<h2>Booking overview</h2>
				<p style="color: #64748b; font-size: 14px;">Reservations, seats and refunds at a glance</p>
			</div>
			<div style="display: flex; align-items: center; gap: 12px;">
				<button type="button" class="btn btn-outline" onclick="goBack('../views/allPagesT.php')">← Back</button>
				<div class="profile-avatar" title="<?= htmlspecialchars($userName) ?>"><?= htmlspecialchars(strtoupper(substr($userName, 0, 1))) ?></div>
			</div>
		</div>

		<?php if (!$dbConnected): ?>
			<div class="alert alert-danger">The database is not connected. Start MySQL in XAMPP and import <strong>busdbT.sql</strong>.</div>
		<?php endif; ?>
		<?php if (!empty($_SESSION['flash_success'])): ?>
			<div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
		<?php endif; ?>
		<?php if (!empty($_SESSION['flash_error'])): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
		<?php endif; ?>
		<?php if ($error !== ''): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
			<button type="button" class="btn btn-orange" onclick="startReservation()">+ New reservation</button>
			<button type="button" class="btn btn-outline" onclick="scrollToSeatmap()">Assign seats</button>
			<a class="btn btn-outline" href="../controllers/ManagerControllerT.php?action=refunds">Review refunds</a>
		</div>

		<div class="metrics-grid">
			<div class="card">
				<div class="card-val"><?= (int) $stats['bookings_today'] ?></div>
				<div class="card-lbl">Bookings today</div>
			</div>
			<div class="card">
				<div class="card-val"><?= (int) $stats['counter_today'] ?></div>
				<div class="card-lbl">Counter sales today</div>
			</div>
			<div class="card">
				<div class="card-val"><?= (int) $stats['occupancy'] ?>%</div>
				<div class="card-lbl">Seat occupancy (upcoming trips)</div>
			</div>
			<div class="card">
				<div class="card-val"><?= (int) $stats['pending_refunds'] ?></div>
				<div class="card-lbl">Refunds pending</div>
			</div>
		</div>

		<?php if (!empty($announcements)): ?>
			<div class="card" style="margin-bottom: 24px; border-left: 4px solid #ff5722;">
				<div class="panel-header">
					<span class="panel-title">📢 Announcements</span>
					<a href="../controllers/AnnouncementControllerT.php?action=index" class="action-link">Manage →</a>
				</div>
				<?php foreach ($announcements as $note): ?>
					<div style="padding: 8px 0; border-top: 1px solid #f1f5f9;">
						<strong><?= htmlspecialchars($note['title']) ?></strong>
						<span class="muted"> · <?= date('d M Y', strtotime($note['created_at'])) ?></span>
						<div style="font-size: 14px; color: #475569; white-space: pre-line;"><?= htmlspecialchars($note['body']) ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="two-column">
			<div class="card" id="reservations">
				<div class="panel-header">
					<span class="panel-title">Recent reservations</span>
				</div>
				<?php if ($reservations): ?>
					<div class="table-wrap">
						<table>
							<thead>
								<tr><th>Passenger</th><th>Trip</th><th>Seat</th><th>Status</th></tr>
							</thead>
							<tbody>
								<?php foreach ($reservations as $row): ?>
								<tr>
									<td><?= htmlspecialchars($row['customer']) ?><br><span class="muted"><?= htmlspecialchars($row['ref']) ?> · <?= htmlspecialchars($row['channel']) ?></span></td>
									<td><?= htmlspecialchars($row['origin']) ?> → <?= htmlspecialchars($row['destination']) ?></td>
									<td><strong><?= htmlspecialchars($row['seat_no']) ?></strong></td>
									<td><span class="badge <?= managerBadge($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<p class="empty">No reservations yet.</p>
				<?php endif; ?>
			</div>

			<div class="card">
				<div class="panel-header">
					<span class="panel-title">Cancellation requests</span>
					<a href="../controllers/ManagerControllerT.php?action=refunds" class="action-link">Manage refunds →</a>
				</div>
				<?php if ($refunds): ?>
					<div class="table-wrap">
						<table>
							<thead>
								<tr><th>Passenger</th><th>Trip</th><th>Requested</th><th>Status</th></tr>
							</thead>
							<tbody>
								<?php foreach ($refunds as $row): ?>
								<tr>
									<td><?= htmlspecialchars($row['customer'] ?? '-') ?><br><span class="muted"><?= htmlspecialchars($row['booking_ref']) ?></span></td>
									<td><?= htmlspecialchars(($row['origin'] ?? '-') . ' → ' . ($row['destination'] ?? '-')) ?></td>
									<td><?= date('d M Y', strtotime($row['requested_at'])) ?></td>
									<td><span class="badge <?= managerBadge($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<p class="empty">No refund requests.</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="card" id="seatmap-section">
			<div class="panel-header">
				<div>
					<span class="panel-title">Reservations & seat allocation</span>
					<?php if ($schedule): ?>
						<p style="color: #64748b; font-size: 13px; margin-top: 4px;">
							<?= htmlspecialchars($schedule['origin']) ?> → <?= htmlspecialchars($schedule['destination']) ?>
							· <?= htmlspecialchars($schedule['bus_number']) ?> (<?= htmlspecialchars($schedule['bus_type']) ?>)
							· <?= date('d M, h:i A', strtotime($schedule['travel_date'] . ' ' . $schedule['departure_time'])) ?>
							· BDT <?= number_format((float) $schedule['fare'], 2) ?>
						</p>
					<?php endif; ?>
				</div>

				<form method="GET" action="../controllers/ManagerControllerT.php">
					<input type="hidden" name="action" value="dashboard">
					<select name="schedule_id" onchange="this.form.submit()" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px;">
						<?php if (!$schedules): ?>
							<option value="">No upcoming trips</option>
						<?php endif; ?>
						<?php foreach ($schedules as $s): ?>
							<option value="<?= (int) $s['id'] ?>" <?= $schedule && (int) $schedule['id'] === (int) $s['id'] ? 'selected' : '' ?>>
								<?= htmlspecialchars($s['origin'] . ' → ' . $s['destination'] . ' · ' . date('d M, h:i A', strtotime($s['travel_date'] . ' ' . $s['departure_time']))) ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>
			</div>

			<?php if (!$schedule): ?>
				<p class="empty">Choose an upcoming trip to sell seats at the counter.</p>
			<?php else: ?>
			<form method="POST" action="../controllers/ManagerControllerT.php?action=dashboard" id="booking-form" class="seatmap-container">
				<input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
				<input type="hidden" name="seat_no" id="selected_seat_input" value="<?= htmlspecialchars($old['seat_no']) ?>">

				<div>
					<div class="field">
						<label for="passenger_name">Passenger Name</label>
						<input type="text" name="passenger_name" id="passenger_name" placeholder="Enter customer name..." value="<?= htmlspecialchars($old['passenger_name']) ?>">
						<span id="name-warn" class="warn" hidden>Name must be at least 3 characters.</span>
					</div>

					<div class="field">
						<label for="phone">Phone</label>
						<input type="text" name="phone" id="phone" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($old['phone']) ?>">
						<span id="phone-warn" class="warn" hidden>Phone must be 11 digits and start with 01.</span>
					</div>

					<div class="field">
						<label for="payment_mode">Payment</label>
						<select name="payment_mode" id="payment_mode">
							<?php foreach ($paymentModes as $mode): ?>
								<option value="<?= htmlspecialchars($mode) ?>" <?= $old['payment_mode'] === $mode ? 'selected' : '' ?>><?= htmlspecialchars($mode) ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="field">
						<label for="display_seat_no">Selected Seat</label>
						<input type="text" id="display_seat_no" readonly placeholder="Click a seat from the seat map" value="<?= htmlspecialchars($old['seat_no']) ?>">
					</div>

					<div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px;">
						<button type="submit" name="action" value="assign_seat" class="btn btn-orange" onclick="return checkAssign()">Assign seat</button>
						<button type="submit" name="action" value="release_seat" class="btn btn-outline" onclick="return checkRelease()">Release seat</button>
					</div>
					<p class="muted" style="margin-top: 10px;">Release works on seats sold at the counter (orange). It voids the ticket and opens a refund request.</p>
				</div>

				<div style="border-left: 1px solid #e2e8f0; padding-left: 24px;">
					<div class="legend">
						<span><i style="background: white;"></i> Free</span>
						<span><i style="background: #cbd5e1;"></i> Booked online</span>
						<span><i style="background: #fed7aa; border-color: #fdba74;"></i> Sold at counter</span>
						<span><i style="background: #ff5722; border-color: #ff5722;"></i> Selected</span>
					</div>

					<div class="seat-grid">
						<?php $i = 0; foreach ($seatMap as $seat => $state): ?>
							<?php if ($i % 4 === 2): ?><div class="aisle"></div><?php endif; ?>
							<div class="seat-item <?= $state === 'free' ? '' : $state ?> <?= $seat === $old['seat_no'] ? 'selected' : '' ?>"
								data-seat="<?= htmlspecialchars($seat) ?>" data-state="<?= $state ?>"
								title="<?= $seat ?>: <?= $state === 'free' ? 'free' : ($state === 'counter' ? 'sold at counter' : 'booked online') ?>"
								onclick="selectSeat(this)"><?= htmlspecialchars($seat) ?></div>
						<?php $i++; endforeach; ?>
					</div>
				</div>
			</form>
			<?php endif; ?>
		</div>
	</div>

	<script>
		// seat map: free and counter-sold seats can be selected, online seats cannot
		function selectSeat(element) {
			if (element.dataset.state === 'taken') return;

			document.querySelectorAll('.seat-item').forEach(function (seat) {
				seat.classList.remove('selected');
			});
			element.classList.add('selected');
			document.getElementById('selected_seat_input').value = element.dataset.seat;
			document.getElementById('display_seat_no').value = element.dataset.seat;
		}

		function selectedSeatState() {
			var seat = document.querySelector('.seat-item.selected');
			return seat ? seat.dataset.state : '';
		}

		// form checks before sending (the server checks again)
		function checkName() {
			var ok = document.getElementById('passenger_name').value.trim().length >= 3;
			document.getElementById('name-warn').hidden = ok;
			return ok;
		}

		function checkPhone() {
			var ok = /^01[0-9]{9}$/.test(document.getElementById('phone').value.trim());
			document.getElementById('phone-warn').hidden = ok;
			return ok;
		}

		function checkAssign() {
			var nameOk = checkName();
			var phoneOk = checkPhone();
			if (selectedSeatState() !== 'free') {
				alert('Please click a free seat on the seat map.');
				return false;
			}
			return nameOk && phoneOk;
		}

		function checkRelease() {
			if (selectedSeatState() !== 'counter') {
				alert('Please click a seat sold at the counter (orange) to release it.');
				return false;
			}
			return confirm('Release seat ' + document.getElementById('selected_seat_input').value + ' and open a refund request?');
		}

		var nameInput = document.getElementById('passenger_name');
		if (nameInput) {
			nameInput.addEventListener('blur', checkName);
			document.getElementById('phone').addEventListener('blur', checkPhone);
		}

		// back to the previous page, or to the fallback page when there is none
		function goBack(fallback) {
			var previous = document.referrer.split('#')[0];
			if (previous !== '' && previous !== location.href.split('#')[0] && history.length > 1) {
				history.back();
			} else {
				location.href = fallback;
			}
		}

		function scrollToSeatmap() {
			document.getElementById('seatmap-section').scrollIntoView({ behavior: 'smooth' });
		}

		function startReservation() {
			scrollToSeatmap();
			if (nameInput) nameInput.focus({ preventScroll: true });
		}
	</script>
</body>
</html>
