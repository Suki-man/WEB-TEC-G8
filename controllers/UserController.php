<?php
/**
 * UserController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * The Admin screen:
 * Create staff accounts, change somebody's role, deactivate or delete an account.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/UserT.php';

class UserController {
    private $userModel;
    private $validator;

    public function __construct() {
        $this->userModel = new User();
        $this->validator = new Validator();
        $this->requireAdmin();
    }

    /**
     * Enforce admin access
     */
    private function requireAdmin() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Please log in as an administrator.";
            header("Location: ../controllers/AuthController.php?action=login");
            exit;
        }

        // If logged in user is not admin, deny access
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
            $_SESSION['flash_error'] = "Access denied. Administrator privileges required.";
            header("Location: ../controllers/BookingController.php?action=dashboard");
            exit;
        }
    }

    /**
     * List all accounts with filters by role, status, and search
     */
    public function index() {
        $roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $users = $this->userModel->filterUsers($roleFilter, $statusFilter, $search);

        $viewData = [
            'users' => $users,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'search' => $search
        ];

        extract($viewData);
        require __DIR__ . '/../views/admin/user_list.php';
    }

    /**
     * Create new staff or user account
     */
    public function create() {
        $errors = [];
        $data = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'role' => 'passenger',
            'status' => 'active'
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
                'password' => isset($_POST['password']) ? trim($_POST['password']) : '',
                'role' => isset($_POST['role']) ? trim($_POST['role']) : 'passenger',
                'status' => isset($_POST['status']) ? trim($_POST['status']) : 'active'
            ];

            $rules = [
                'name' => 'required|min:3',
                'email' => 'required|email|unique_email',
                'phone' => 'required|phone',
                'password' => 'required|min:6',
                'role' => 'required',
                'status' => 'required'
            ];

            if ($this->validator->validate($data, $rules)) {
                $created = $this->userModel->createByAdmin($data);
                if ($created) {
                    $_SESSION['flash_success'] = "Account for " . htmlspecialchars($data['name']) . " created successfully.";
                    header("Location: ../controllers/UserController.php?action=index");
                    exit;
                } else {
                    $errors['general'] = "Failed to create user. Please check database.";
                }
            } else {
                $errors = $this->validator->getErrors();
            }
        }

        $isEdit = false;
        require __DIR__ . '/../views/admin/user_form.php';
    }

    /**
     * Edit existing user: change role, update details, or update password
     */
    public function edit() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $user = $this->userModel->find($id);

        if (!$user) {
            $_SESSION['flash_error'] = "User account not found.";
            header("Location: ../controllers/UserController.php?action=index");
            exit;
        }

        $errors = [];
        $data = $user;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
                'role' => isset($_POST['role']) ? trim($_POST['role']) : 'passenger',
                'status' => isset($_POST['status']) ? trim($_POST['status']) : 'active'
            ];

            $rules = [
                'name' => 'required|min:3',
                'email' => "required|email|unique_email:{$id}",
                'phone' => 'required|phone',
                'role' => 'required',
                'status' => 'required'
            ];

            if (!empty($_POST['password'])) {
                $rules['password'] = 'min:6';
                $data['password'] = trim($_POST['password']);
            }

            if ($this->validator->validate($_POST, $rules)) {
                $updated = $this->userModel->updateByAdmin($id, $data);
                if ($updated !== false) {
                    $_SESSION['flash_success'] = "Account #{$id} updated successfully.";
                    header("Location: ../controllers/UserController.php?action=index");
                    exit;
                } else {
                    $errors['general'] = "Failed to update account.";
                }
            } else {
                $errors = $this->validator->getErrors();
            }
        }

        $isEdit = true;
        require __DIR__ . '/../views/admin/user_form.php';
    }

    /**
     * Toggle active/inactive status
     */
    public function toggleStatus() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $status = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '';

        if ($id > 0 && in_array($status, ['active', 'inactive'])) {
            $this->userModel->updateStatus($id, $status);
            $_SESSION['flash_success'] = "User #{$id} status updated to {$status}.";
        }

        header("Location: ../controllers/UserController.php?action=index");
        exit;
    }

    /**
     * Delete an account
     */
    public function delete() {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        // Prevent admin from deleting themselves
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['flash_error'] = "You cannot delete your own logged-in account.";
        } elseif ($id > 0) {
            $this->userModel->deleteAccount($id);
            $_SESSION['flash_success'] = "Account #{$id} has been deleted.";
        }

        header("Location: ../controllers/UserController.php?action=index");
        exit;
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new UserController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
}

