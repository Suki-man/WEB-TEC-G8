<?php
// Top bar for the announcement pages. Rendered from controllers/, so the
// links start with ../controllers/.
$navRole = $_SESSION['role'] ?? '';
?>
<nav class="navbar">
	<div class="brand">📢 Announcements</div>
	<div>
		<?php if ($navRole === 'admin'): ?>
			<a href="../controllers/UserController.php?action=index">Manage Users</a>
		<?php else: ?>
			<a href="../controllers/ManagerControllerT.php?action=dashboard">Manager Dashboard</a>
			<a href="../controllers/ManagerControllerT.php?action=refunds">Refunds</a>
		<?php endif; ?>
		<a href="../controllers/AnnouncementControllerT.php?action=index" class="active">Announcements</a>
		<a href="../controllers/HomeController.php" target="_blank">View Website</a>
		<a href="../controllers/AuthController.php?action=logout">Logout (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
	</div>
</nav>
