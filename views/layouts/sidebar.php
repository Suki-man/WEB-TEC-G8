<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="sidebar">
	<h3>Navigation</h3>
	<ul>
		<li><a href="../busroute/dashboard.php">Dashboard</a></li>
		<li><a href="../busroute/bus_list.php">Manage Buses</a></li>
		<li><a href="../busroute/route_list.php">Manage Routes</a></li>
		<li><a href="../busroute/schedule_list.php">Manage Schedules</a></li>
	</ul>
	<br>
	<form action="../../controller/logout-handler.php" method="post">
		<input type="submit" value="Logout">
	</form>
</div>