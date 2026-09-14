<?php
/**
 * AuthController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * Log in, register a new customer, and log out.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/UserT.php';

class AuthController {
    private $userModel;
    private $validator;

    public function __construct() {
        $this->userModel = new User();
        $this->validator = new Validator();
    }

    /**
     * Show login form or process login request
     */
    public function login() {
        // If already logged in, redirect to respective dashboard
        if (!empty($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['role']);
            return;
        }

        $error = '';
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            $rules = [
                'email' => 'required',
                'password' => 'required'
            ];

            if ($this->validator->validate($_POST, $rules)) {
                $user = $this->userModel->findByLogin($email);

                if ($user) {
                    // Check password (supports hashed and plaintext fallback)
                    $passwordValid = password_verify($password, $user['password']) || ($password === $user['password']);

                    if ($passwordValid) {
                        if ($user['status'] === 'inactive') {
                            $error = "Your account has been deactivated. Please contact the administrator.";
                        } else {
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_phone'] = $user['phone'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['username'] = $user['username'] ? $user['username'] : $user['name'];

                            $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";
                            unset($_SESSION['guest']);

                            // a guest who was booking a seat goes straight back to that trip
                            if ($user['role'] === 'passenger' && !empty($_SESSION['return_to'])) {
                                $returnTo = $_SESSION['return_to'];
                                unset($_SESSION['return_to']);
                                header("Location: " . $returnTo);
                                exit;
                            }
                            unset($_SESSION['return_to']);
                            $this->redirectByRole($user['role']);
                            return;
                        }
                    } else {
                        $error = "Invalid email/username or password.";
                    }
                } else {
                    $error = "No account found with this email or username.";
                }
            } else {
                $error = $this->validator->getFirstError();
            }
        }

        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Show customer registration form or process registration
     */
    public function register() {
        if (!empty($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['role']);
            return;
        }

        $errors = [];
        $name = '';
        $email = '';
        $phone = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

            $rules = [
                'name' => 'required|min:3',
                'email' => 'required|email|unique_email',
                'phone' => 'required|phone',
                'password' => 'required|min:6',
                'confirm_password' => 'required|matches:password'
            ];

            if ($this->validator->validate($_POST, $rules)) {
                $created = $this->userModel->registerCustomer($name, $email, $phone, $_POST['password']);
                if ($created) {
                    $_SESSION['flash_success'] = "Registration successful! You can now log in to book your tickets.";
                    header("Location: ../controllers/AuthController.php?action=login");
                    exit;
                } else {
                    $errors['general'] = "Registration failed due to a database error. Please try again.";
                }
            } else {
                $errors = $this->validator->getErrors();
            }
        }

        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Continue as a guest: look at trips and seat maps without an account.
     * Booking a seat still asks the guest to log in or register first.
     */
    public function guest() {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_phone'], $_SESSION['role'], $_SESSION['username'], $_SESSION['return_to']);
        $_SESSION['guest'] = true;
        $_SESSION['flash_success'] = "You are browsing as a guest. Pick a trip and a seat, then log in or register to book it.";
        header("Location: ../controllers/HomeController.php");
        exit;
    }

    /**
     * Log out current user and destroy session
     */
    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        session_start();
        $_SESSION['flash_success'] = "You have been logged out successfully.";
        header("Location: ../controllers/AuthController.php?action=login");
        exit;
    }

    /**
     * Role-based redirect helper
     */
    private function redirectByRole($role) {
        switch ($role) {
            case 'admin':
                header("Location: ../controllers/UserController.php?action=index");
                break;
            case 'bookingmgr':
                header("Location: ../controllers/ManagerControllerT.php?action=dashboard");
                break;
            case 'busroute':
                header("Location: ../views/busroute/dashboard.php");
                break;
            case 'passenger':
            default:
                header("Location: ../controllers/BookingController.php?action=dashboard");
                break;
        }
        exit;
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new AuthController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'login';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->login();
    }
}

