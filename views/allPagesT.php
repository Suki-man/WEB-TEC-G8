<?php
session_start();

//talha
require_once __DIR__ . '/../models/UserT.php';

// ?as=<role>&go=<page> logs in as the first sample account with that role
// (passenger, admin, busroute, bookingmgr) and opens the page, so the
// login-protected pages can be tested without typing a password.
// ?as=guest logs the sample account out first.
if (isset($_GET['as'])) {
	$as = $_GET['as'];
	$go = isset($_GET['go']) ? $_GET['go'] : 'allPagesT.php';

	// only follow links to pages inside this project
	if (!preg_match('#^(\.\./controllers/[A-Za-z]+\.php|busroute/[A-Za-z_]+\.php|allPagesT\.php)(\?[A-Za-z0-9_=&%-]*)?$#', $go)) {
		$go = 'allPagesT.php';
	}

	unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_phone'], $_SESSION['role'], $_SESSION['username'], $_SESSION['guest'], $_SESSION['return_to']);

	if ($as !== 'guest') {
		// uses the database, or the sample accounts in preview mode
		$accounts = (new User())->filterUsers($as, 'active');
		if ($accounts) {
			$account = $accounts[0];
			$_SESSION['user_id']    = $account['id'];
			$_SESSION['user_name']  = $account['name'];
			$_SESSION['user_email'] = $account['email'];
			$_SESSION['user_phone'] = $account['phone'];
			$_SESSION['role']       = $account['role'];
		}
	}

	header('Location: ' . $go);
	exit();
}

// A link that opens $target as the sample account with this role.
function openAs($role, $target) {
	return 'allPagesT.php?as=' . urlencode($role) . '&go=' . urlencode($target);
}
//talha

require_once __DIR__ . '/../models/BusT.php';
require_once __DIR__ . '/../models/RouteT.php';
require_once __DIR__ . '/../models/ScheduleT.php';

// The list pages only show what is stored in the session, so load it fresh
// from the database every time this page opens.
$dbError   = '';
$buses     = [];
$routes    = [];
$schedules = [];
try {
	$buses     = (new Bus())->getAllBuses();
	$routes    = (new Route())->getAllRoutes();
	$schedules = (new Schedule())->getAllSchedules();
	$_SESSION['busesList']     = $buses;
	$_SESSION['routesList']    = $routes;
	$_SESSION['schedulesList'] = $schedules;
} catch (Throwable $e) {
	$dbError = $e->getMessage();
}

$sections = [
	[
		'title' => 'Buses',
		'count' => count($buses),
		'links' => [
			['label' => 'Bus list',       'href' => openAs('busroute', 'busroute/bus_list.php')],
			['label' => 'Create new bus', 'href' => openAs('busroute', 'busroute/bus_form.php')],
		],
		'edit'  => $buses ? openAs('busroute', 'busroute/edit.php?type=bus&id=' . $buses[0]['id']) : '',
	],
	[
		'title' => 'Routes',
		'count' => count($routes),
		'links' => [
			['label' => 'Route list',       'href' => openAs('busroute', 'busroute/route_list.php')],
			['label' => 'Create new route', 'href' => openAs('busroute', 'busroute/route_form.php')],
		],
		'edit'  => $routes ? openAs('busroute', 'busroute/edit.php?type=route&id=' . $routes[0]['id']) : '',
	],
	[
		'title' => 'Schedules',
		'count' => count($schedules),
		'links' => [
			['label' => 'Schedule list',       'href' => openAs('busroute', 'busroute/schedule_list.php')],
			['label' => 'Create new schedule', 'href' => openAs('busroute', 'busroute/schedule_form.php')],
		],
		'edit'  => $schedules ? openAs('busroute', 'busroute/edit.php?type=schedule&id=' . $schedules[0]['id']) : '',
	],
];

//talha
// Ids for the "book", "change seat" and "edit" links below (0 = none yet).
$ids = ['schedule' => 0, 'booking' => 0, 'traveller' => 0, 'watch' => 0, 'user' => 0];
if (Preview::isOn()) {
	// the ids of the sample rows in PreviewT.php
	$ids = ['schedule' => 1, 'booking' => 1, 'traveller' => 1, 'watch' => 1, 'user' => 2];
} elseif ($dbError === '') {
	try {
		$conn  = Preview::connect();
		$first = function ($sql) use ($conn) {
			$row = $conn->query($sql)->fetch_row();
			return $row ? (int) $row[0] : 0;
		};
		$passengerId      = $first("SELECT id FROM users WHERE role = 'passenger' AND status = 'active' ORDER BY id LIMIT 1");
		$ids['schedule']  = $first("SELECT id FROM schedules WHERE status = 'Active' AND travel_date >= CURDATE() ORDER BY travel_date, departure_time LIMIT 1");
		$ids['booking']   = $first("SELECT id FROM bookings WHERE user_id = $passengerId AND status = 'Confirmed' ORDER BY id LIMIT 1");
		$ids['traveller'] = $first("SELECT id FROM travellers WHERE user_id = $passengerId ORDER BY id LIMIT 1");
		$ids['watch']     = $first("SELECT id FROM watchlist WHERE user_id = $passengerId ORDER BY id LIMIT 1");
		$ids['user']      = $first("SELECT id FROM users WHERE role <> 'admin' ORDER BY id LIMIT 1");
	} catch (Throwable $e) {
		$dbError = $e->getMessage();
	}
}

$passengerLinks = [
	['label' => 'Dashboard',   'href' => openAs('passenger', '../controllers/BookingController.php?action=dashboard')],
	['label' => 'My bookings', 'href' => openAs('passenger', '../controllers/BookingController.php?action=index')],
];
if ($ids['schedule']) {
	$passengerLinks[] = ['label' => 'Book a seat (seat map)', 'href' => openAs('passenger', '../controllers/BookingController.php?action=book&schedule_id=' . $ids['schedule'])];
}
if ($ids['booking']) {
	$passengerLinks[] = ['label' => 'Change seat on a booking', 'href' => openAs('passenger', '../controllers/BookingController.php?action=changeSeat&booking_id=' . $ids['booking'])];
}
$passengerLinks[] = ['label' => 'Watchlist',        'href' => openAs('passenger', '../controllers/WatchlistController.php?action=index')];
$passengerLinks[] = ['label' => 'Add to watchlist', 'href' => openAs('passenger', '../controllers/WatchlistController.php?action=create')];
if ($ids['watch']) {
	$passengerLinks[] = ['label' => 'Edit a watchlist route', 'href' => openAs('passenger', '../controllers/WatchlistController.php?action=edit&id=' . $ids['watch'])];
}
$passengerLinks[] = ['label' => 'Co-travellers',     'href' => openAs('passenger', '../controllers/TravellerController.php?action=index')];
$passengerLinks[] = ['label' => 'Add co-traveller', 'href' => openAs('passenger', '../controllers/TravellerController.php?action=create')];
if ($ids['traveller']) {
	$passengerLinks[] = ['label' => 'Edit a co-traveller', 'href' => openAs('passenger', '../controllers/TravellerController.php?action=edit&id=' . $ids['traveller'])];
}

$adminLinks = [
	['label' => 'All user accounts',  'href' => openAs('admin', '../controllers/UserController.php?action=index')],
	['label' => 'Create an account', 'href' => openAs('admin', '../controllers/UserController.php?action=create')],
];
if ($ids['user']) {
	$adminLinks[] = ['label' => 'Edit an account', 'href' => openAs('admin', '../controllers/UserController.php?action=edit&id=' . $ids['user'])];
}

$adminLinks[] = ['label' => 'Announcements', 'href' => openAs('admin', '../controllers/AnnouncementControllerT.php?action=index')];

$groups = [
	[
		'title' => 'Anyone',
		'note'  => 'Not logged in',
		'links' => [
			['label' => 'Search buses (home page)', 'href' => openAs('guest', '../controllers/HomeController.php')],
			['label' => 'Log in',                   'href' => openAs('guest', '../controllers/AuthController.php?action=login')],
			['label' => 'Register',                 'href' => openAs('guest', '../controllers/AuthController.php?action=register')],
			['label' => 'Continue as guest',        'href' => openAs('guest', '../controllers/AuthController.php?action=guest')],
			['label' => 'Seat map as a guest',      'href' => openAs('guest', '../controllers/BookingController.php?action=book&schedule_id=' . ($ids['schedule'] ? $ids['schedule'] : 1))],
		],
	],
	['title' => 'Passenger', 'note' => 'Opens as the sample passenger', 'links' => $passengerLinks],
	['title' => 'Admin',     'note' => 'Opens as the sample admin',     'links' => $adminLinks],
	[
		'title' => 'Booking Manager',
		'note'  => 'Opens as the sample booking manager',
		'links' => [
			['label' => 'Dashboard & seat allocation', 'href' => openAs('bookingmgr', '../controllers/ManagerControllerT.php?action=dashboard')],
			['label' => 'Cancellations & refunds',     'href' => openAs('bookingmgr', '../controllers/ManagerControllerT.php?action=refunds')],
			['label' => 'Announcements',               'href' => openAs('bookingmgr', '../controllers/AnnouncementControllerT.php?action=index')],
		],
	],
];
//talha
?>
<!DOCTYPE html>
<html lang='en'>
<head>
	<meta charset='utf-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>All Pages - Bus Management System</title>
	<link rel='stylesheet' href='../public/css/style.css'>
	<style>
		.hub {
			max-width: 900px;
			margin: 30px auto;
		}
		.hub .note {
			background-color: #fff8e5;
			border: 1px solid #f0d9a0;
			border-radius: 6px;
			padding: 10px 14px;
			font-size: 14px;
		}
		.hub .error {
			background-color: #fdeaea;
			border: 1px solid #f0c5c5;
			border-radius: 6px;
			padding: 10px 14px;
		}
		.cards {
			display: flex;
			flex-wrap: wrap;
			gap: 16px;
			margin-top: 20px;
		}
		.card {
			flex: 1 1 240px;
			background-color: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 16px 18px;
		}
		.card h2 {
			margin: 0 0 4px;
			color: #4a6fa5;
		}
		.card .count {
			color: #666;
			font-size: 14px;
			margin: 0 0 12px;
		}
		.card a {
			display: block;
			margin-bottom: 6px;
		}
		.dash {
			display: inline-block;
			margin-top: 20px;
			padding: 10px 18px;
			background-color: #4a6fa5;
			color: #fff;
			border-radius: 6px;
			text-decoration: none;
		}
		<?php //talha ?>
		.section {
			margin: 34px 0 0;
			padding-bottom: 6px;
			border-bottom: 2px solid #4a6fa5;
			color: #1f3864;
		}
		<?php //talha ?>
	</style>
</head>
<body>
	<div class="hub">
		<h1>All Pages</h1> <?php //talha ?>

		<p class="note">
			<?php //talha ?>
			Every link logs in as a sample account from <strong>busdbT.sql</strong> (or as a guest) and opens the page.
			To log in yourself: admin / <strong>admin</strong>, other accounts use password <strong>12345678</strong>.
			<?php //talha ?>
		</p>

		<?php //talha ?>
		<?php if (Preview::isOn()): ?>
			<p class="error">
				<strong>Preview mode:</strong> the <strong>bus_management</strong> database is not set up yet,
				so every page shows sample data and nothing you submit is saved.
				Import <strong>busdbT.sql</strong> when you are ready to connect.
			</p>
		<?php endif; ?>
		<?php //talha ?>

		<?php if ($dbError !== ''): ?>
			<p class="error">
				Could not read the database: <?php echo htmlspecialchars($dbError); ?><br>
				Import <strong>busdbT.sql</strong> in phpMyAdmin, then reload this page.
			</p>
		<?php endif; ?>

		<?php //talha ?>
		<h2 class="section">Passenger &amp; Admin pages</h2>
		<div class="cards">
			<?php foreach ($groups as $group): ?>
				<div class="card">
					<h2><?php echo $group['title']; ?></h2>
					<p class="count"><?php echo $group['note']; ?></p>
					<?php foreach ($group['links'] as $link): ?>
						<a href="<?php echo htmlspecialchars($link['href']); ?>"><?php echo $link['label']; ?></a>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<h2 class="section">Bus &amp; Route pages</h2>
		<?php //talha ?>

		<a class="dash" href="<?php echo htmlspecialchars(openAs('busroute', 'busroute/dashboard.php')); ?>">Open the Dashboard</a>

		<div class="cards">
			<?php foreach ($sections as $section): ?>
				<div class="card">
					<h2><?php echo $section['title']; ?></h2>
					<p class="count"><?php echo $section['count']; ?> in the database</p>
					<?php foreach ($section['links'] as $link): ?>
						<a href="<?php echo htmlspecialchars($link['href']); ?>"><?php echo $link['label']; ?></a>
					<?php endforeach; ?>
					<?php if ($section['edit'] !== ''): ?>
						<a href="<?php echo htmlspecialchars($section['edit']); ?>">Edit the first one</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<p><br><a href="welcomeT.php">&larr; Back to the welcome page</a></p>
	</div>
</body>
</html>
