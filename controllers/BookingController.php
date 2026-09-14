<?php
/**
 * BookingController - Bus Ticket Management System
 * Part A: Nripendra Sutradhar Pranto
 * 
 * Book a seat, change the seat later, and cancel a ticket.
 * Cancelling also frees the seat and opens a refund.
 * Acceptance criteria:
 * Book a seat -> change to a different seat -> cancel it -> a refund request appears by itself.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../core/ValidatorT.php';
require_once __DIR__ . '/../models/BookingT.php';
require_once __DIR__ . '/../models/TravellerT.php';

class BookingController {
    private $bookingModel;
    private $travellerModel;
    private $validator;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->travellerModel = new Traveller();
        $this->validator = new Validator();

        // guests may open the seat map; book() asks them to log in before booking
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';
        if ($action !== 'book') {
            $this->requireAuth();
        }
    }

    /**
     * Enforce passenger login
     */
    private function requireAuth() {
        if (empty($_SESSION['user_id'])) {
            // come back to this page after logging in
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['return_to'] = '../controllers/BookingController.php?' . http_build_query($_GET);
            }
            $_SESSION['flash_error'] = "Please log in to manage bookings.";
            header("Location: ../controllers/AuthController.php?action=login");
            exit;
        }
    }

    /**
     * Passenger Dashboard:
     * Displays next trip, totals, notices, and shortcuts
     */
    public function dashboard() {
        $userId = $_SESSION['user_id'];
        $nextTrip = $this->bookingModel->getUpcomingBookingByUser($userId);
        $stats = $this->bookingModel->getUserStats($userId);
        $notices = $this->bookingModel->getNotices();

        // Count saved travellers and watchlists
        require_once __DIR__ . '/../models/WatchlistT.php';
        $wlModel = new Watchlist();
        $watchlist = $wlModel->getWatchlistWithSeatCount($userId);
        $travellers = $this->travellerModel->getByUser($userId);

        $viewData = [
            'nextTrip' => $nextTrip,
            'stats' => $stats,
            'notices' => $notices,
            'watchlistCount' => count($watchlist),
            'travellerCount' => count($travellers)
        ];

        extract($viewData);
        require __DIR__ . '/../views/passenger/dashboard.php';
    }

    /**
     * List all customer tickets
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $bookings = $this->bookingModel->getBookingsByUser($userId);

        require __DIR__ . '/../views/passenger/booking_list.php';
    }

    /**
     * Display seat map and booking form, or process seat booking
     */
    public function book() {
        $isGuest = empty($_SESSION['user_id']);
        $userId = $isGuest ? 0 : $_SESSION['user_id'];
        $scheduleId = isset($_REQUEST['schedule_id']) ? (int)$_REQUEST['schedule_id'] : 0;

        if ($scheduleId <= 0) {
            $_SESSION['flash_error'] = "Invalid trip selected.";
            header("Location: ../controllers/HomeController.php");
            exit;
        }

        $schedule = $this->bookingModel->getScheduleById($scheduleId);
        if (!$schedule) {
            $_SESSION['flash_error'] = "Trip schedule not found.";
            header("Location: ../controllers/HomeController.php");
            exit;
        }

        $occupiedSeats = $this->bookingModel->getOccupiedSeats($scheduleId);
        $travellers = $isGuest ? [] : $this->travellerModel->getByUser($userId);
        $error = '';

        // a seat picked before logging in (?seat=A1) is shown as selected
        $preselectSeat = isset($_GET['seat']) ? strtoupper(trim($_GET['seat'])) : '';
        if (!preg_match('/^[A-J][1-4]$/', $preselectSeat) || in_array($preselectSeat, $occupiedSeats)) {
            $preselectSeat = '';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $seatNumber = isset($_POST['seat_number']) ? strtoupper(trim($_POST['seat_number'])) : '';
            $travellerId = !empty($_POST['traveller_id']) ? (int)$_POST['traveller_id'] : null;

            if ($isGuest) {
                // guests log in (or register) first, then come back to this trip and seat
                $seatParam = preg_match('/^[A-J][1-4]$/', $seatNumber) ? '&seat=' . $seatNumber : '';
                $_SESSION['return_to'] = '../controllers/BookingController.php?action=book&schedule_id=' . $scheduleId . $seatParam;
                $_SESSION['flash_error'] = "Please log in or register to book this seat. You will come back to this trip right after.";
                header("Location: ../controllers/AuthController.php?action=login");
                exit;
            }

            if (empty($seatNumber)) {
                $error = "Please click a seat on the seat map to select it.";
            } elseif (in_array($seatNumber, $occupiedSeats)) {
                $error = "Seat {$seatNumber} is already taken. Please choose another seat.";
            } else {
                $fare = isset($schedule['fare']) ? (float)$schedule['fare'] : 0;
                $result = $this->bookingModel->createBooking($userId, $scheduleId, $seatNumber, $travellerId, $fare);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header("Location: ../controllers/BookingController.php?action=index");
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }

        $viewData = [
            'schedule' => $schedule,
            'occupiedSeats' => $occupiedSeats,
            'travellers' => $travellers,
            'error' => $error,
            'isGuest' => $isGuest,
            'preselectSeat' => $preselectSeat
        ];

        extract($viewData);
        require __DIR__ . '/../views/passenger/booking_form.php';
    }

    /**
     * Change a booked seat to another free seat
     */
    public function changeSeat() {
        $userId = $_SESSION['user_id'];
        $bookingId = isset($_REQUEST['booking_id']) ? (int)$_REQUEST['booking_id'] : 0;

        $booking = $this->bookingModel->getBookingDetails($bookingId, $userId);
        if (!$booking) {
            $_SESSION['flash_error'] = "Booking not found.";
            header("Location: ../controllers/BookingController.php?action=index");
            exit;
        }

        if ($booking['status'] !== 'confirmed') {
            $_SESSION['flash_error'] = "Only active confirmed tickets can be edited.";
            header("Location: ../controllers/BookingController.php?action=index");
            exit;
        }

        $scheduleId = (int)$booking['schedule_id'];
        $schedule = $this->bookingModel->getScheduleById($scheduleId);
        $occupiedSeats = $this->bookingModel->getOccupiedSeats($scheduleId);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newSeatNumber = isset($_POST['new_seat_number']) ? strtoupper(trim($_POST['new_seat_number'])) : '';

            if (empty($newSeatNumber)) {
                $error = "Please select a free seat on the map.";
            } else {
                $result = $this->bookingModel->changeSeat($bookingId, $newSeatNumber, $userId);

                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                    header("Location: ../controllers/BookingController.php?action=index");
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }

        $isSeatChangeMode = true;
        $viewData = [
            'booking' => $booking,
            'schedule' => $schedule,
            'occupiedSeats' => $occupiedSeats,
            'currentSeat' => $booking['seat_number'],
            'isSeatChangeMode' => $isSeatChangeMode,
            'error' => $error
        ];

        extract($viewData);
        require __DIR__ . '/../views/passenger/booking_form.php';
    }

    /**
     * Cancel a ticket:
     * Frees the seat in seat_allocations and opens a refund request in refunds
     */
    public function cancel() {
        $userId = $_SESSION['user_id'];
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : (isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0);
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : 'Customer requested cancellation';

        if ($bookingId <= 0) {
            $_SESSION['flash_error'] = "Invalid booking ID.";
            header("Location: ../controllers/BookingController.php?action=index");
            exit;
        }

        $result = $this->bookingModel->cancelBooking($bookingId, $userId, $reason);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: ../controllers/BookingController.php?action=index");
        exit;
    }
}

// Support direct invocation if routed directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $controller = new BookingController();
    $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
}

