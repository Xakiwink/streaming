<?php
/**
 * User Login API
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Get JSON input
$input = get_json_input();

// Validate required fields
$username = isset($input['username']) ? sanitize_string($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($username)) {
    send_error_response('Username is required', HTTP_BAD_REQUEST);
}

if (empty($password)) {
    send_error_response('Password is required', HTTP_BAD_REQUEST);
}

// Get user from database
$user = get_user_by_username($username);

if (!$user) {
    send_error_response('Invalid username or password', HTTP_UNAUTHORIZED);
}

// Verify password
if (!verify_password($username, $password, $user['password'])) {
    send_error_response('Invalid username or password', HTTP_UNAUTHORIZED);
}

// Start session and login user
login_user([
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role']
]);

// Log activity
log_activity('user_login', $user['id'], "User logged in: $username", $_SERVER['REMOTE_ADDR'] ?? null);

// Remove password from response
unset($user['password']);

// Return success response
send_success_response($user, 'Login successful', HTTP_OK);

