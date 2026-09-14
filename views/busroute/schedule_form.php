<?php
session_start();
require __DIR__ . '/guardT.php';
require_once __DIR__ . '/../../models/BusT.php';
require_once __DIR__ . '/../../models/RouteT.php';
// the drop-downs list every bus and route
$buses = (new Bus())->getAllBuses();
$routes = (new Route())->getAllRoutes();
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Create Schedule</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h1>

	<h2>Create New Schedule</h2>
	<?php busrouteFlash(); ?>
	<form action="../../controllers/scheduleController.php?action=create" method="post">
		Bus: <select name="bus_id" id="bus_id" required>
			<?php foreach ($buses as $bus): ?>
				<option value="<?php echo (int) $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_number'] . ' (' . $bus['type'] . ', ' . $bus['seat_capacity'] . ' seats' . ($bus['status'] === 'Active' ? '' : ', inactive') . ')'); ?></option>
			<?php endforeach; ?>
		</select>
		<br>
		Route: <select name="route_id" id="route_id" required>
			<?php foreach ($routes as $route): ?>
				<option value="<?php echo (int) $route['id']; ?>"><?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination'] . ($route['status'] === 'Active' ? '' : ' (inactive)')); ?></option>
			<?php endforeach; ?>
		</select>
		<br>
		Travel Date: <input type="date" name="travel_date" id="travel_date" min="<?php echo date('Y-m-d'); ?>" required>
		<br>
		Departure Time: <input type="time" name="departure_time" id="departure_time" required>
		<br>
		Arrival Time: <input type="time" name="arrival_time" id="arrival_time" required>
		<br>
		Fare: <input type="number" step="0.01" min="1" name="fare" id="fare" required>
		<br>
		Status:
		<select name="status" id="status">
			<option value="Active">Active</option>
			<option value="Inactive">Inactive</option>
		</select>
		<br><br>
		<input type="submit" value="Create">
	</form>

	<br><br>
	<a href="./schedule_list.php">Back to Schedule List</a>
</body>
</html>