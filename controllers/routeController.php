<?php
require_once __DIR__ . '/../models/RouteT.php';
require_once __DIR__ . '/../core/ValidatorT.php';
session_start();

// only bus & route staff and the admin can change routes
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['busroute', 'admin'], true)) {
	$_SESSION['flash_error'] = 'Please log in as bus & route staff.';
	header('Location: AuthController.php?action=login');
	exit();
}

$action = $_GET['action'] ?? 'create';
$route = new Route();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action === 'delete') {
	$result = $route->deleteRoute((int) ($_POST['id'] ?? 0));
	if ($result === true) {
		$_SESSION['flash_success'] = 'Route deleted.';
	} else {
		$_SESSION['flash_error'] = 'Unable to delete the route: ' . Validator::dbMessage($result, 'route');
	}
	header('Location: ../views/busroute/route_list.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$id = (int) ($_POST['id'] ?? 0);
	$origin = trim($_POST['origin'] ?? '');
	$destination = trim($_POST['destination'] ?? '');
	$distance_km = (float) ($_POST['distance_km'] ?? 0);
	$est_duration = trim($_POST['est_duration'] ?? '');
	$status = $_POST['status'] ?? '';

	// where to go back to when something is wrong
	$back = $action === 'update' ? "../views/busroute/edit.php?type=route&id=$id" : '../views/busroute/route_form.php';

	$validator = new Validator();
	$error = '';
	if (!$validator->validate($_POST, ['origin' => 'required', 'destination' => 'required', 'distance_km' => 'required|numeric', 'est_duration' => 'required', 'status' => 'required'])) {
		$error = $validator->getFirstError();
	} elseif (strcasecmp($origin, $destination) === 0) {
		$error = 'Origin and destination must be different cities.';
	} elseif ($distance_km <= 0) {
		$error = 'Distance must be more than 0 km.';
	} elseif (!in_array($status, ['Active', 'Inactive'], true)) {
		$error = 'Please choose Active or Inactive.';
	}

	if ($error === '') {
		if ($action === 'update') {
			$result = $route->updateRoute($id, $origin, $destination, $distance_km, $est_duration, $status);
			$saved = $result === true;
		} else {
			$result = $route->insertRoute($origin, $destination, $distance_km, $est_duration, $status);
			$saved = is_int($result);
		}
		if (!$saved) {
			$error = Validator::dbMessage($result, 'route');
		}
	}

	if ($error === '') {
		unset($_SESSION['insert_error']);
		$_SESSION['flash_success'] = $action === 'update' ? "Route $origin → $destination updated." : "Route $origin → $destination created.";
		header('Location: ../views/busroute/route_list.php');
	} else {
		$_SESSION['insert_error'] = 'Unable to save the route: ' . $error;
		header("Location: $back");
	}
	exit();
}

header('Location: ../views/busroute/route_list.php');