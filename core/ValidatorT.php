<?php
require_once __DIR__ . '/../models/PreviewT.php';

class Validator {
	private $errors = [];

	// Checks $data against $rules, for example ['email' => 'required|email'].
	// Returns true when every field passes.
	public function validate($data, $rules) {
		$this->errors = [];

		foreach ($rules as $field => $ruleString) {
			$value      = isset($data[$field]) ? trim((string) $data[$field]) : '';
			$fieldRules = explode('|', $ruleString);
			$label      = ucfirst(str_replace('_', ' ', $field));

			// an empty optional field is not checked any further
			if ($value === '' && !in_array('required', $fieldRules, true)) {
				continue;
			}

			foreach ($fieldRules as $rule) {
				$param = null;
				if (strpos($rule, ':') !== false) {
					[$rule, $param] = explode(':', $rule, 2);
				}

				$message = $this->check($rule, $param, $value, $data, $label);
				if ($message !== null) {
					$this->errors[$field][] = $message;
					break; // one message per field is enough
				}
			}
		}

		return empty($this->errors);
	}

	// ['field' => ['message'], ...]
	public function getErrors() {
		return $this->errors;
	}

	public function getFirstError() {
		foreach ($this->errors as $messages) {
			return $messages[0];
		}
		return '';
	}

	// Returns an error message, or null when the rule passes.
	private function check($rule, $param, $value, $data, $label) {
		switch ($rule) {
			case 'required':
				return $value === '' ? "$label is required." : null;
			case 'email':
				return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.";
			case 'min':
				return mb_strlen($value) >= (int) $param ? null : "$label must be at least $param characters.";
			case 'phone':
				return preg_match('/^01[0-9]{9}$/', $value) ? null : "Phone number must be 11 digits and start with 01.";
			case 'numeric':
				return is_numeric($value) ? null : "$label must be a number.";
			case 'matches':
				$other = isset($data[$param]) ? trim((string) $data[$param]) : '';
				return $value === $other ? null : "$label does not match.";
			case 'date':
				$d = DateTime::createFromFormat('Y-m-d', $value);
				return ($d && $d->format('Y-m-d') === $value) ? null : "$label must be a valid date.";
			case 'future_date':
				return $value >= date('Y-m-d') ? null : "$label cannot be in the past.";
			case 'unique_email':
				// unique_email:5 ignores the account with id 5 (used when editing)
				return $this->emailTaken($value, (int) $param) ? "This email address is already registered." : null;
			default:
				return null;
		}
	}

	// A readable message for a database error returned by the models.
	// $thing is what was being saved or deleted, e.g. "bus".
	public static function dbMessage($error, $thing) {
		if (stripos($error, 'Cannot add or update a child row') !== false) {
			return "the chosen bus or route does not exist.";
		}
		if (stripos($error, 'foreign key') !== false) {
			return "this $thing is still used by schedules or bookings. Remove those first, or set it to Inactive instead.";
		}
		if (stripos($error, 'Duplicate entry') !== false) {
			return "a $thing with the same number already exists.";
		}
		return $error;
	}

	private function emailTaken($email, $ignoreId) {
		$conn = Preview::connect();
		if (!$conn) {
			// preview mode: check the sample accounts
			foreach (Preview::users() as $user) {
				if (strcasecmp($user['email'], $email) === 0 && (int) $user['id'] !== $ignoreId) {
					return true;
				}
			}
			return false;
		}
		$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
		$stmt->bind_param("si", $email, $ignoreId);
		$stmt->execute();
		return $stmt->get_result()->num_rows > 0;
	}
}
