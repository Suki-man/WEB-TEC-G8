<?php
require_once __DIR__ . '/PreviewT.php';

class Route {
	private $conn;

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// Returns the new route id (int) on success, or an error message (string).
	public function insertRoute($origin, $destination, $distance_km, $est_duration, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("INSERT INTO routes (origin, destination, distance_km, est_duration, status) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param("ssdss", $origin, $destination, $distance_km, $est_duration, $status);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return $this->conn->insert_id;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns every route as an array of rows.
	public function getAllRoutes() {
		if (!$this->conn) {
			return Preview::routes();
		}
		$result = $this->conn->query("SELECT * FROM routes ORDER BY id");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// Returns one route as an array, or null if the id does not exist.
	public function getRouteById($id) {
		if (!$this->conn) {
			return Preview::find(Preview::routes(), $id);
		}
		$stmt = $this->conn->prepare("SELECT * FROM routes WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// Returns true on success, or an error message (string).
	public function updateRoute($id, $origin, $destination, $distance_km, $est_duration, $status) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("UPDATE routes SET origin = ?, destination = ?, distance_km = ?, est_duration = ?, status = ? WHERE id = ?");
			$stmt->bind_param("ssdssi", $origin, $destination, $distance_km, $est_duration, $status, $id);
			if (!$stmt->execute()) {
				return $stmt->error;
			}
			return true;
		} catch (mysqli_sql_exception $e) {
			return $e->getMessage();
		}
	}

	// Returns true on success, or an error message (string).
	// Fails while a schedule still uses this route.
	public function deleteRoute($id) {
		if (!$this->conn) {
			return Preview::message();
		}
		try {
			$stmt = $this->conn->prepare("DELETE FROM routes WHERE id = ?");
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
