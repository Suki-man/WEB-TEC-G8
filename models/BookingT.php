<?php
require_once __DIR__ . '/PreviewT.php';

class Booking {
	private $conn;

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	/* ---------- trip search ---------- */

	// Every city that appears on an active route, as a list of names.
	public function getCities() {
		if (!$this->conn) {
			$cities = [];
			foreach (Preview::routes() as $route) {
				$cities[] = $route['origin'];
				$cities[] = $route['destination'];
			}
			$cities = array_values(array_unique($cities));
			sort($cities);
			return $cities;
		}
		$rows = $this->fetchAll("SELECT origin AS city FROM routes WHERE status = 'Active'
			UNION SELECT destination FROM routes WHERE status = 'Active'
			ORDER BY city");
		return array_column($rows, 'city');
	}

	// Active trips, optionally filtered. With no date, only today onwards.
	// Each row also has free_seats.
	public function searchTrips($fromCity = '', $toCity = '', $date = '') {
		if (!$this->conn) {
			$trips = [];
			foreach (Preview::schedules() as $schedule) {
				$bus  = Preview::find(Preview::buses(), $schedule['bus_id']);
				$trip = Preview::trip($schedule);
				if ($schedule['status'] !== 'Active' || $bus['status'] !== 'Active'
					|| ($fromCity !== '' && $trip['from_city'] !== $fromCity)
					|| ($toCity !== '' && $trip['to_city'] !== $toCity)
					|| ($date !== '' && $trip['departure_date'] !== $date)) {
					continue;
				}
				$trips[] = $trip;
			}
			return $trips;
		}
		$sql = "SELECT s.id, s.fare, s.travel_date AS departure_date, s.departure_time, s.arrival_time,
				r.origin AS from_city, r.destination AS to_city,
				b.bus_number AS bus_name, b.type AS bus_type, b.seat_capacity,
				(SELECT COUNT(*) FROM seat_allocations sa WHERE sa.schedule_id = s.id) AS taken
			FROM schedules s
			JOIN routes r ON r.id = s.route_id
			JOIN buses b ON b.id = s.bus_id
			WHERE s.status = 'Active' AND r.status = 'Active' AND b.status = 'Active'";
		$types  = '';
		$params = [];

		if ($fromCity !== '') {
			$sql .= " AND r.origin = ?";
			$types .= 's';
			$params[] = $fromCity;
		}
		if ($toCity !== '') {
			$sql .= " AND r.destination = ?";
			$types .= 's';
			$params[] = $toCity;
		}
		if ($date !== '') {
			$sql .= " AND s.travel_date = ?";
			$types .= 's';
			$params[] = $date;
		} else {
			$sql .= " AND s.travel_date >= CURDATE()";
		}
		$sql .= " ORDER BY s.travel_date, s.departure_time";

		$trips = $this->fetchAll($sql, $types, $params);
		foreach ($trips as &$trip) {
			$trip['free_seats'] = max(0, (int) $trip['seat_capacity'] - (int) $trip['taken']);
		}
		unset($trip);
		return $trips;
	}

	// One trip with its route and bus, or null.
	public function getScheduleById($id) {
		if (!$this->conn) {
			$schedule = Preview::find(Preview::schedules(), $id);
			return $schedule ? Preview::trip($schedule) : null;
		}
		return $this->fetchOne("SELECT s.id, s.fare, s.status, s.travel_date AS departure_date, s.departure_time, s.arrival_time,
				r.origin AS from_city, r.destination AS to_city,
				b.bus_number AS bus_name, b.type AS bus_type, b.seat_capacity
			FROM schedules s
			JOIN routes r ON r.id = s.route_id
			JOIN buses b ON b.id = s.bus_id
			WHERE s.id = ?", "i", [$id]);
	}

	// Seat numbers already taken on a trip, e.g. ['A1', 'B4'].
	public function getOccupiedSeats($scheduleId) {
		if (!$this->conn) {
			return array_column(Preview::where(Preview::bookings(), 'schedule_id', $scheduleId), 'seat_no');
		}
		$rows = $this->fetchAll("SELECT seat_no FROM seat_allocations WHERE schedule_id = ?
			UNION SELECT seat_no FROM bookings WHERE schedule_id = ? AND status = 'Confirmed'", "ii", [$scheduleId, $scheduleId]);
		return array_map('strtoupper', array_column($rows, 'seat_no'));
	}

	/* ---------- a passenger's bookings ---------- */

	public function getBookingsByUser($userId) {
		if (!$this->conn) {
			return array_map(['Preview', 'booking'], Preview::where(Preview::bookings(), 'user_id', $userId));
		}
		return $this->fetchAll($this->bookingSelect() . " WHERE bk.user_id = ? ORDER BY bk.booked_at DESC, bk.id DESC", "i", [$userId]);
	}

	// The next confirmed trip from today onwards, or null.
	public function getUpcomingBookingByUser($userId) {
		if (!$this->conn) {
			foreach ($this->getBookingsByUser($userId) as $booking) {
				if ($booking['status'] === 'confirmed') {
					return $booking;
				}
			}
			return null;
		}
		return $this->fetchOne($this->bookingSelect() . " WHERE bk.user_id = ? AND bk.status = 'Confirmed' AND s.travel_date >= CURDATE()
			ORDER BY s.travel_date, s.departure_time LIMIT 1", "i", [$userId]);
	}

	// ['total_bookings' => n, 'active_bookings' => n]
	public function getUserStats($userId) {
		if (!$this->conn) {
			$bookings = Preview::where(Preview::bookings(), 'user_id', $userId);
			return [
				'total_bookings'  => count($bookings),
				'active_bookings' => count(Preview::where($bookings, 'status', 'Confirmed')),
			];
		}
		return $this->fetchOne("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(status = 'Confirmed'), 0) AS active_bookings
			FROM bookings WHERE user_id = ?", "i", [$userId]);
	}

	// One booking, only if it belongs to this user, or null.
	public function getBookingDetails($bookingId, $userId) {
		if (!$this->conn) {
			$booking = Preview::find(Preview::bookings(), $bookingId);
			return ($booking && (int) $booking['user_id'] === (int) $userId) ? Preview::booking($booking) : null;
		}
		return $this->fetchOne($this->bookingSelect() . " WHERE bk.id = ? AND bk.user_id = ?", "ii", [$bookingId, $userId]);
	}

	// Live notices for passengers.
	public function getNotices() {
		if (!$this->conn) {
			return Preview::notices();
		}
		return $this->fetchAll("SELECT title, body AS content, created_at FROM announcements
			WHERE is_active = 1 AND audience IN ('All', 'User') AND CURDATE() BETWEEN publish_from AND publish_to
			ORDER BY created_at DESC LIMIT 5");
	}

	/* ---------- book, change seat, cancel ---------- */

	// Returns ['success' => bool, 'message' => string].
	public function createBooking($userId, $scheduleId, $seatNumber, $travellerId, $fare) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		$seatNumber = strtoupper(trim($seatNumber));
		$schedule   = $this->getScheduleById($scheduleId);

		if (!$schedule || $schedule['status'] !== 'Active') {
			return $this->result(false, "This trip is not open for booking.");
		}
		if (!$this->seatExists($seatNumber, $schedule['seat_capacity'])) {
			return $this->result(false, "Seat $seatNumber does not exist on this bus.");
		}
		if ($travellerId !== null && !$this->ownsTraveller($travellerId, $userId)) {
			return $this->result(false, "Please choose a co-traveller from your own list.");
		}

		try {
			$this->conn->begin_transaction();

			$stmt = $this->conn->prepare("INSERT INTO bookings (user_id, schedule_id, traveller_id, seat_no, fare, status) VALUES (?, ?, ?, ?, ?, 'Confirmed')");
			$stmt->bind_param("iiisd", $userId, $scheduleId, $travellerId, $seatNumber, $fare);
			$stmt->execute();
			$ticket = 'TKT-' . $this->conn->insert_id;

			// the UNIQUE key on (schedule_id, seat_no) stops a seat being sold twice
			$stmt = $this->conn->prepare("INSERT INTO seat_allocations (schedule_id, seat_no, booking_ref, alloc_type) VALUES (?, ?, ?, 'Online')");
			$stmt->bind_param("iss", $scheduleId, $seatNumber, $ticket);
			$stmt->execute();

			$this->conn->commit();
			return $this->result(true, "Seat $seatNumber booked. Your ticket code is $ticket.");
		} catch (mysqli_sql_exception $e) {
			$this->conn->rollback();
			if ($e->getCode() === 1062) {
				return $this->result(false, "Seat $seatNumber was just taken by someone else. Please choose another seat.");
			}
			return $this->result(false, "Booking failed: " . $e->getMessage());
		}
	}

	// Returns ['success' => bool, 'message' => string].
	public function changeSeat($bookingId, $newSeatNumber, $userId) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		$newSeat = strtoupper(trim($newSeatNumber));
		$booking = $this->getBookingDetails($bookingId, $userId);

		if (!$booking || $booking['status'] !== 'confirmed') {
			return $this->result(false, "Only active confirmed tickets can be changed.");
		}
		$oldSeat  = $booking['seat_number'];
		$schedule = $this->getScheduleById($booking['schedule_id']);

		if ($newSeat === $oldSeat) {
			return $this->result(false, "Seat $newSeat is already your seat. Please choose a different one.");
		}
		if (!$this->seatExists($newSeat, $schedule['seat_capacity'])) {
			return $this->result(false, "Seat $newSeat does not exist on this bus.");
		}
		if (in_array($newSeat, $this->getOccupiedSeats($booking['schedule_id']), true)) {
			return $this->result(false, "Seat $newSeat is already taken. Please choose another seat.");
		}

		try {
			$this->conn->begin_transaction();

			$stmt = $this->conn->prepare("UPDATE seat_allocations SET seat_no = ? WHERE schedule_id = ? AND seat_no = ?");
			$stmt->bind_param("sis", $newSeat, $booking['schedule_id'], $oldSeat);
			$stmt->execute();

			if ($stmt->affected_rows === 0) {
				// no allocation row for the old seat yet, so create one for the new seat
				$ticket = 'TKT-' . $booking['id'];
				$stmt = $this->conn->prepare("INSERT INTO seat_allocations (schedule_id, seat_no, booking_ref, alloc_type) VALUES (?, ?, ?, 'Online')");
				$stmt->bind_param("iss", $booking['schedule_id'], $newSeat, $ticket);
				$stmt->execute();
			}

			$stmt = $this->conn->prepare("UPDATE bookings SET seat_no = ? WHERE id = ? AND user_id = ?");
			$stmt->bind_param("sii", $newSeat, $bookingId, $userId);
			$stmt->execute();

			$this->conn->commit();
			return $this->result(true, "Seat changed from $oldSeat to $newSeat.");
		} catch (mysqli_sql_exception $e) {
			$this->conn->rollback();
			if ($e->getCode() === 1062) {
				return $this->result(false, "Seat $newSeat was just taken by someone else. Please choose another seat.");
			}
			return $this->result(false, "Seat change failed: " . $e->getMessage());
		}
	}

	// Cancels the ticket, frees the seat and opens a refund request.
	// Returns ['success' => bool, 'message' => string].
	public function cancelBooking($bookingId, $userId, $reason) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		$booking = $this->getBookingDetails($bookingId, $userId);

		if (!$booking) {
			return $this->result(false, "Booking not found.");
		}
		if ($booking['status'] !== 'confirmed') {
			return $this->result(false, "This ticket is already cancelled.");
		}

		$ticket    = $booking['booking_code'];
		$fare      = (float) $booking['total_fare'];
		$deduction = round($fare * 0.10, 2);
		$refund    = round($fare - $deduction, 2);
		$reason    = $reason !== '' ? $reason : 'Customer requested cancellation';

		try {
			$this->conn->begin_transaction();

			$stmt = $this->conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
			$stmt->bind_param("ii", $bookingId, $userId);
			$stmt->execute();

			$stmt = $this->conn->prepare("DELETE FROM seat_allocations WHERE schedule_id = ? AND seat_no = ?");
			$stmt->bind_param("is", $booking['schedule_id'], $booking['seat_number']);
			$stmt->execute();

			$stmt = $this->conn->prepare("INSERT INTO refunds (booking_ref, cancel_reason, ticket_amount, deduction, refund_amount, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
			$stmt->bind_param("ssddd", $ticket, $reason, $fare, $deduction, $refund);
			$stmt->execute();

			$this->conn->commit();
			return $this->result(true, "Ticket $ticket cancelled. Seat {$booking['seat_number']} is free again and a refund request for BDT " . number_format($refund, 2) . " has been opened.");
		} catch (mysqli_sql_exception $e) {
			$this->conn->rollback();
			return $this->result(false, "Cancellation failed: " . $e->getMessage());
		}
	}

	/* ---------- helpers ---------- */

	// The columns every booking screen needs, named the way the views expect.
	private function bookingSelect() {
		return "SELECT bk.id, CONCAT('TKT-', bk.id) AS booking_code, bk.user_id, bk.schedule_id,
				bk.seat_no AS seat_number, bk.fare AS total_fare, LOWER(bk.status) AS status, bk.booked_at,
				r.origin AS from_city, r.destination AS to_city,
				s.travel_date AS departure_date, s.departure_time,
				b.bus_number AS bus_name, b.type AS bus_type,
				t.name AS traveller_name, t.phone AS traveller_phone,
				rf.status AS refund_status, rf.refund_amount, rf.deduction AS refund_deduction,
				rf.method AS refund_method, rf.processed_at AS refund_processed_at
			FROM bookings bk
			JOIN schedules s ON s.id = bk.schedule_id
			JOIN routes r ON r.id = s.route_id
			JOIN buses b ON b.id = s.bus_id
			LEFT JOIN travellers t ON t.id = bk.traveller_id
			-- the latest refund request for this ticket (TKT-<id>), so the passenger sees the manager's decision
			LEFT JOIN refunds rf ON rf.id = (SELECT MAX(r2.id) FROM refunds r2 WHERE r2.booking_ref = CONCAT('TKT-', bk.id))";
	}

	// Seats are A1-A4, B1-B4 ... up to the bus's seat capacity.
	private function seatExists($seat, $capacity) {
		if (!preg_match('/^([A-J])([1-4])$/', $seat, $m)) {
			return false;
		}
		$position = (ord($m[1]) - ord('A')) * 4 + (int) $m[2];
		return $position <= (int) $capacity;
	}

	private function ownsTraveller($travellerId, $userId) {
		return $this->fetchOne("SELECT id FROM travellers WHERE id = ? AND user_id = ?", "ii", [$travellerId, $userId]) !== null;
	}

	private function result($success, $message) {
		return ['success' => $success, 'message' => $message];
	}

	private function fetchAll($sql, $types = '', $params = []) {
		$stmt = $this->conn->prepare($sql);
		if ($params) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}

	private function fetchOne($sql, $types = '', $params = []) {
		$rows = $this->fetchAll($sql, $types, $params);
		return $rows ? $rows[0] : null;
	}
}
