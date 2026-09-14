<?php
require_once __DIR__ . '/../models/BusT.php';
require_once __DIR__ . '/../core/ValidatorT.php';
session_start();

// only bus & route staff and the admin can change buses
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['busroute', 'admin'], true)) {
	$_SESSION['flash_error'] = 'Please log in as bus & route staff.';
	header('Location: AuthController.php?action=login');
	exit();
}

$action = $_GET['action'] ?? 'create';
$bus = new Bus();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action === 'delete') {
	$result = $bus->deleteBus((int) ($_POST['id'] ?? 0));
	if ($result === true) {
		$_SESSION['flash_success'] = 'Bus deleted.';
	} else {
		$_SESSION['flash_error'] = 'Unable to delete the bus: ' . Validator::dbMessage($result, 'bus');
	}
	header('Location: ../views/busroute/bus_list.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$id = (int) ($_POST['id'] ?? 0);
	$bus_number = trim($_POST['bus_number'] ?? '');
	$type = trim($_POST['type'] ?? '');
	$seat_capacity = (int) ($_POST['seat_capacity'] ?? 0);
	$layout = trim($_POST['layout'] ?? '');
	$status = $_POST['status'] ?? '';

	// where to go back to when something is wrong
	$back = $action === 'update' ? "../views/busroute/edit.php?type=bus&id=$id" : '../views/busroute/bus_form.php';

	$validator = new Validator();
	$error = '';
	if (!$validator->validate($_POST, ['bus_number' => 'required', 'type' => 'required', 'seat_capacity' => 'required|numeric', 'layout' => 'required', 'status' => 'required'])) {
		$error = $validator->getFirstError();
	} elseif ($seat_capacity < 1 || $seat_capacity > 40) {
		$error = 'Seat capacity must be between 1 and 40 (seats A1 to J4).';
	} elseif (!in_array($status, ['Active', 'Inactive'], true)) {
		$error = 'Please choose Active or Inactive.';
	}

	if ($error === '') {
		if ($action === 'update') {
			$result = $bus->updateBus($id, $bus_number, $type, $seat_capacity, $layout, $status);
			$saved = $result === true;
		} else {
			$result = $bus->insertBus($bus_number, $type, $seat_capacity, $layout, $status);
			$saved = is_int($result);
		}
		if (!$saved) {
			$error = Validator::dbMessage($result, 'bus');
		}
	}

	if ($error === '') {
		unset($_SESSION['insert_error']);
		$_SESSION['flash_success'] = $action === 'update' ? "Bus $bus_number updated." : "Bus $bus_number created.";
		header('Location: ../views/busroute/bus_list.php');
	} else {
		$_SESSION['insert_error'] = 'Unable to save the bus: ' . $error;
		header("Location: $back");
	}
	exit();
}

header('Location: ../views/busroute/bus_list.php');