<?php // Top bar for the Bus & Route pages. Included after guardT.php. ?>
<nav class="br-nav">
	<strong>🚌 Bus &amp; Route Management</strong>
	<span>
		<a href="dashboard.php">Dashboard</a>
		<a href="bus_list.php">Buses</a>
		<a href="route_list.php">Routes</a>
		<a href="schedule_list.php">Schedules</a>
		<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
			<a href="../../controllers/UserController.php?action=index">Admin</a>
		<?php endif; ?>
		<a href="../../controllers/AuthController.php?action=logout">Log out (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
	</span>
</nav>
