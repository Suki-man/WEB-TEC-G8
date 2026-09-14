<?php
session_start();
require __DIR__ . '/guardT.php';
require_once __DIR__ . '/../../models/RouteT.php';
// always read the list fresh from the database
$routesList = (new Route())->getAllRoutes();
?>
<html>
<head>
	<title>Route List</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Route List</h1>
	<h2>Hello, <?php echo htmlspecialchars($_SESSION['username']) ?></h2>
	<?php busrouteFlash(); ?>
	<style>
		table, th, td {
			border: 1px solid black;
			border-collapse: collapse;
			padding: 5px 10px;
		}
	</style>
	<table>
		<a class="btn" href="./route_form.php">Create New Route</a>
		<br><br>
		<tr>
			<th>ID</th>
			<th>Origin</th>
			<th>Destination</th>
			<th>Distance (KM)</th>
			<th>Estimated Duration</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
		<?php
		for ($i = 0; $i < count($routesList); $i++) {
			$id = (int) $routesList[$i]['id'];
			$origin = htmlspecialchars($routesList[$i]['origin']);
			$destination = htmlspecialchars($routesList[$i]['destination']);
			$distance_km = htmlspecialchars($routesList[$i]['distance_km']);
			$est_duration = htmlspecialchars($routesList[$i]['est_duration']);
			$status = htmlspecialchars($routesList[$i]['status']);

			$id_td = "<td>$id</td>";
			$origin_td = "<td>$origin</td>";
			$destination_td = "<td>$destination</td>";
			$distance_td = "<td>$distance_km</td>";
			$duration_td = "<td>$est_duration</td>";
			$status_td = "<td>$status</td>";

			$edit = "<a class='btn' href='./edit.php?type=route&amp;id=$id'>Edit</a>";
			$delete = "<form action='../../controllers/routeController.php?action=delete' method='post' onsubmit=\"return confirm('Delete this route?');\"> <input type='hidden' name='id' value='$id'> <input type='submit' value='Delete'> </form>";
			$action_td = "<td> $edit $delete </td>";

			echo "<tr> $id_td $origin_td $destination_td $distance_td $duration_td $status_td $action_td </tr>";
		}
		?>
	</table>
	<br>
	<a href="./dashboard.php">Dashboard</a>
</body>
</html>