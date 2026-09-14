<?php
session_start();
require __DIR__ . '/guardT.php';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Dashboard</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h1>
	<h2>Bus Management Dashboard</h2>
	<?php busrouteFlash(); ?>

	<ul>
		<li><a href="./bus_list.php">Manage Buses</a></li>
		<li><a href="./route_list.php">Manage Routes</a></li>
		<li><a href="./schedule_list.php">Manage Schedules</a></li>
	</ul>

	<br>
	<a class="btn" href="../../controllers/AuthController.php?action=logout">Logout</a>
</body>
</html>