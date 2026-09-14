<?php
/**
 * WatchlistController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * Add, edit and remove saved routes.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/WatchlistT.php';

class WatchlistController {
    private $watchlistModel;
    private $validator;

    public function __construct() {
        $this->watchlistModel = new Watchlist();
        $this->validator = new Validator();
        $this->requireAuth();
    }

    private function requireAuth() {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Please log in to manage your watchlist.";
            header("Location: ../controllers/AuthController.php?action=login");
            exit;
        }
    }

    /**
     * List saved routes with free seats count right now
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $watchlist = $this->watchlistModel->getWatchlistWithSeatCount($userId);

        require __DIR__ . '/../views/passenger/watchlist_list.php';
    }

    /**
     * Add new route to watchlist
     */
    public function create() {
        $userId = $_SESSION['user_id'];
        $routes = $this->watchlistModel->getAllRoutes();
        $error = '';
        $routeId = '';
        $preferredDate = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
            $preferredDate = isset($_POST['preferred_date']) ? trim($_POST['preferred_date']) : '';

            $rules = [
                'route_id' => 'required|numeric'
            ];
            if (!empty($preferredDate)) {
                $rules['preferred_date'] = 'date|future_date';
            }

            if ($this->validator->validate($_POST, $rules)) {
                $result = $this->watchlistModel->addWatchlist($userId, $routeId, $preferredDate);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header("Location: ../controllers/WatchlistController.php?action=index");
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = $this->validator->getFirstError();
            }
        }

        $isEdit = false;
        require __DIR__ . '/../views/passenger/watchlist_form.php';
    }

    /**
     * Edit existing watchlist route
     */
    public function edit() {
        $userId = $_SESSION['user_id'];
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        $watchItem = $this->watchlistModel->getByIdAndUser($id, $userId);
        if (!$watchItem) {
            $_SESSION['flash_error'] = "Watchlist item not found.";
            header("Location: ../controllers/WatchlistController.php?action=index");
            exit;
        }

        $routes = $this->watchlistModel->getAllRoutes();
        $error = '';
        $routeId = $watchItem['route_id'];
        $preferredDate = $watchItem['preferred_date'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
            $preferredDate = isset($_POST['preferred_date']) ? trim($_POST['preferred_date']) : '';

            $rules = [
                'route_id' => 'required|numeric'
            ];
            if (!empty($preferredDate)) {
                $rules['preferred_date'] = 'date|future_date';
            }

            if ($this->validator->validate($_POST, $rules)) {
                $result = $this->watchlistModel->updateWatchlist($id, $userId, $routeId, $preferredDate);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header("Location: ../controllers/WatchlistController.php?action=index");
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = $this->validator->getFirstError();
            }
        }

        $isEdit = true;
        require __DIR__ . '/../views/passenger/watchlist_form.php';
    }

    /**
     * Remove route from watchlist
     */
    public function delete() {
        $userId = $_SESSION['user_id'];
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        if ($id > 0) {
            $this->watchlistModel->deleteWatchlist($id, $userId);
            $_SESSION['flash_success'] = "Route removed from watchlist.";
        }

        header("Location: ../controllers/WatchlistController.php?action=index");
        exit;
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new WatchlistController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
}

