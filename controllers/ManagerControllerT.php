<?php
/**
 * ManagerController - Bus Ticket Management System
 * Part C: Booking Manager (counter staff)
 *
 * Dashboard with counter sales and seat allocation, and the
 * cancellation & refund queue.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
	session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/ManagerT.php';
require_once __DIR__ . '/../models/AnnouncementT.php';

// CSS class for a status badge, shared by both manager views.
function managerBadge($status) {
	$colors = [
		'confirmed' => 'green', 'paid' => 'green', 'completed' => 'green', 'settled' => 'green',
		'pending'   => 'yellow', 'approved' => 'blue',
		'cancelled' => 'red', 'void' => 'red', 'rejected' => 'red',
	];
	$key = strtolower($status);
	return 'badge-' . ($colors[$key] ?? 'grey');
}

class ManagerController {
	private $model;
	private $validator;

	public function __construct() {
		$this->model     = new Manager();
		$this->validator = new Validator();
		$this->requireManager();
	}

	// Only booking managers (and the admin) can open these pages.
	private function requireManager() {
		if (empty($_SESSION['user_id'])) {
			$_SESSION['flash_error'] = "Please log in as a booking manager.";
			header("Location: ../controllers/AuthController.php?action=login");
			exit;
		}
		if (!in_array($_SESSION['role'] ?? '', ['bookingmgr', 'admin'], true)) {
			$_SESSION['flash_error'] = "Access denied. Booking manager privileges required.";
			header("Location: ../controllers/BookingController.php?action=dashboard");
			exit;
		}
	}

	/**
	 * Booking overview: numbers, recent tickets, refund requests,
	 * and the counter form with the seat map.
	 */
	public function dashboard() {
		$schedules  = $this->model->getActiveSchedules();
		$scheduleId = isset($_REQUEST['schedule_id']) ? (int) $_REQUEST['schedule_id'] : ($schedules ? (int) $schedules[0]['id'] : 0);
		$error      = '';
		$old        = ['passenger_name' => '', 'phone' => '', 'payment_mode' => 'Cash', 'seat_no' => ''];

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$action = $_POST['action'] ?? '';
			$old    = [
				'passenger_name' => trim($_POST['passenger_name'] ?? ''),
				'phone'          => trim($_POST['phone'] ?? ''),
				'payment_mode'   => $_POST['payment_mode'] ?? 'Cash',
				'seat_no'        => strtoupper(trim($_POST['seat_no'] ?? '')),
			];

			if ($action === 'assign_seat') {
				$rules = [
					'seat_no'        => 'required',
					'passenger_name' => 'required|min:3',
					'phone'          => 'required|phone',
				];
				if ($this->validator->validate($old, $rules)) {
					$result = $this->model->assignSeat($scheduleId, $old['seat_no'], $old['passenger_name'], $old['phone'], $old['payment_mode'], (int) $_SESSION['user_id']);
				} else {
					$result = ['success' => false, 'message' => $this->validator->getFirstError()];
				}
			} elseif ($action === 'release_seat') {
				$result = $old['seat_no'] === ''
					? ['success' => false, 'message' => "Please click a counter-sold seat on the seat map to release it."]
					: $this->model->releaseSeat($scheduleId, $old['seat_no']);
			} else {
				$result = ['success' => false, 'message' => "Unknown action."];
			}

			if ($result['success']) {
				$_SESSION['flash_success'] = $result['message'];
				header("Location: ../controllers/ManagerControllerT.php?action=dashboard&schedule_id=" . $scheduleId);
				exit;
			}
			$error = $result['message'];
		}

		$dbConnected  = $this->model->isConnected();
		$stats        = $this->model->getStats();
		$reservations = $this->model->getRecentReservations(5);
		$refunds      = $this->model->getRefunds(5);
		$schedule     = $scheduleId ? $this->model->getSchedule($scheduleId) : null;
		$seatMap      = $this->model->getSeatMap($schedule);
		$paymentModes = $this->model->getPaymentModes();
		$announcements = (new Announcement())->getLiveFor(['All', 'Manager'], 3);

		require __DIR__ . '/../views/bookingmgr/dashboard.php';
	}

	/**
	 * Cancellation & refund queue: approve with a deduction, or reject.
	 */
	public function refunds() {
		$error = '';

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$rules = [
				'refund_id' => 'required|numeric',
				'deduction' => 'required|numeric',
				'status'    => 'required',
				'method'    => 'required',
			];
			if ($this->validator->validate($_POST, $rules)) {
				$result = $this->model->processRefund((int) $_POST['refund_id'], $_POST['status'], $_POST['deduction'], $_POST['method'], (int) $_SESSION['user_id']);
			} else {
				$result = ['success' => false, 'message' => $this->validator->getFirstError()];
			}

			if ($result['success']) {
				$_SESSION['flash_success'] = $result['message'];
				header("Location: ../controllers/ManagerControllerT.php?action=refunds");
				exit;
			}
			$error = $result['message'];
		}

		$dbConnected   = $this->model->isConnected();
		$refunds       = $this->model->getRefunds();
		$refundMethods = $this->model->getRefundMethods();

		require __DIR__ . '/../views/bookingmgr/refund_list.php';
	}
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
	$controller = new ManagerController();
	$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
	if (in_array($action, ['dashboard', 'refunds'], true)) {
		$controller->$action();
	} else {
		$controller->dashboard();
	}
}
