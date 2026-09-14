<?php
require_once __DIR__ . '/PreviewT.php';

class Watchlist {
	private $conn;

	public function __construct() {
		// null while the database is not set up yet (preview mode)
		$this->conn = Preview::connect();
	}

	// Active routes for the drop-down: id, from_city, to_city.
	public function getAllRoutes() {
		if (!$this->conn) {
			$routes = [];
			foreach (Preview::where(Preview::routes(), 'status', 'Active') as $route) {
				$routes[] = ['id' => $route['id'], 'from_city' => $route['origin'], 'to_city' => $route['destination']];
			}
			return $routes;
		}
		$result = $this->conn->query("SELECT id, origin AS from_city, destination AS to_city FROM routes WHERE status = 'Active' ORDER BY origin, destination");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// A passenger's saved routes. Each one also shows the next trip on that
	// route (on the preferred date if one is set) and how many seats are free.
	public function getWatchlistWithSeatCount($userId) {
		if (!$this->conn) {
			$items = [];
			foreach (Preview::where(Preview::watchlist(), 'user_id', $userId) as $row) {
				$route   = Preview::find(Preview::routes(), $row['route_id']);
				$items[] = $row + ['from_city' => $route['origin'], 'to_city' => $route['destination'], 'distance' => $route['distance_km']];
			}
		} else {
			$stmt = $this->conn->prepare("SELECT w.id, w.route_id, w.preferred_date,
					r.origin AS from_city, r.destination AS to_city, r.distance_km AS distance
				FROM watchlist w
				JOIN routes r ON r.id = w.route_id
				WHERE w.user_id = ?
				ORDER BY w.id");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		}

		foreach ($items as &$item) {
			$trip = $this->nextTrip($item['route_id'], $item['preferred_date']);

			$item['has_schedule'] = $trip !== null;
			$item['schedule_id']  = $trip ? $trip['id'] : null;
			$item['bus_name']     = $trip ? $trip['bus_number'] : '';
			$item['next_date']    = $trip ? $trip['travel_date'] : null;
			$item['next_time']    = $trip ? $trip['departure_time'] : null;
			$item['fare']         = $trip ? $trip['fare'] : 0;
			$item['total_seats']  = $trip ? (int) $trip['seat_capacity'] : 0;
			$item['free_seats']   = $trip ? max(0, (int) $trip['seat_capacity'] - (int) $trip['taken']) : 0;
		}
		unset($item);

		return $items;
	}

	// One saved route, only if it belongs to this passenger, or null.
	public function getByIdAndUser($id, $userId) {
		if (!$this->conn) {
			$row = Preview::find(Preview::watchlist(), $id);
			return ($row && (int) $row['user_id'] === (int) $userId) ? $row : null;
		}
		$stmt = $this->conn->prepare("SELECT id, route_id, preferred_date FROM watchlist WHERE id = ? AND user_id = ?");
		$stmt->bind_param("ii", $id, $userId);
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}

	// Returns ['success' => bool, 'message' => string].
	public function addWatchlist($userId, $routeId, $preferredDate) {
		if (!$this->conn) {
			return ['success' => false, 'message' => Preview::message()];
		}
		$date  = $preferredDate !== '' ? $preferredDate : null;
		$error = $this->checkRoute($userId, $routeId, $date, 0);
		if ($error !== null) {
			return ['success' => false, 'message' => $error];
		}
		$stmt = $this->conn->prepare("INSERT INTO watchlist (user_id, route_id, preferred_date) VALUES (?, ?, ?)");
		$stmt->bind_param("iis", $userId, $routeId, $date);
		$stmt->execute();
		return ['success' => true, 'message' => "Route saved to your watchlist."];
	}

	// Returns ['success' => bool, 'message' => string].
	public function updateWatchlist($id, $userId, $routeId, $preferredDate) {
		if (!$this->conn) {
			return ['success' => false, 'message' => Preview::message()];
		}
		$date  = $preferredDate !== '' ? $preferredDate : null;
		$error = $this->checkRoute($userId, $routeId, $date, $id);
		if ($error !== null) {
			return ['success' => false, 'message' => $error];
		}
		$stmt = $this->conn->prepare("UPDATE watchlist SET route_id = ?, preferred_date = ? WHERE id = ? AND user_id = ?");
		$stmt->bind_param("isii", $routeId, $date, $id, $userId);
		$stmt->execute();
		return ['success' => true, 'message' => "Watchlist updated."];
	}

	// Returns true when a row was deleted.
	public function deleteWatchlist($id, $userId) {
		if (!$this->conn) {
			return false;
		}
		$stmt = $this->conn->prepare("DELETE FROM watchlist WHERE id = ? AND user_id = ?");
		$stmt->bind_param("ii", $id, $userId);
		$stmt->execute();
		return $stmt->affected_rows > 0;
	}

	/* ---------- helpers ---------- */

	// The route must exist, and the same route and date must not be saved twice.
	private function checkRoute($userId, $routeId, $date, $ignoreId) {
		$stmt = $this->conn->prepare("SELECT id FROM routes WHERE id = ? AND status = 'Active'");
		$stmt->bind_param("i", $routeId);
		$stmt->execute();
		if ($stmt->get_result()->num_rows === 0) {
			return "Please choose a route from the list.";
		}

		// <=> treats two empty dates as equal
		$stmt = $this->conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND route_id = ? AND preferred_date <=> ? AND id <> ?");
		$stmt->bind_param("iisi", $userId, $routeId, $date, $ignoreId);
		$stmt->execute();
		if ($stmt->get_result()->num_rows > 0) {
			return "This route is already on your watchlist for that date.";
		}
		return null;
	}

	// The next active trip on a route, or null.
	private function nextTrip($routeId, $preferredDate) {
		if (!$this->conn) {
			foreach (Preview::where(Preview::schedules(), 'route_id', $routeId) as $schedule) {
				$bus = Preview::find(Preview::buses(), $schedule['bus_id']);
				if ($schedule['status'] === 'Active' && $bus['status'] === 'Active'
					&& (empty($preferredDate) || $schedule['travel_date'] === $preferredDate)) {
					$taken = count(Preview::where(Preview::bookings(), 'schedule_id', $schedule['id']));
					return $schedule + ['bus_number' => $bus['bus_number'], 'seat_capacity' => $bus['seat_capacity'], 'taken' => $taken];
				}
			}
			return null;
		}
		$sql = "SELECT s.id, s.travel_date, s.departure_time, s.fare, b.bus_number, b.seat_capacity,
				(SELECT COUNT(*) FROM seat_allocations sa WHERE sa.schedule_id = s.id) AS taken
			FROM schedules s
			JOIN buses b ON b.id = s.bus_id
			WHERE s.route_id = ? AND s.status = 'Active' AND b.status = 'Active'";

		if (!empty($preferredDate)) {
			$sql .= " AND s.travel_date = ? ORDER BY s.departure_time LIMIT 1";
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param("is", $routeId, $preferredDate);
		} else {
			$sql .= " AND s.travel_date >= CURDATE() ORDER BY s.travel_date, s.departure_time LIMIT 1";
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param("i", $routeId);
		}
		$stmt->execute();
		return $stmt->get_result()->fetch_assoc();
	}
}
