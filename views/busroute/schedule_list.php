<?php
session_start();
require __DIR__ . '/guardT.php';
require_once __DIR__ . '/../../models/ScheduleT.php';
// always read the list fresh from the database, with bus numbers and route names
$schedulesList = (new Schedule())->getAllSchedulesWithNames();
?>
<html>
<head>
	<title>Schedule List</title>
	<link rel='stylesheet' href='../../public/css/busrouteT.css'>
</head>
<body>
	<?php include __DIR__ . '/navT.php'; ?>
	<h1>Schedule List</h1>
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
		<a class="btn" href="./schedule_form.php">Create New Schedule</a>
		<br><br>
		<tr>
			<th>ID</th>
			<th>Bus</th>
			<th>Route</th>
			<th>Travel Date</th>
			<th>Departure Time</th>
			<th>Arrival Time</th>
			<th>Fare</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
		<?php
		for ($i = 0; $i < count($schedulesList); $i++) {
			$id = (int) $schedulesList[$i]['id'];
			$bus_number = htmlspecialchars($schedulesList[$i]['bus_number']);
			$route_name = htmlspecialchars($schedulesList[$i]['origin'] . ' → ' . $schedulesList[$i]['destination']);
			$travel_date = htmlspecialchars($schedulesList[$i]['travel_date']);
			$departure_time = substr($schedulesList[$i]['departure_time'], 0, 5);
			$arrival_time = substr($schedulesList[$i]['arrival_time'], 0, 5);
			$fare = htmlspecialchars($schedulesList[$i]['fare']);
			$status = htmlspecialchars($schedulesList[$i]['status']);

			$id_td = "<td>$id</td>";
			$bus_td = "<td>$bus_number</td>";
			$route_td = "<td>$route_name</td>";
			$date_td = "<td>$travel_date</td>";
			$dep_td = "<td>$departure_time</td>";
			$arr_td = "<td>$arrival_time</td>";
			$fare_td = "<td>$fare</td>";
			$status_td = "<td>$status</td>";

			$edit = "<a class='btn' href='./edit.php?type=schedule&amp;id=$id'>Edit</a>";
			$delete = "<form action='../../controllers/scheduleController.php?action=delete' method='post' onsubmit=\"return confirm('Delete this schedule?');\"> <input type='hidden' name='id' value='$id'> <input type='submit' value='Delete'> </form>";
			$action_td = "<td> $edit $delete </td>";

			echo "<tr> $id_td $bus_td $route_td $date_td $dep_td $arr_td $fare_td $status_td $action_td </tr>";
		}
		?>
	</table>
	<br>
	<a href="./dashboard.php">Dashboard</a>
</body>
</html>