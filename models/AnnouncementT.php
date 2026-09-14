<?php
require_once __DIR__ . '/PreviewT.php';

// Announcements (notices) posted by the admin or a booking manager.
class Announcement {
	private $conn;
	// audience value in the database => what the pages call it
	private $audiences = [
		'All'     => 'Everyone',
		'User'    => 'Passengers',
		'Manager' => 'Booking managers',
		'Admin'   => 'Admins',
	];

	public function __construct() {
		// null while the database is not set up yet
		$this->conn = Preview::connect();
	}

	public function isConnected() {
		return $this->conn !== null;
	}

	public function getAudiences() {
		return $this->audiences;
	}

	// Every announcement with the poster's name, newest first.
	public function getAll() {
		if (!$this->conn) {
			return [];
		}
		return $this->fetchAll("SELECT a.*, u.name AS author
			FROM announcements a
			LEFT JOIN users u ON u.id = a.created_by
			ORDER BY a.created_at DESC, a.id DESC");
	}

	// One announcement, or null.
	public function find($id) {
		if (!$this->conn) {
			return null;
		}
		return $this->fetchOne("SELECT * FROM announcements WHERE id = ?", "i", [$id]);
	}

	// Switched-on announcements for these audiences whose dates include today.
	public function getLiveFor($audiences, $limit = 5) {
		if (!$this->conn || !$audiences) {
			return [];
		}
		$marks = implode(', ', array_fill(0, count($audiences), '?'));
		return $this->fetchAll("SELECT id, title, body, audience, publish_from, publish_to, created_at
			FROM announcements
			WHERE is_active = 1 AND audience IN ($marks) AND CURDATE() BETWEEN publish_from AND publish_to
			ORDER BY created_at DESC, id DESC
			LIMIT " . (int) $limit, str_repeat('s', count($audiences)), array_values($audiences));
	}

	// Returns ['success' => bool, 'message' => string].
	public function create($data, $userId) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		try {
			$stmt = $this->conn->prepare("INSERT INTO announcements (created_by, title, body, audience, publish_from, publish_to, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("isssssi", $userId, $data['title'], $data['body'], $data['audience'], $data['publish_from'], $data['publish_to'], $data['is_active']);
			$stmt->execute();
			return $this->result(true, "Announcement \"{$data['title']}\" posted.");
		} catch (mysqli_sql_exception $e) {
			return $this->result(false, "Could not post the announcement: " . $e->getMessage());
		}
	}

	// Returns ['success' => bool, 'message' => string].
	public function update($id, $data) {
		if (!$this->conn) {
			return $this->result(false, Preview::message());
		}
		try {
			$stmt = $this->conn->prepare("UPDATE announcements SET title = ?, body = ?, audience = ?, publish_from = ?, publish_to = ?, is_active = ? WHERE id = ?");
			$stmt->bind_param("sssssii", $data['title'], $data['body'], $data['audience'], $data['publish_from'], $data['publish_to'], $data['is_active'], $id);
			$stmt->execute();
			return $this->result(true, "Announcement \"{$data['title']}\" updated.");
		} catch (mysqli_sql_exception $e) {
			return $this->result(false, "Could not update the announcement: " . $e->getMessage());
		}
	}

	// Switches an announcement on (1) or off (0). Returns true when it exists.
	public function setActive($id, $active) {
		if (!$this->conn) {
			return false;
		}
		$active = $active ? 1 : 0;
		$stmt = $this->conn->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
		$stmt->bind_param("ii", $active, $id);
		$stmt->execute();
		return $this->find($id) !== null;
	}

	// Returns true when a row was deleted.
	public function delete($id) {
		if (!$this->conn) {
			return false;
		}
		$stmt = $this->conn->prepare("DELETE FROM announcements WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->affected_rows > 0;
	}

	/* ---------- helpers ---------- */

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
