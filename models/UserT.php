<?php
require_once __DIR__ . '/PreviewT.php';

class User {
	private $conn;
	private $roles    = ['passenger', 'admin', 'bookingmgr', 'busroute'];
	private $statuses = ['active', 'inactive'];

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// One user by email, including the password hash (used at login), or null.
	public function findByEmail($email) {
		if (!$this->conn) {
			foreach (Preview::users() as $user) {
				if (strcasecmp($user['email'], $email) === 0) {
					return $user;
				}
			}
			return null;
		}
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// One user by email or username, including the password hash (used at login), or null.
	public function findByLogin($login) {
		if (!$this->conn) {
			foreach (Preview::users() as $user) {
				if (strcasecmp($user['email'], $login) === 0 || strcasecmp($user['username'], $login) === 0) {
					return $user;
				}
			}
			return null;
		}
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
		$stmt->bind_param("ss", $login, $login);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// One user by id, without the password hash, or null.
	public function find($id) {
		if (!$this->conn) {
			$user = Preview::find(Preview::users(), $id);
			if ($user) {
				unset($user['password'], $user['username']);
			}
			return $user;
		}
		$stmt = $this->conn->prepare("SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// A new customer from the sign-up page. Returns true or false.
	public function registerCustomer($name, $email, $phone, $password) {
		if (!$this->conn) {
			return false;
		}
		try {
			$hash = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $this->conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'passenger', 'active')");
			$stmt->bind_param("ssss", $name, $email, $phone, $hash);
			return $stmt->execute();
		} catch (mysqli_sql_exception $e) {
			return false;
		}
	}

	// The admin list, with optional role and status filters and a search word.
	public function filterUsers($role = '', $status = '', $search = '') {
		if (!$this->conn) {
			return $this->filterPreviewUsers($role, $status, $search);
		}

		$sql    = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE 1 = 1";
		$types  = '';
		$params = [];

		if (in_array($role, $this->roles, true)) {
			$sql .= " AND role = ?";
			$types .= 's';
			$params[] = $role;
		}
		if (in_array($status, $this->statuses, true)) {
			$sql .= " AND status = ?";
			$types .= 's';
			$params[] = $status;
		}
		if ($search !== '') {
			$sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
			$like = '%' . $search . '%';
			$types .= 'sss';
			array_push($params, $like, $like, $like);
		}
		$sql .= " ORDER BY id";

		$stmt = $this->conn->prepare($sql);
		if ($params) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}

	// A new account of any role, created by the admin. Returns true or false.
	public function createByAdmin($data) {
		if (!$this->conn) {
			return false;
		}
		if (!in_array($data['role'], $this->roles, true) || !in_array($data['status'], $this->statuses, true)) {
			return false;
		}
		try {
			$hash = password_hash($data['password'], PASSWORD_DEFAULT);
			$stmt = $this->conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssss", $data['name'], $data['email'], $data['phone'], $hash, $data['role'], $data['status']);
			return $stmt->execute();
		} catch (mysqli_sql_exception $e) {
			return false;
		}
	}

	// Returns the number of changed rows (0 is fine), or false on an error.
	// The password is only changed when one is given.
	public function updateByAdmin($id, $data) {
		if (!$this->conn) {
			return false;
		}
		if (!in_array($data['role'], $this->roles, true) || !in_array($data['status'], $this->statuses, true)) {
			return false;
		}
		try {
			if (!empty($data['password'])) {
				$hash = password_hash($data['password'], PASSWORD_DEFAULT);
				$stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ?, password = ? WHERE id = ?");
				$stmt->bind_param("ssssssi", $data['name'], $data['email'], $data['phone'], $data['role'], $data['status'], $hash, $id);
			} else {
				$stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
				$stmt->bind_param("sssssi", $data['name'], $data['email'], $data['phone'], $data['role'], $data['status'], $id);
			}
			$stmt->execute();
			return $stmt->affected_rows;
		} catch (mysqli_sql_exception $e) {
			return false;
		}
	}

	// Returns true or false.
	public function updateStatus($id, $status) {
		if (!$this->conn || !in_array($status, $this->statuses, true)) {
			return false;
		}
		$stmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
		$stmt->bind_param("si", $status, $id);
		return $stmt->execute();
	}

	// Returns true when the account was deleted. Returns false while other
	// records (counter sales, notices, complaint replies) still point at it.
	public function deleteAccount($id) {
		if (!$this->conn) {
			return false;
		}
		try {
			$stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
			$stmt->bind_param("i", $id);
			$stmt->execute();
			return $stmt->affected_rows > 0;
		} catch (mysqli_sql_exception $e) {
			return false;
		}
	}

	// Same filters as filterUsers(), on the sample accounts.
	private function filterPreviewUsers($role, $status, $search) {
		$users = [];
		foreach (Preview::users() as $user) {
			if (in_array($role, $this->roles, true) && $user['role'] !== $role) {
				continue;
			}
			if (in_array($status, $this->statuses, true) && $user['status'] !== $status) {
				continue;
			}
			if ($search !== '' && stripos($user['name'] . ' ' . $user['email'] . ' ' . $user['phone'], $search) === false) {
				continue;
			}
			unset($user['password'], $user['username']);
			$users[] = $user;
		}
		return $users;
	}
}
