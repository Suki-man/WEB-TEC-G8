<?php
/**
 * AnnouncementController - Bus Ticket Management System
 *
 * Post notices for passengers and staff. The admin and booking managers
 * can post, edit, switch off and delete announcements.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
	session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/AnnouncementT.php';

class AnnouncementController {
	private $model;
	private $validator;

	public function __construct() {
		$this->model     = new Announcement();
		$this->validator = new Validator();
		$this->requireStaff();
	}

	// Only the admin and booking managers can manage announcements.
	private function requireStaff() {
		if (empty($_SESSION['user_id'])) {
			$_SESSION['flash_error'] = "Please log in as an admin or booking manager to manage announcements.";
			header("Location: ../controllers/AuthController.php?action=login");
			exit;
		}
		if (!in_array($_SESSION['role'] ?? '', ['admin', 'bookingmgr'], true)) {
			$_SESSION['flash_error'] = "Access denied. Only the admin and booking managers can post announcements.";
			header("Location: ../controllers/HomeController.php");
			exit;
		}
	}

	/**
	 * Every announcement, with edit / switch off / delete.
	 */
	public function index() {
		$announcements = $this->model->getAll();
		$audiences     = $this->model->getAudiences();
		$dbConnected   = $this->model->isConnected();

		require __DIR__ . '/../views/announcement/announcement_listT.php';
	}

	/**
	 * Post a new announcement.
	 */
	public function create() {
		$errors = [];
		$data   = [
			'title'        => '',
			'body'         => '',
			'audience'     => 'All',
			'publish_from' => date('Y-m-d'),
			'publish_to'   => date('Y-m-d', strtotime('+30 days')),
			'is_active'    => 1,
		];

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$data   = $this->readForm();
			$errors = $this->check($data);

			if (!$errors) {
				$result = $this->model->create($data, (int) $_SESSION['user_id']);
				if ($result['success']) {
					$_SESSION['flash_success'] = $result['message'];
					$this->backToList();
				}
				$errors['general'] = $result['message'];
			}
		}

		$isEdit    = false;
		$audiences = $this->model->getAudiences();
		require __DIR__ . '/../views/announcement/announcement_formT.php';
	}

	/**
	 * Edit an announcement.
	 */
	public function edit() {
		$id       = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
		$existing = $this->model->find($id);
		if (!$existing) {
			$_SESSION['flash_error'] = "Announcement not found.";
			$this->backToList();
		}

		$errors = [];
		$data   = $existing;

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$data   = $this->readForm();
			$errors = $this->check($data);

			if (!$errors) {
				$result = $this->model->update($id, $data);
				if ($result['success']) {
					$_SESSION['flash_success'] = $result['message'];
					$this->backToList();
				}
				$errors['general'] = $result['message'];
			}
			$data['id'] = $id;
		}

		$isEdit    = true;
		$audiences = $this->model->getAudiences();
		require __DIR__ . '/../views/announcement/announcement_formT.php';
	}

	/**
	 * Switch an announcement on or off (POST).
	 */
	public function toggle() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$id     = (int) ($_POST['id'] ?? 0);
			$active = (int) ($_POST['active'] ?? 0);
			if ($this->model->setActive($id, $active)) {
				$_SESSION['flash_success'] = $active ? "Announcement switched on." : "Announcement switched off. Nobody sees it now.";
			} else {
				$_SESSION['flash_error'] = "Announcement not found.";
			}
		}
		$this->backToList();
	}

	/**
	 * Delete an announcement (POST).
	 */
	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if ($this->model->delete((int) ($_POST['id'] ?? 0))) {
				$_SESSION['flash_success'] = "Announcement deleted.";
			} else {
				$_SESSION['flash_error'] = "Announcement not found.";
			}
		}
		$this->backToList();
	}

	/* ---------- helpers ---------- */

	private function readForm() {
		return [
			'title'        => trim($_POST['title'] ?? ''),
			'body'         => trim($_POST['body'] ?? ''),
			'audience'     => $_POST['audience'] ?? '',
			'publish_from' => trim($_POST['publish_from'] ?? ''),
			'publish_to'   => trim($_POST['publish_to'] ?? ''),
			'is_active'    => isset($_POST['is_active']) ? 1 : 0,
		];
	}

	// ['field' => ['message'], ...] - empty when everything is fine.
	private function check($data) {
		$rules = [
			'title'        => 'required|min:3',
			'body'         => 'required|min:5',
			'audience'     => 'required',
			'publish_from' => 'required|date',
			'publish_to'   => 'required|date',
		];
		$this->validator->validate($data, $rules);
		$errors = $this->validator->getErrors();

		if (empty($errors['title']) && mb_strlen($data['title']) > 150) {
			$errors['title'][] = "Title can be at most 150 characters.";
		}
		if (empty($errors['audience']) && !array_key_exists($data['audience'], $this->model->getAudiences())) {
			$errors['audience'][] = "Please choose who should see it.";
		}
		if (empty($errors['publish_from']) && empty($errors['publish_to']) && $data['publish_to'] < $data['publish_from']) {
			$errors['publish_to'][] = "Show until must be the same day as Show from or later.";
		}
		return $errors;
	}

	private function backToList() {
		header("Location: ../controllers/AnnouncementControllerT.php?action=index");
		exit;
	}
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
	$controller = new AnnouncementController();
	$action = isset($_GET['action']) ? $_GET['action'] : 'index';
	if (in_array($action, ['index', 'create', 'edit', 'toggle', 'delete'], true)) {
		$controller->$action();
	} else {
		$controller->index();
	}
}
