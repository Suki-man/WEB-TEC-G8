<?php
session_start();
require __DIR__ . '/guardT.php';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Create Route</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h1>

	<h2>Create New Route</h2>
	<?php busrouteFlash(); ?>
	<form action="../../controllers/routeController.php?action=create" method="post">
		Origin: <input type="text" name="origin" id="origin" required>
		<br>
		Destination: <input type="text" name="destination" id="destination" required>
		<br>
		Distance (KM): <input type="number" step="0.01" min="0.01" name="distance_km" id="distance_km" required>
		<br>
		Estimated Duration: <input type="text" name="est_duration" id="est_duration" placeholder="e.g. 4 hours" required>
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
	<a href="./route_list.php">Back to Route List</a>
</body>
</html>