<?php
require_once __DIR__ . '/PreviewT.php';

// Booking manager (counter staff): counter sales, seat allocation and refunds.
class Manager {
	private $conn;
	private $paymentModes  = ['Cash', 'bKash', 'Nagad', 'Card'];
	private $refundMethods = ['Cash', 'bKash', 'Nagad', 'Card', 'Bank Transfer'];

	public function __construct() {
		// null while the database is not set up yet
		$this->conn = Preview::connect();
	}

	public function isConnected() {
		return $this->conn !== null;
	}

	public function getPaymentModes() {
		return $this->paymentModes;
	}

	public function getRefundMethods() {
		return $this->refundMethods;
	}

	/* ---------- dashboard ---------- */

	// Numbers for the four cards at the top of the dashboard.
	public function getStats() {
		$stats = ['bookings_today' => 0, 'counter_today' => 0, 'pending_refunds' => 0, 'occupancy' => 0];
		if (!$this->conn) {
			return $stats;
		}

		$online  = $this->fetchOne("SELECT COUNT(*) AS n FROM bookings WHERE DATE(booked_at) = CURDATE()");
		$counter = $this->fetchOne("SELECT COUNT(*) AS n FROM counter_bookings WHERE DATE(created_at) = CURDATE() AND status <> 'Void'");
		$refunds = $this->fetchOne("SELECT COUNT(*) AS n FROM refunds WHERE status IN ('Pending', 'Approved')");

		// taken seats out of all seats on upcoming active trips
		$capacity = $this->fetchOne("SELECT COALESCE(SUM(b.seat_capacity), 0) AS n
			FROM schedules s JOIN buses b ON b.id = s.bus_id
			WHERE s.status = 'Active' AND s.travel_date >= CURDATE()");
		$taken = $this->fetchOne("SELECT COUNT(*) AS n
			FROM seat_allocations sa JOIN schedules s ON s.id = sa.schedule_id
			WHERE s.status = 'Active' AND s.travel_date >= CURDATE()");

		$stats['counter_today']   = (int) $counter['n'];
		$stats['bookings_today']  = (int) $online['n'] + $stats['counter_today'];
		$stats['pending_refunds'] = (int) $refunds['n'];
		$stats['occupancy']       = $capacity['n'] > 0 ? (int) round($taken['n'] / $capacity['n'] * 100) : 0;
		return $stats;
	}

	// The latest online and counter tickets together, newest first.
	public function getRecentReservations($limit = 5) {
		if (!$this->conn) {
			return [];
		}
		return $this->fetchAll("SELECT * FROM (
				SELECT CONCAT('TKT-', bk.id) AS ref, u.name AS customer, r.origin, r.destination,
					bk.seat_no, bk.status, bk.booked_at AS created_at, 'Online' AS channel
				FROM bookings bk
				JOIN users u ON u.id = bk.user_id
				JOIN schedules s ON s.id = bk.schedule_id
				JOIN routes r ON r.id = s.route_id
				UNION ALL
				SELECT CONCAT('CTR-', cb.id), cb.customer_name, r.origin, r.destination,
					cb.seat_no, cb.status, cb.created_at, 'Counter'
				FROM counter_bookings cb
				JOIN schedules s ON s.id = cb.schedule_id
				JOIN routes r ON r.id = s.route_id
			) x
			ORDER BY created_at DESC
			LIMIT " . (int) $limit);
	}

	/* ---------- counter sales and seats ---------- */

	// Upcoming active trips for the trip picker.
	public function getActiveSchedules() {
		if (!$this->conn) {
			return [];
		}
		return $this->fetchAll($this->scheduleSelect() . " WHERE s.status = 'Active' AND s.travel_date >= CURDATE()
			ORDER BY s.travel_date, s.departure_time");
	}

	// One trip with its route and bus, or null.
	public function getSchedule($id) {
		if (!$this->conn) {
			return null;
		}
		return $this->fetchOne($this->scheduleSelect() . " WHERE s.id = ?", "i", [$id]);
	}

	// Every seat on the trip's bus: ['A1' => 'free' | 'taken' | 'counter', ...].
	// Seats are A1-A4, B1-B4 ... up to the bus's seat capacity (max 40).
	public function getSeatMap($schedule) {
		if (!$this->conn || !$schedule) {
			return [];
		}
		$state = [];
		foreach ($this->fetchAll("SELECT seat_no, alloc_type FROM seat_allocations WHERE schedule_id = ?", "i", [$schedule['id']]) as $row) {
			$state[strtoupper($row['seat_no'])] = $row['alloc_type'] === 'Counter' ? 'counter' : 'taken';
		}
		foreach ($this->fetchAll("SELECT seat_no FROM bookings WHERE schedule_id = ? AND status = 'Confirmed'", "i", [$schedule['id']]) as $row) {
			$seat = strtoupper($row['seat_no']);
			if (!isset($state[$seat])) {
				$state[$seat] = 'taken';
			}
		}

		$map   = [];
		$seats = min(40, (int) $schedule['seat_capacity']);
		for ($i = 0; $i < $seats; $i++) {
			$seat       = chr(ord('A') + intdiv($i, 4)) . ($i % 4 + 1);
			$map[$seat] = $state[$seat] ?? 'free';
		}
		return $map;
	}

	// Sells a free seat at the counter. Returns ['success' => bool, 'message' => string].
	public function assignSeat($scheduleId, $seat, $name, $phone, $mode, $managerId) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		$seat     = strtoupper(trim($seat));
		$schedule = $this->getSchedule($scheduleId);

		if (!$schedule || $schedule['status'] !== 'Active') {
			return $this->result(false, "This trip is not open for booking.");
		}
		$map = $this->getSeatMap($schedule);
		if (!isset($map[$seat])) {
			return $this->result(false, "Seat $seat does not exist on this bus.");
		}
		if ($map[$seat] !== 'free') {
			return $this->result(false, "Seat $seat is already taken. Please choose a free seat.");
		}
		if (!in_array($mode, $this->paymentModes, true)) {
			return $this->result(false, "Please choose a payment method.");
		}

		$amount = (float) $schedule['fare'];
		try {
			$this->conn->begin_transaction();

			$stmt = $this->conn->prepare("INSERT INTO counter_bookings (schedule_id, customer_name, phone, seat_no, amount, payment_mode, issued_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Paid')");
			$stmt->bind_param("isssdsi", $scheduleId, $name, $phone, $seat, $amount, $mode, $managerId);
			$stmt->execute();
			$ticket = 'CTR-' . $this->conn->insert_id;

			// the UNIQUE key on (schedule_id, seat_no) stops a seat being sold twice
			$stmt = $this->conn->prepare("INSERT INTO seat_allocations (schedule_id, seat_no, booking_ref, alloc_type) VALUES (?, ?, ?, 'Counter')");
			$stmt->bind_param("iss", $scheduleId, $seat, $ticket);
			$stmt->execute();

			$this->conn->commit();
			return $this->result(true, "Seat $seat assigned to $name. Ticket $ticket, BDT " . number_format($amount, 2) . " paid by $mode.");
		} catch (mysqli_sql_exception $e) {
			$this->conn->rollback();
			if ($e->getCode() === 1062) {
				return $this->result(false, "Seat $seat was just taken. Please choose another seat.");
			}
			return $this->result(false, "Could not assign the seat: " . $e->getMessage());
		}
	}

	// Releases a seat that was sold at the counter: voids the sale, frees the
	// seat and opens a refund request. Returns ['success' => bool, 'message' => string].
	public function releaseSeat($scheduleId, $seat) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		$seat = strtoupper(trim($seat));
		$sale = $this->fetchOne("SELECT id, amount, payment_mode FROM counter_bookings
			WHERE schedule_id = ? AND seat_no = ? AND status = 'Paid' ORDER BY id DESC LIMIT 1", "is", [$scheduleId, $seat]);

		if (!$sale) {
			return $this->result(false, "Seat $seat was not sold at the counter. Online tickets are cancelled by the passenger.");
		}

		$ticket = 'CTR-' . $sale['id'];
		$amount = (float) $sale['amount'];
		$method = $sale['payment_mode'];
		try {
			$this->conn->begin_transaction();

			$stmt = $this->conn->prepare("UPDATE counter_bookings SET status = 'Void' WHERE id = ?");
			$stmt->bind_param("i", $sale['id']);
			$stmt->execute();

			$stmt = $this->conn->prepare("DELETE FROM seat_allocations WHERE schedule_id = ? AND seat_no = ? AND alloc_type = 'Counter'");
			$stmt->bind_param("is", $scheduleId, $seat);
			$stmt->execute();

			$stmt = $this->conn->prepare("INSERT INTO refunds (booking_ref, cancel_reason, ticket_amount, deduction, refund_amount, method, status) VALUES (?, 'Released at counter', ?, 0, ?, ?, 'Pending')");
			$stmt->bind_param("sdds", $ticket, $amount, $amount, $method);
			$stmt->execute();

			$this->conn->commit();
			return $this->result(true, "Seat $seat released. Ticket $ticket is void and a refund request was opened.");
		} catch (mysqli_sql_exception $e) {
			$this->conn->rollback();
			return $this->result(false, "Could not release the seat: " . $e->getMessage());
		}
	}

	/* ---------- refunds ---------- */

	// Refund requests with the customer and trip, open ones first.
	public function getRefunds($limit = 0) {
		if (!$this->conn) {
			return [];
		}
		$sql = "SELECT rf.id, rf.booking_ref, rf.cancel_reason, rf.ticket_amount, rf.deduction, rf.refund_amount,
				rf.method, rf.status, rf.requested_at, rf.processed_at,
				COALESCE(u.name, cb.customer_name) AS customer,
				COALESCE(r1.origin, r2.origin) AS origin,
				COALESCE(r1.destination, r2.destination) AS destination
			FROM refunds rf
			LEFT JOIN bookings bk ON rf.booking_ref = CONCAT('TKT-', bk.id)
			LEFT JOIN users u ON u.id = bk.user_id
			LEFT JOIN schedules s1 ON s1.id = bk.schedule_id
			LEFT JOIN routes r1 ON r1.id = s1.route_id
			LEFT JOIN counter_bookings cb ON rf.booking_ref = CONCAT('CTR-', cb.id)
			LEFT JOIN schedules s2 ON s2.id = cb.schedule_id
			LEFT JOIN routes r2 ON r2.id = s2.route_id
			ORDER BY rf.status IN ('Pending', 'Approved') DESC, rf.requested_at DESC, rf.id DESC";
		if ($limit > 0) {
			$sql .= " LIMIT " . (int) $limit;
		}
		return $this->fetchAll($sql);
	}

	// Approves (Settled) or rejects an open refund. The deduction is a percentage
	// of the ticket price. Returns ['success' => bool, 'message' => string].
	public function processRefund($id, $decision, $deductionPercent, $method, $managerId) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		if (!in_array($decision, ['Settled', 'Rejected'], true)) {
			return $this->result(false, "Please choose to approve or reject the refund.");
		}
		if (!in_array($method, $this->refundMethods, true)) {
			return $this->result(false, "Please choose a payout method.");
		}

		$refund = $this->fetchOne("SELECT id, booking_ref, ticket_amount, status FROM refunds WHERE id = ?", "i", [$id]);
		if (!$refund) {
			return $this->result(false, "Refund request not found.");
		}
		if (!in_array($refund['status'], ['Pending', 'Approved'], true)) {
			return $this->result(false, "Refund for {$refund['booking_ref']} was already processed.");
		}

		$ticket  = (float) $refund['ticket_amount'];
		$percent = max(0, min(100, (float) $deductionPercent));
		if ($decision === 'Settled') {
			$deduction = round($ticket * $percent / 100, 2);
			$amount    = round($ticket - $deduction, 2);
		} else {
			$deduction = 0;
			$amount    = 0;
		}

		$stmt = $this->conn->prepare("UPDATE refunds SET status = ?, deduction = ?, refund_amount = ?, method = ?, processed_by = ?, processed_at = NOW()
			WHERE id = ? AND status IN ('Pending', 'Approved')");
		$stmt->bind_param("sddsii", $decision, $deduction, $amount, $method, $managerId, $id);
		$stmt->execute();

		if ($decision === 'Settled') {
			return $this->result(true, "Refund for {$refund['booking_ref']} settled: BDT " . number_format($amount, 2) . " by $method after a {$percent}% deduction.");
		}
		return $this->result(true, "Refund for {$refund['booking_ref']} rejected.");
	}

	/* ---------- helpers ---------- */

	private function scheduleSelect() {
		return "SELECT s.id, s.fare, s.status, s.travel_date, s.departure_time,
				r.origin, r.destination, b.bus_number, b.type AS bus_type, b.seat_capacity
			FROM schedules s
			JOIN routes r ON r.id = s.route_id
			JOIN buses b ON b.id = s.bus_id";
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
