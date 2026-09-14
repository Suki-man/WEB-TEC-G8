<?php
class DBConnection {
	private $host = "localhost";
	private $username = "root";
	private $password = "";
	private $database = "bus_management";

	public function connect() {
		$conn = @new mysqli($this->host, $this->username, $this->password, $this->database);

		// throw instead of die() so Preview (models/PreviewT.php) can fall back to sample data
		if ($conn->connect_error) {
			throw new Exception("Connection failed: " . $conn->connect_error);
		}

		$conn->set_charset("utf8");
		return $conn;
	}
}