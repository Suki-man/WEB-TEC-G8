<?php
/**
 * TravellerController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * Add, edit and remove saved co-travellers.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/TravellerT.php';

class TravellerController {
    private $travellerModel;
    private $validator;

    public function __construct() {
        $this->travellerModel = new Traveller();
        $this->validator = new Validator();
        $this->requireAuth();
    }

    private function requireAuth() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Please log in to manage co-travellers.";
            header("Location: ../controllers/AuthController.php?action=login");
            exit;
        }
    }

    /**
     * List all co-travellers for current passenger
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $travellers = $this->travellerModel->getByUser($userId);

        require __DIR__ . '/../views/passenger/traveller_list.php';
    }

    /**
     * Add new co-traveller
     */
    public function create() {
        $userId = $_SESSION['user_id'];
        $errors = [];
        $data = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'gender' => '',
            'age' => '',
            'relation' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'gender' => isset($_POST['gender']) ? trim($_POST['gender']) : '',
                'age' => isset($_POST['age']) ? trim($_POST['age']) : '',
                'relation' => isset($_POST['relation']) ? trim($_POST['relation']) : ''
            ];

            $rules = [
                'name' => 'required|min:2',
                'phone' => 'required|phone'
            ];

            if (!empty($data['email'])) {
                $rules['email'] = 'email';
            }
            if (!empty($data['age'])) {
                $rules['age'] = 'numeric';
            }

            if ($this->validator->validate($data, $rules)) {
                $result = $this->travellerModel->createTraveller($userId, $data);

                if ($result['success']) {
                    $_SESSION['flash_success'] = "Co-traveller added successfully.";
                    header("Location: ../controllers/TravellerController.php?action=index");
                    exit;
                } else {
                    $errors['general'] = $result['message'];
                }
            } else {
                $errors = $this->validator->getErrors();
            }
        }

        $isEdit = false;
        require __DIR__ . '/../views/passenger/traveller_form.php';
    }

    /**
     * Edit an existing co-traveller
     */
    public function edit() {
        $userId = $_SESSION['user_id'];
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        $traveller = $this->travellerModel->getByIdAndUser($id, $userId);
        if (!$traveller) {
            $_SESSION['flash_error'] = "Co-traveller not found.";
            header("Location: ../controllers/TravellerController.php?action=index");
            exit;
        }

        $errors = [];
        $data = $traveller;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'gender' => isset($_POST['gender']) ? trim($_POST['gender']) : '',
                'age' => isset($_POST['age']) ? trim($_POST['age']) : '',
                'relation' => isset($_POST['relation']) ? trim($_POST['relation']) : ''
            ];

            $rules = [
                'name' => 'required|min:2',
                'phone' => 'required|phone'
            ];

            if (!empty($data['email'])) {
                $rules['email'] = 'email';
            }
            if (!empty($data['age'])) {
                $rules['age'] = 'numeric';
            }

            if ($this->validator->validate($data, $rules)) {
                $result = $this->travellerModel->updateTraveller($id, $userId, $data);

                if ($result['success']) {
                    $_SESSION['flash_success'] = "Co-traveller updated successfully.";
                    header("Location: ../controllers/TravellerController.php?action=index");
                    exit;
                } else {
                    $errors['general'] = $result['message'];
                }
            } else {
                $errors = $this->validator->getErrors();
            }
        }

        $isEdit = true;
        require __DIR__ . '/../views/passenger/traveller_form.php';
    }

    /**
     * Remove co-traveller
     */
    public function delete() {
        $userId = $_SESSION['user_id'];
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        if ($id > 0) {
            $this->travellerModel->deleteTraveller($id, $userId);
            $_SESSION['flash_success'] = "Co-traveller removed successfully.";
        }

        header("Location: ../controllers/TravellerController.php?action=index");
        exit;
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new TravellerController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
}

