<?php
require_once __DIR__ . '/PreviewT.php';

class Schedule {
	private $conn;

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// Returns the new schedule id (int) on success, or an error message (string).
	// Fails if bus_id or route_id does not exist.
	public function insertSchedule($bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, fare, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("iisssds", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return $this->conn->insert_id;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns every schedule as an array of rows.
	public function getAllSchedules() {
		if (!$this->conn) {
			return Preview::schedules();
		}
		$result = $this->conn->query("SELECT * FROM schedules ORDER BY travel_date, departure_time");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// Every schedule with its bus number and route cities, for the schedule list.
	public function getAllSchedulesWithNames() {
		if (!$this->conn) {
			$rows = [];
			foreach (Preview::schedules() as $schedule) {
				$bus    = Preview::find(Preview::buses(), $schedule['bus_id']);
				$route  = Preview::find(Preview::routes(), $schedule['route_id']);
				$rows[] = $schedule + ['bus_number' => $bus['bus_number'], 'origin' => $route['origin'], 'destination' => $route['destination']];
			}
			return $rows;
		}
		$result = $this->conn->query("SELECT s.*, b.bus_number, r.origin, r.destination
			FROM schedules s
			JOIN buses b ON b.id = s.bus_id
			JOIN routes r ON r.id = s.route_id
			ORDER BY s.travel_date, s.departure_time");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// Returns one schedule as an array, or null if the id does not exist.
	public function getScheduleById($id) {
		if (!$this->conn) {
			return Preview::find(Preview::schedules(), $id);
		}
		$stmt = $this->conn->prepare("SELECT * FROM schedules WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// Returns true on success, or an error message (string).
	public function updateSchedule($id, $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("UPDATE schedules SET bus_id = ?, route_id = ?, travel_date = ?, departure_time = ?, arrival_time = ?, fare = ?, status = ? WHERE id = ?");
			$stmt->bind_param("iisssdsi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $fare, $status, $id);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return true;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns true on success, or an error message (string).
	public function deleteSchedule($id) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("DELETE FROM schedules WHERE id = ?");
			$stmt->bind_param("i", $id);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return true;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}
}
