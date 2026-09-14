<?php
require_once __DIR__ . '/../db.php';

// Preview mode: while the bus_management database is not set up yet (or MySQL
// is stopped), the models use the sample rows below instead of crashing, so
// every page can still be opened. Nothing is saved in preview mode.
// The sample rows are the same as the ones in busdbT.sql.
class Preview {
	private static $checked = false;
	private static $conn    = null;
	private static $hash      = '$2y$10$etJBpqx9vCGZvkyP37wAa.KLwt6.zLPNGntlXAa8Pvxz7WCO.3KWK'; // 12345678
	private static $adminHash = '$2y$10$z3SUYouX.AKwxlyCYRULdeXpgbfS5f0T5edHhEQUTqoap19PEBzpm'; // admin

	// The database connection, or null when there is no database yet.
	public static function connect() {
		if (!self::$checked) {
			self::$checked = true;
			try {
				self::$conn = (new DBConnection())->connect();
			} catch (Throwable $e) {
				self::$conn = null;
			}
		}
		return self::$conn;
	}

	public static function isOn() {
		return self::connect() === null;
	}

	public static function message() {
		return "Preview mode: the database is not connected yet, so nothing was saved.";
	}

	/* ---------- sample rows ---------- */

	public static function users() {
		return [
			['id' => 1, 'name' => 'Admin User',      'username' => 'admin',      'email' => 'admin@bus.com',      'password' => self::$adminHash, 'phone' => '01711000001', 'role' => 'admin',      'status' => 'active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 2, 'name' => 'Manager User',    'username' => 'manager',    'email' => 'manager@bus.com',    'password' => self::$hash, 'phone' => '01711000002', 'role' => 'busroute',   'status' => 'active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 3, 'name' => 'Normal User',     'username' => 'user',       'email' => 'user@bus.com',       'password' => self::$hash, 'phone' => '01711000003', 'role' => 'passenger',  'status' => 'active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 4, 'name' => 'Booking Manager', 'username' => 'bookingmgr', 'email' => 'bookingmgr@bus.com', 'password' => self::$hash, 'phone' => '01711000004', 'role' => 'bookingmgr', 'status' => 'active', 'created_at' => '2026-09-01 10:00:00'],
		];
	}

	public static function buses() {
		return [
			['id' => 1, 'bus_number' => 'DHK-1101', 'type' => 'AC',      'seat_capacity' => 40, 'layout' => '2x2', 'status' => 'Active',   'created_at' => '2026-09-01 10:00:00'],
			['id' => 2, 'bus_number' => 'DHK-1102', 'type' => 'Non-AC',  'seat_capacity' => 36, 'layout' => '2x2', 'status' => 'Active',   'created_at' => '2026-09-01 10:00:00'],
			['id' => 3, 'bus_number' => 'DHK-1103', 'type' => 'Sleeper', 'seat_capacity' => 28, 'layout' => '2x1', 'status' => 'Inactive', 'created_at' => '2026-09-01 10:00:00'],
		];
	}

	public static function routes() {
		return [
			['id' => 1, 'origin' => 'Dhaka', 'destination' => 'Sylhet',     'distance_km' => '241.00', 'est_duration' => '6 hours',   'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 2, 'origin' => 'Dhaka', 'destination' => 'Chattogram', 'distance_km' => '264.00', 'est_duration' => '6.5 hours', 'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 3, 'origin' => 'Dhaka', 'destination' => 'Rajshahi',   'distance_km' => '256.00', 'est_duration' => '6 hours',   'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
		];
	}

	public static function schedules() {
		return [
			['id' => 1, 'bus_id' => 1, 'route_id' => 1, 'travel_date' => '2026-09-20', 'departure_time' => '22:30:00', 'arrival_time' => '04:30:00', 'fare' => '850.00', 'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 2, 'bus_id' => 2, 'route_id' => 2, 'travel_date' => '2026-09-21', 'departure_time' => '08:00:00', 'arrival_time' => '14:30:00', 'fare' => '650.00', 'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
			['id' => 3, 'bus_id' => 1, 'route_id' => 3, 'travel_date' => '2026-09-22', 'departure_time' => '09:30:00', 'arrival_time' => '15:30:00', 'fare' => '780.00', 'status' => 'Active', 'created_at' => '2026-09-01 10:00:00'],
		];
	}

	public static function travellers() {
		return [
			['id' => 1, 'user_id' => 3, 'name' => 'Rahim Uddin', 'age' => 45, 'gender' => 'male', 'nid_no' => null, 'phone' => '01711000010', 'email' => null, 'relation' => 'father'],
		];
	}

	public static function bookings() {
		return [
			['id' => 1, 'user_id' => 3, 'schedule_id' => 1, 'traveller_id' => null, 'seat_no' => 'A1', 'fare' => '850.00', 'status' => 'Confirmed', 'booked_at' => '2026-09-10 12:00:00'],
		];
	}

	public static function watchlist() {
		return [
			['id' => 1, 'user_id' => 3, 'route_id' => 2, 'preferred_date' => null],
		];
	}

	public static function notices() {
		return [
			['title' => 'Welcome to online booking', 'content' => 'You can now book seats, save co-travellers and watch routes online.', 'created_at' => '2026-09-01 10:00:00'],
		];
	}

	/* ---------- helpers ---------- */

	// The row with this id, or null.
	public static function find($rows, $id) {
		foreach ($rows as $row) {
			if ((int) $row['id'] === (int) $id) {
				return $row;
			}
		}
		return null;
	}

	// The rows where $field equals $value.
	public static function where($rows, $field, $value) {
		return array_values(array_filter($rows, function ($row) use ($field, $value) {
			return (string) $row[$field] === (string) $value;
		}));
	}

	// A schedule with its route and bus, named the way the booking pages expect.
	public static function trip($schedule) {
		$route = self::find(self::routes(), $schedule['route_id']);
		$bus   = self::find(self::buses(), $schedule['bus_id']);
		$taken = count(self::where(self::bookings(), 'schedule_id', $schedule['id']));

		return [
			'id'             => $schedule['id'],
			'fare'           => $schedule['fare'],
			'status'         => $schedule['status'],
			'departure_date' => $schedule['travel_date'],
			'departure_time' => $schedule['departure_time'],
			'arrival_time'   => $schedule['arrival_time'],
			'from_city'      => $route['origin'],
			'to_city'        => $route['destination'],
			'bus_name'       => $bus['bus_number'],
			'bus_type'       => $bus['type'],
			'seat_capacity'  => $bus['seat_capacity'],
			'free_seats'     => max(0, $bus['seat_capacity'] - $taken),
		];
	}

	// A booking with its trip and co-traveller, named the way the booking pages expect.
	public static function booking($booking) {
		$trip      = self::trip(self::find(self::schedules(), $booking['schedule_id']));
		$traveller = $booking['traveller_id'] ? self::find(self::travellers(), $booking['traveller_id']) : null;

		return [
			'id'              => $booking['id'],
			'booking_code'    => 'TKT-' . $booking['id'],
			'user_id'         => $booking['user_id'],
			'schedule_id'     => $booking['schedule_id'],
			'seat_number'     => $booking['seat_no'],
			'total_fare'      => $booking['fare'],
			'status'          => strtolower($booking['status']),
			'booked_at'       => $booking['booked_at'],
			'from_city'       => $trip['from_city'],
			'to_city'         => $trip['to_city'],
			'departure_date'  => $trip['departure_date'],
			'departure_time'  => $trip['departure_time'],
			'bus_name'        => $trip['bus_name'],
			'bus_type'        => $trip['bus_type'],
			'traveller_name'  => $traveller ? $traveller['name'] : null,
			'traveller_phone' => $traveller ? $traveller['phone'] : null,
			'refund_status'       => null,
			'refund_amount'       => null,
			'refund_deduction'    => null,
			'refund_method'       => null,
			'refund_processed_at' => null,
		];
	}
}
