<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Bus Management System</title>
	<link rel='stylesheet' href='../../public/css/style.css'>
</head>
<body>
	<header>
		<h1>Bus Management System</h1>
		<?php if (isset($_SESSION['username'])): ?>
			<p>Welcome, <?php echo $_SESSION['username']; ?></p>
		<?php endif; ?>
	</header>
	<hr>