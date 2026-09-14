<?php
session_start();
require_once __DIR__ . '/../../models/BusT.php';
require_once __DIR__ . '/../../models/RouteT.php';
require_once __DIR__ . '/../../models/ScheduleT.php';
require __DIR__ . '/guardT.php';

$id = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? null;

if (!$id || !in_array($type, ['bus', 'route', 'schedule'], true)) {
	header('Location: dashboard.php');
	exit();
}

$data = null;
$buses = [];
$routes = [];
if ($type == 'bus') {
	$busModel = new Bus();
	$data = $busModel->getBusById($id);
} elseif ($type == 'route') {
	$routeModel = new Route();
	$data = $routeModel->getRouteById($id);
} elseif ($type == 'schedule') {
	$scheduleModel = new Schedule();
	$data = $scheduleModel->getScheduleById($id);
	$buses = (new Bus())->getAllBuses();
	$routes = (new Route())->getAllRoutes();
}

if (!$data) {
	$_SESSION['flash_error'] = ucfirst($type) . " #$id was not found.";
	header('Location: ' . $type . '_list.php');
	exit();
}

// a field's value, escaped for a form input
function fieldValue($data, $field) {
	return htmlspecialchars($data[$field], ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Edit Record</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h1>
	<h2>Edit <?php echo ucfirst($type); ?></h2>
	<?php busrouteFlash(); ?>

	<?php if ($type == 'bus'): ?>
		<form action="../../controllers/busController.php?action=update" method="post">
			<input type="hidden" name="id" value="<?php echo (int) $data['id']; ?>">
			Bus Number: <input type="text" name="bus_number" value="<?php echo fieldValue($data, 'bus_number'); ?>" required><br>
			Type: <input type="text" name="type" value="<?php echo fieldValue($data, 'type'); ?>" required><br>
			Seat Capacity: <input type="number" name="seat_capacity" min="1" max="40" value="<?php echo (int) $data['seat_capacity']; ?>" required><br>
			Layout: <input type="text" name="layout" value="<?php echo fieldValue($data, 'layout'); ?>" required><br>
			Status:
			<select name="status">
				<option value="Active" <?php if($data['status']=='Active') echo 'selected'; ?>>Active</option>
				<option value="Inactive" <?php if($data['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
			</select><br><br>
			<input type="submit" value="Update Bus">
		</form>
	<?php elseif ($type == 'route'): ?>
		<form action="../../controllers/routeController.php?action=update" method="post">
			<input type="hidden" name="id" value="<?php echo (int) $data['id']; ?>">
			Origin: <input type="text" name="origin" value="<?php echo fieldValue($data, 'origin'); ?>" required><br>
			Destination: <input type="text" name="destination" value="<?php echo fieldValue($data, 'destination'); ?>" required><br>
			Distance (KM): <input type="number" step="0.01" min="0.01" name="distance_km" value="<?php echo fieldValue($data, 'distance_km'); ?>" required><br>
			Estimated Duration: <input type="text" name="est_duration" value="<?php echo fieldValue($data, 'est_duration'); ?>" required><br>
			Status:
			<select name="status">
				<option value="Active" <?php if($data['status']=='Active') echo 'selected'; ?>>Active</option>
				<option value="Inactive" <?php if($data['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
			</select><br><br>
			<input type="submit" value="Update Route">
		</form>
	<?php elseif ($type == 'schedule'): ?>
		<form action="../../controllers/scheduleController.php?action=update" method="post">
			<input type="hidden" name="id" value="<?php echo (int) $data['id']; ?>">
			Bus: <select name="bus_id" required>
				<?php foreach ($buses as $bus): ?>
					<option value="<?php echo (int) $bus['id']; ?>" <?php if ((int) $bus['id'] === (int) $data['bus_id']) echo 'selected'; ?>><?php echo htmlspecialchars($bus['bus_number'] . ' (' . $bus['type'] . ', ' . $bus['seat_capacity'] . ' seats' . ($bus['status'] === 'Active' ? '' : ', inactive') . ')'); ?></option>
				<?php endforeach; ?>
			</select><br>
			Route: <select name="route_id" required>
				<?php foreach ($routes as $route): ?>
					<option value="<?php echo (int) $route['id']; ?>" <?php if ((int) $route['id'] === (int) $data['route_id']) echo 'selected'; ?>><?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination'] . ($route['status'] === 'Active' ? '' : ' (inactive)')); ?></option>
				<?php endforeach; ?>
			</select><br>
			Travel Date: <input type="date" name="travel_date" value="<?php echo fieldValue($data, 'travel_date'); ?>" required><br>
			Departure Time: <input type="time" name="departure_time" value="<?php echo substr($data['departure_time'], 0, 5); ?>" required><br>
			Arrival Time: <input type="time" name="arrival_time" value="<?php echo substr($data['arrival_time'], 0, 5); ?>" required><br>
			Fare: <input type="number" step="0.01" min="1" name="fare" value="<?php echo fieldValue($data, 'fare'); ?>" required><br>
			Status:
			<select name="status">
				<option value="Active" <?php if($data['status']=='Active') echo 'selected'; ?>>Active</option>
				<option value="Inactive" <?php if($data['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
			</select><br><br>
			<input type="submit" value="Update Schedule">
		</form>
	<?php endif; ?>

	<br>
	<a href="./<?php echo $type; ?>_list.php">Back to <?php echo ucfirst($type); ?> List</a> &nbsp;
	<a href="./dashboard.php">Back to Dashboard</a>
</body>
</html>