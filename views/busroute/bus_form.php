<?php
session_start();
require __DIR__ . '/guardT.php';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Create Bus</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h1>

	<h2>Create New Bus</h2>
	<?php busrouteFlash(); ?>
	<form action="../../controllers/busController.php?action=create" method="post">
		Bus Number: <input type="text" name="bus_number" id="bus_number" required>
		<br>
		Type: <input type="text" name="type" id="type" placeholder="e.g. AC, Non-AC, Sleeper" required>
		<br>
		Seat Capacity: <input type="number" name="seat_capacity" id="seat_capacity" min="1" max="40" placeholder="1 to 40" required>
		<br>
		Layout: <input type="text" name="layout" id="layout" placeholder="e.g. 2x2" required>
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
	<a href="./bus_list.php">Back to Bus List</a>
</body>
</html>