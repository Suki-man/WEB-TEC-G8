<?php
// Home page and bus search. Shown by controllers/HomeController.php, which
// provides $cities, $trips, $fromCity, $toCity, $date and $searched.
$loggedIn  = !empty($_SESSION['user_id']);
$dashboard = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
	? '../controllers/UserController.php?action=index'
	: '../controllers/BookingController.php?action=dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Search Buses - Bus Ticket Management System</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: "Segoe UI", Arial, sans-serif; background: #f4f6f9; color: #2d3748; line-height: 1.5; }
		.navbar { background: #1a365d; color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
		.navbar .brand { font-size: 1.2rem; font-weight: bold; }
		.navbar a { color: #fff; text-decoration: none; margin-left: 16px; }
		.navbar a:hover { text-decoration: underline; }
		.container { max-width: 1100px; margin: 28px auto; padding: 0 16px; }
		h1 { color: #1a365d; margin-bottom: 4px; }
		.subtitle { color: #718096; margin-bottom: 20px; }
		.card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06); padding: 20px; margin-bottom: 24px; }
		.search { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
		.search label { display: block; font-size: 0.85rem; font-weight: 600; color: #4a5568; margin-bottom: 4px; }
		.search select, .search input { padding: 9px 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 0.95rem; min-width: 180px; }
		.btn { display: inline-block; padding: 9px 18px; border-radius: 6px; border: none; font-weight: 600; font-size: 0.95rem; text-decoration: none; cursor: pointer; }
		.btn-primary { background: #2b6cb0; color: #fff; }
		.btn-success { background: #38a169; color: #fff; }
		.btn-light { background: #edf2f7; color: #4a5568; }
		h2 { color: #1a365d; margin-bottom: 12px; font-size: 1.3rem; }
		table { width: 100%; border-collapse: collapse; }
		th { background: #edf2f7; color: #4a5568; font-size: 0.82rem; text-transform: uppercase; text-align: left; padding: 10px 12px; }
		td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
		.muted { color: #718096; font-size: 0.85rem; }
		.seats-ok { color: #276749; font-weight: 600; }
		.seats-full { color: #9b2c2c; font-weight: 600; }
		.alert { padding: 12px 14px; border-radius: 6px; margin-bottom: 18px; }
		.alert-success { background: #f0fff4; color: #276749; border-left: 4px solid #38a169; }
		.alert-danger { background: #fff5f5; color: #9b2c2c; border-left: 4px solid #e53e3e; }
		.empty { text-align: center; padding: 30px 10px; color: #4a5568; }
	</style>
</head>
<body>
<nav class="navbar">
	<div class="brand">Bus Ticket Management System</div>
	<div>
		<a href="../views/welcomeT.php">Welcome page</a>
		<?php if ($loggedIn): ?>
			<a href="<?= htmlspecialchars($dashboard) ?>">My dashboard</a>
			<a href="../controllers/AuthController.php?action=logout">Log out (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
		<?php else: ?>
			<?php if (!empty($_SESSION['guest'])): ?>
				<span style="margin-left: 16px; opacity: 0.8;">Browsing as guest</span>
			<?php endif; ?>
			<a href="../controllers/AuthController.php?action=login">Log in</a>
			<a href="../controllers/AuthController.php?action=register">Register</a>
		<?php endif; ?>
	</div>
</nav>

<div class="container">
	<?php if (!empty($_SESSION['flash_success'])): ?>
		<div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
	<?php endif; ?>
	<?php if (!empty($_SESSION['flash_error'])): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
	<?php endif; ?>

	<h1>Find a Bus</h1>
	<p class="subtitle">Choose where you are going and when, then pick a seat.</p>

	<div class="card">
		<form class="search" method="GET" action="../controllers/HomeController.php">
			<div>
				<label for="from_city">From</label>
				<select name="from_city" id="from_city">
					<option value="">Any city</option>
					<?php foreach ($cities as $city): ?>
						<option value="<?= htmlspecialchars($city) ?>" <?= $fromCity === $city ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="to_city">To</label>
				<select name="to_city" id="to_city">
					<option value="">Any city</option>
					<?php foreach ($cities as $city): ?>
						<option value="<?= htmlspecialchars($city) ?>" <?= $toCity === $city ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="date">Travel date</label>
				<input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
			</div>
			<div>
				<button type="submit" class="btn btn-primary">Search</button>
				<?php if ($searched): ?>
					<a href="../controllers/HomeController.php" class="btn btn-light">Clear</a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<div class="card">
		<h2><?= $searched ? count($trips) . ' trip(s) found' : 'Upcoming trips' ?></h2>

		<?php if (empty($trips)): ?>
			<p class="empty">No trips match your search. Try another date or city.</p>
		<?php else: ?>
			<table>
				<tr>
					<th>Route</th>
					<th>Date</th>
					<th>Time</th>
					<th>Bus</th>
					<th>Fare</th>
					<th>Seats</th>
					<th></th>
				</tr>
				<?php foreach ($trips as $trip): ?>
					<tr>
						<td><strong><?= htmlspecialchars($trip['from_city']) ?></strong> &rarr; <?= htmlspecialchars($trip['to_city']) ?></td>
						<td><?= date('D, M d, Y', strtotime($trip['departure_date'])) ?></td>
						<td>
							<?= date('h:i A', strtotime($trip['departure_time'])) ?>
							<div class="muted">arrives <?= date('h:i A', strtotime($trip['arrival_time'])) ?></div>
						</td>
						<td>
							<?= htmlspecialchars($trip['bus_name']) ?>
							<div class="muted"><?= htmlspecialchars($trip['bus_type']) ?></div>
						</td>
						<td><strong>BDT <?= number_format($trip['fare'], 2) ?></strong></td>
						<td>
							<?php if ($trip['free_seats'] > 0): ?>
								<span class="seats-ok"><?= (int) $trip['free_seats'] ?> free</span>
							<?php else: ?>
								<span class="seats-full">Sold out</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($trip['free_seats'] > 0): ?>
								<a href="../controllers/BookingController.php?action=book&amp;schedule_id=<?= (int) $trip['id'] ?>" class="btn btn-success">Book seat</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>
</div>
</body>
</html>
