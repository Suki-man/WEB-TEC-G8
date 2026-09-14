<?php
/**
 * HomeController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * The home page and the bus search.
 * Takes From, To and the date, and shows the matching trips.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/BookingT.php';

class HomeController {
    private $bookingModel;
    private $validator;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->validator = new Validator();
    }

    /**
     * Show home page with search form and available trips
     */
    public function index() {
        $fromCity = isset($_GET['from_city']) ? trim($_GET['from_city']) : '';
        $toCity = isset($_GET['to_city']) ? trim($_GET['to_city']) : '';
        $date = isset($_GET['date']) ? trim($_GET['date']) : '';

        $cities = $this->bookingModel->getCities();
        $trips = [];
        $searched = false;

        if (!empty($fromCity) || !empty($toCity) || !empty($date)) {
            $searched = true;
            $trips = $this->bookingModel->searchTrips($fromCity, $toCity, $date);
        } else {
            // Default: show upcoming available trips
            $trips = $this->bookingModel->searchTrips();
        }

        // Pass variables to view
        $viewData = [
            'cities' => $cities,
            'trips' => $trips,
            'fromCity' => $fromCity,
            'toCity' => $toCity,
            'date' => $date,
            'searched' => $searched
        ];

        extract($viewData);
        require __DIR__ . '/../views/home/indexT.php';
    }

    /**
     * Search action handler
     */
    public function search() {
        $this->index();
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new HomeController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
}

