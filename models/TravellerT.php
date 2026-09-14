<?php
require_once __DIR__ . '/PreviewT.php';

class Traveller {
	private $conn;
	private $genders = ['male', 'female', 'other'];

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// Every saved co-traveller of one passenger.
	public function getByUser($userId) {
		if (!$this->conn) {
			return Preview::where(Preview::travellers(), 'user_id', $userId);
		}
		$stmt = $this->conn->prepare("SELECT * FROM travellers WHERE user_id = ? ORDER BY name");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}

	// One co-traveller, only if it belongs to this passenger, or null.
	public function getByIdAndUser($id, $userId) {
		if (!$this->conn) {
			$traveller = Preview::find(Preview::travellers(), $id);
			return ($traveller && (int) $traveller['user_id'] === (int) $userId) ? $traveller : null;
		}
		$stmt = $this->conn->prepare("SELECT * FROM travellers WHERE id = ? AND user_id = ?");
		$stmt->bind_param("ii", $id, $userId);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// Returns ['success' => bool, 'message' => string].
	public function createTraveller($userId, $data) {
		if (!$this->conn) {
			return ['success' => false, 'message' => Preview::message()];
		}
		[$email, $gender, $age, $relation] = $this->optionalFields($data);
		try {
			$stmt = $this->conn->prepare("INSERT INTO travellers (user_id, name, phone, email, gender, age, relation) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("issssis", $userId, $data['name'], $data['phone'], $email, $gender, $age, $relation);
			$stmt->execute();
			return ['success' => true, 'message' => "Co-traveller added."];
		} catch (mysqli_sql_exception $e) {
			return ['success' => false, 'message' => "Could not save the co-traveller: " . $e->getMessage()];
		}
	}

	// Returns ['success' => bool, 'message' => string].
	public function updateTraveller($id, $userId, $data) {
		if (!$this->conn) {
			return ['success' => false, 'message' => Preview::message()];
		}
		[$email, $gender, $age, $relation] = $this->optionalFields($data);
		try {
			$stmt = $this->conn->prepare("UPDATE travellers SET name = ?, phone = ?, email = ?, gender = ?, age = ?, relation = ? WHERE id = ? AND user_id = ?");
			$stmt->bind_param("ssssisii", $data['name'], $data['phone'], $email, $gender, $age, $relation, $id, $userId);
			$stmt->execute();
			return ['success' => true, 'message' => "Co-traveller updated."];
		} catch (mysqli_sql_exception $e) {
			return ['success' => false, 'message' => "Could not update the co-traveller: " . $e->getMessage()];
		}
	}

	// Returns true when a row was deleted. Past bookings keep working:
	// their traveller link is simply cleared.
	public function deleteTraveller($id, $userId) {
		if (!$this->conn) {
			return false;
		}
		$stmt = $this->conn->prepare("DELETE FROM travellers WHERE id = ? AND user_id = ?");
		$stmt->bind_param("ii", $id, $userId);
		$stmt->execute();
		return $stmt->affected_rows > 0;
	}

	// Empty optional fields are stored as NULL rather than as ''.
	private function optionalFields($data) {
		$gender = strtolower(trim($data['gender'] ?? ''));
		return [
			($data['email'] ?? '') !== '' ? $data['email'] : null,
			in_array($gender, $this->genders, true) ? $gender : null,
			($data['age'] ?? '') !== '' ? (int) $data['age'] : null,
			($data['relation'] ?? '') !== '' ? $data['relation'] : null,
		];
	}
}
