<?php
session_start();
require __DIR__ . '/guardT.php';
require_once __DIR__ . '/../../models/BusT.php';
// always read the list fresh from the database
$busesList = (new Bus())->getAllBuses();
?>
<html>
<head>
	<title>Bus List</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Bus List</h1>
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
		<a class="btn" href="./bus_form.php">Create New Bus</a>
		<br><br>
		<tr>
			<th>ID</th>
			<th>Bus Number</th>
			<th>Type</th>
			<th>Seat Capacity</th>
			<th>Layout</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
		<?php
		for ($i = 0; $i < count($busesList); $i++) {
			$id = (int) $busesList[$i]['id'];
			$bus_number = htmlspecialchars($busesList[$i]['bus_number']);
			$type = htmlspecialchars($busesList[$i]['type']);
			$seat_capacity = (int) $busesList[$i]['seat_capacity'];
			$layout = htmlspecialchars($busesList[$i]['layout']);
			$status = htmlspecialchars($busesList[$i]['status']);

			$id_td = "<td>$id</td>";
			$bus_td = "<td>$bus_number</td>";
			$type_td = "<td>$type</td>";
			$cap_td = "<td>$seat_capacity</td>";
			$layout_td = "<td>$layout</td>";
			$status_td = "<td>$status</td>";

			$edit = "<a class='btn' href='./edit.php?type=bus&amp;id=$id'>Edit</a>";
			$delete = "<form action='../../controllers/busController.php?action=delete' method='post' onsubmit=\"return confirm('Delete this bus?');\"> <input type='hidden' name='id' value='$id'> <input type='submit' value='Delete'> </form>";
			$action_td = "<td> $edit $delete </td>";

			echo "<tr> $id_td $bus_td $type_td $cap_td $layout_td $status_td $action_td </tr>";
		}
		?>
	</table>
	<br>
	<a href="./dashboard.php">Dashboard</a>
</body>
</html>