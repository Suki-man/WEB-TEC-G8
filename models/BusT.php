<?php
require_once __DIR__ . '/PreviewT.php';

class Bus {
	private $conn;

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// Returns the new bus id (int) on success, or an error message (string).
	public function insertBus($bus_number, $type, $seat_capacity, $layout, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("INSERT INTO buses (bus_number, type, seat_capacity, layout, status) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param("ssiss", $bus_number, $type, $seat_capacity, $layout, $status);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return $this->conn->insert_id;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns every bus as an array of rows.
	public function getAllBuses() {
		if (!$this->conn) {
			return Preview::buses();
		}
		$result = $this->conn->query("SELECT * FROM buses ORDER BY id");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// Returns one bus as an array, or null if the id does not exist.
	public function getBusById($id) {
		if (!$this->conn) {
			return Preview::find(Preview::buses(), $id);
		}
		$stmt = $this->conn->prepare("SELECT * FROM buses WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// Returns true on success, or an error message (string).
	public function updateBus($id, $bus_number, $type, $seat_capacity, $layout, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("UPDATE buses SET bus_number = ?, type = ?, seat_capacity = ?, layout = ?, status = ? WHERE id = ?");
			$stmt->bind_param("ssissi", $bus_number, $type, $seat_capacity, $layout, $status, $id);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return true;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns true on success, or an error message (string).
	// Fails while a schedule still uses this bus.
	public function deleteBus($id) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("DELETE FROM buses WHERE id = ?");
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
