<?php
require_once __DIR__ . '/../models/ScheduleT.php';
require_once __DIR__ . '/../core/ValidatorT.php';
session_start();

// only bus & route staff and the admin can change schedules
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['busroute', 'admin'], true)) {
	$_SESSION['flash_error'] = 'Please log in as bus & route staff.';
	header('Location: AuthController.php?action=login');
	exit();
}

$action = $_GET['action'] ?? 'create';
$schedule = new Schedule();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action === 'delete') {
	$result = $schedule->deleteSchedule((int) ($_POST['id'] ?? 0));
	if ($result === true) {
		$_SESSION['flash_success'] = 'Schedule deleted.';
	} else {
		$_SESSION['flash_error'] = 'Unable to delete the schedule: ' . Validator::dbMessage($result, 'schedule');
	}
	header('Location: ../views/busroute/schedule_list.php');
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$id = (int) ($_POST['id'] ?? 0);
	$bus_id = (int) ($_POST['bus_id'] ?? 0);
	$route_id = (int) ($_POST['route_id'] ?? 0);
	$travel_date = trim($_POST['travel_date'] ?? '');
	$departure_time = trim($_POST['departure_time'] ?? '');
	$arrival_time = trim($_POST['arrival_time'] ?? '');
	$fare = (float) ($_POST['fare'] ?? 0);
	$status = $_POST['status'] ?? '';

	// where to go back to when something is wrong
	$back = $action === 'update' ? "../views/busroute/edit.php?type=schedule&id=$id" : '../views/busroute/schedule_form.php';

	$validator = new Validator();
	$error = '';
	if (!$validator->validate($_POST, ['bus_id' => 'required|numeric', 'route_id' => 'required|numeric', 'travel_date' => 'required|date', 'departure_time' => 'required', 'arrival_time' => 'required', 'fare' => 'required|numeric', 'status' => 'required'])) {
		$error = $validator->getFirstError();
	} elseif ($fare <= 0) {
		$error = 'Fare must be more than 0.';
	} elseif (!in_array($status, ['Active', 'Inactive'], true)) {
		$error = 'Please choose Active or Inactive.';
	}

	if ($error === '') {
		if ($action === 'update') {
			$result = $schedule->updateSchedule($id, $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status);
			$saved = $result === true;
		} else {
			$result = $schedule->insertSchedule($bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status);
			$saved = is_int($result);
		}
		if (!$saved) {
			$error = Validator::dbMessage($result, 'schedule');
		}
	}

	if ($error === '') {
		unset($_SESSION['insert_error']);
		$_SESSION['flash_success'] = $action === 'update' ? "Schedule #$id updated." : "Schedule for $travel_date created.";
		header('Location: ../views/busroute/schedule_list.php');
	} else {
		$_SESSION['insert_error'] = 'Unable to save the schedule: ' . $error;
		header("Location: $back");
	}
	exit();
}

header('Location: ../views/busroute/schedule_list.php');