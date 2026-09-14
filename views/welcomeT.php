<?php
session_start();

// Opening the welcome page logs out any old login (for example from the
// allPages test links), so Log in and Register always show their forms
// instead of jumping to that account's dashboard.
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_phone'], $_SESSION['role'], $_SESSION['username'], $_SESSION['guest'], $_SESSION['return_to']);

//talha
// The welcome page only offers Log in and Register. After logging in,
// AuthController sends each account to its own pages based on its role.
$options = [
	['title' => 'Log in',   'desc' => 'Already have an account? Log in to continue.',      'link' => '../controllers/AuthController.php?action=login'],
	['title' => 'Register', 'desc' => 'New here? Create a passenger account to book seats.', 'link' => '../controllers/AuthController.php?action=register'],
	['title' => 'Continue as guest', 'desc' => 'Look at trips and seat maps first. Log in when you want to book.', 'link' => '../controllers/AuthController.php?action=guest'],
];
//talha
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>Welcome - Bus Management System</title>
	<link rel='stylesheet' href='../public/css/style.css'>
	<style>
		.welcome {
			max-width: 860px;
			margin: 60px auto;
			text-align: center;
		}
		.welcome h1 {
			margin-bottom: 6px;
		}
		.welcome .subtitle {
			color: #666;
			margin-bottom: 36px;
		}
		.options {
			display: flex;
			flex-wrap: wrap;
			gap: 20px;
			justify-content: center;
		}
		.option {
			flex: 1 1 220px;
			max-width: 260px;
			padding: 28px 20px;
			background-color: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			color: #333;
			text-decoration: none;
		}
		.option:hover {
			border-color: #4a6fa5;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
		}
		.option h2 {
			margin: 0 0 10px;
			color: #4a6fa5;
		}
		.option p {
			margin: 0;
			font-size: 14px;
			color: #666;
		}
		<?php //talha ?>
		.all-pages {
			display: inline-block;
			margin-top: 28px;
			padding: 5px 12px;
			font-size: 12px;
			background-color: #4a6fa5;
			color: #fff;
			border-radius: 4px;
			text-decoration: none;
		}
		.all-pages:hover {
			background-color: #33507a;
		}
		<?php //talha ?>
	</style>
</head>
<body>
	<div class="welcome">
		<h1>Bus Management System</h1>
		<p class="subtitle">Welcome! Please log in or create an account.<?php //talha ?></p>

		<div class="options">
			<?php foreach ($options as $option): ?>
				<a class="option" href="<?php echo htmlspecialchars($option['link']); ?>">
					<h2><?php echo htmlspecialchars($option['title']); ?></h2>
					<p><?php echo htmlspecialchars($option['desc']); ?></p>
				</a>
			<?php endforeach; ?>
		</div>

		<?php //talha ?>
		<a class="all-pages" href="allPagesT.php">Open all pages (testing)</a> <?php //talha ?>
		<?php //talha ?>
	</div>
</body>
</html>
