<?php

/**
 * Adds a new task to the task list
 * 
 * @param string $task_name The name of the task to add.
 * @return bool True on success, false on failure.
 */
function addTask( string $task_name ): bool {
	$file  = __DIR__ . '/tasks.txt';
	$tasks = _read_json_file($file, []);
	foreach ($tasks as $task) {
		if (strcasecmp($task['name'], $task_name) === 0) {
			return false;
		}
	}
	$task = [
		'id' => uniqid('task_', true),
		'name' => $task_name,
		'completed' => false
	];
	$tasks[] = $task;
	return _write_json_file($file, $tasks) !== false;
}

/**
 * Retrieves all tasks from the tasks.txt file
 * 
 * @return array Array of tasks. -- Format [ id, name, completed ]
 */
function getAllTasks(): array {
	$file = __DIR__ . '/tasks.txt';
	return _read_json_file($file, []);
}

/**
 * Marks a task as completed or uncompleted
 * 
 * @param string  $task_id The ID of the task to mark.
 * @param bool $is_completed True to mark as completed, false to mark as uncompleted.
 * @return bool True on success, false on failure
 */
function markTaskAsCompleted( string $task_id, bool $is_completed ): bool {
	$file  = __DIR__ . '/tasks.txt';
	$tasks = _read_json_file($file, []);
	$found = false;
	foreach ($tasks as &$task) {
		if ($task['id'] === $task_id) {
			$task['completed'] = $is_completed;
			$found = true;
			break;
		}
	}
	if ($found) {
		return _write_json_file($file, $tasks) !== false;
	}
	return false;
}

/**
 * Deletes a task from the task list
 * 
 * @param string $task_id The ID of the task to delete.
 * @return bool True on success, false on failure.
 */
function deleteTask( string $task_id ): bool {
	$file  = __DIR__ . '/tasks.txt';
	$tasks = _read_json_file($file, []);
	$new_tasks = [];
	$found = false;
	foreach ($tasks as $task) {
		if ($task['id'] === $task_id) {
			$found = true;
			continue;
		}
		$new_tasks[] = $task;
	}
	if ($found) {
		return _write_json_file($file, $new_tasks) !== false;
	}
	return false;
}

/**
 * Generates a 6-digit verification code
 * 
 * @return string The generated verification code.
 */
function generateVerificationCode(): string {
	return str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
}

/**
 * Subscribe an email address to task notifications.
 *
 * Generates a verification code, stores the pending subscription,
 * and sends a verification email to the subscriber.
 *
 * @param string $email The email address to subscribe.
 * @return bool True if verification email sent successfully, false otherwise.
 */
function subscribeEmail( string $email ): bool {
	$pending_file = __DIR__ . '/pending_subscriptions.txt';
	$subscribers_file = __DIR__ . '/subscribers.txt';

	$subscribers = _read_json_file($subscribers_file, []);
	if (in_array($email, $subscribers)) {
		return false;
	}

	$pending = _read_json_file($pending_file, []);
	$code = generateVerificationCode();
	$pending[$email] = [
		'code' => $code,
		'timestamp' => time()
	];
	_write_json_file($pending_file, $pending);

	$link = getBaseUrl() . '/src/verify.php?email=' . urlencode($email) . '&code=' . urlencode($code);
	$subject = 'Verify subscription to Task Planner';
	$body = '<p>Click the link below to verify your subscription to Task Planner:</p>'
		. '<p><a id="verification-link" href="' . htmlspecialchars($link) . '">Verify Subscription</a></p>';
	$headers = "From: no-reply@example.com\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
	return mail($email, $subject, $body, $headers);
}

/**
 * Verifies an email subscription
 * 
 * @param string $email The email address to verify.
 * @param string $code The verification code.
 * @return bool True on success, false on failure.
 */
function verifySubscription( string $email, string $code ): bool {
	$pending_file     = __DIR__ . '/pending_subscriptions.txt';
	$subscribers_file = __DIR__ . '/subscribers.txt';

	$pending = _read_json_file($pending_file, []);
	if (!isset($pending[$email]) || $pending[$email]['code'] !== $code) {
		return false;
	}
	unset($pending[$email]);
	_write_json_file($pending_file, $pending);

	$subscribers = _read_json_file($subscribers_file, []);
	if (!in_array($email, $subscribers)) {
		$subscribers[] = $email;
		_write_json_file($subscribers_file, $subscribers);
	}
	return true;
}

/**
 * Unsubscribes an email from the subscribers list
 * 
 * @param string $email The email address to unsubscribe.
 * @return bool True on success, false on failure.
 */
function unsubscribeEmail( string $email ): bool {
	$subscribers_file = __DIR__ . '/subscribers.txt';
	$subscribers = _read_json_file($subscribers_file, []);
	$new_subscribers = [];
	$found = false;
	foreach ($subscribers as $sub) {
		if ($sub === $email) {
			$found = true;
			continue;
		}
		$new_subscribers[] = $sub;
	}
	if ($found) {
		return _write_json_file($subscribers_file, $new_subscribers) !== false;
	}
	return false;
}

/**
 * Sends task reminders to all subscribers
 * Internally calls  sendTaskEmail() for each subscriber
 */
function sendTaskReminders(): void {
	$subscribers_file = __DIR__ . '/subscribers.txt';
	$subscribers = _read_json_file($subscribers_file, []);
	$tasks = getAllTasks();
	$pending_tasks = array_filter($tasks, function($task) {
		return !$task['completed'];
	});
	foreach ($subscribers as $email) {
		sendTaskEmail($email, $pending_tasks);
	}
}

/**
 * Sends a task reminder email to a subscriber with pending tasks.
 *
 * @param string $email The email address of the subscriber.
 * @param array $pending_tasks Array of pending tasks to include in the email.
 * @return bool True if email was sent successfully, false otherwise.
 */
function sendTaskEmail( string $email, array $pending_tasks ): bool {
	$subject = 'Task Planner - Pending Tasks Reminder';
	$body = '<h2>Pending Tasks Reminder</h2>';
	$body .= '<p>Here are the current pending tasks:</p>';
	$body .= '<ul>';
	foreach ($pending_tasks as $task) {
		$body .= '<li>' . htmlspecialchars($task['name']) . '</li>';
	}
	$body .= '</ul>';
	$unsubscribe_link = getBaseUrl() . '/src/unsubscribe.php?email=' . urlencode($email);
	$body .= '<p><a id="unsubscribe-link" href="' . htmlspecialchars($unsubscribe_link) . '">Unsubscribe from notifications</a></p>';
	$headers = "From: no-reply@example.com\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
	return mail($email, $subject, $body, $headers);
}

function getBaseUrl(): string {
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$script = $_SERVER['SCRIPT_NAME'] ?? '';
	$base = dirname(dirname($script));
	return $protocol . '://' . $host . $base;
}

function _read_json_file($file, $default) {
    if (!file_exists($file) || filesize($file) === 0) {
        return $default;
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return $data === null ? $default : $data;
}

function _write_json_file($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
