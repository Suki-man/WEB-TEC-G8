<?php
// Shared check for the Bus & Route pages (views/busroute): only bus & route
// staff and the admin may open them. Everyone else is sent to the login page.
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['busroute', 'admin'], true)) {
	$_SESSION['flash_error'] = "Please log in as bus & route staff to manage buses, routes and schedules.";
	header("Location: ../../controllers/AuthController.php?action=login");
	exit();
}

// the pages greet $_SESSION['username']
if (empty($_SESSION['username'])) {
	$_SESSION['username'] = $_SESSION['user_name'];
}

// Shows, then clears, the message a controller left after saving or deleting.
function busrouteFlash() {
	$messages = ['flash_success' => 'flash-success', 'flash_error' => 'flash-error', 'insert_error' => 'flash-error'];
	foreach ($messages as $key => $class) {
		if (!empty($_SESSION[$key])) {
			echo "<p class='$class'>" . htmlspecialchars($_SESSION[$key]) . "</p>";
			unset($_SESSION[$key]);
		}
	}
}
