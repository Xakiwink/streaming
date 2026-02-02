<?php
/**
 * Create User API (Admin Only)
 * POST /api/users/create.php
 */

header('Content-Type: application/json');
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

// Require admin role
require_role(ROLE_ADMIN);

// Get JSON input
$input = get_json_input();

// Validate required fields
$username = isset($input['username']) ? sanitize_string($input['username']) : '';
$email = isset($input['email']) ? sanitize_string($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$role = isset($input['role']) ? sanitize_string($input['role']) : ROLE_STUDENT;

// Validate username
$username_validation = validate_username($username);
if (!$username_validation['valid']) {
    send_error_response($username_validation['error'], HTTP_BAD_REQUEST);
}

// Validate email
$email_validation = validate_email($email);
if (!$email_validation['valid']) {
    send_error_response($email_validation['error'], HTTP_BAD_REQUEST);
}

// Validate password
$password_validation = validate_password($password);
if (!$password_validation['valid']) {
    send_error_response($password_validation['error'], HTTP_BAD_REQUEST);
}

// Validate role
$role_validation = validate_role($role);
if (!$role_validation['valid']) {
    send_error_response($role_validation['error'], HTTP_BAD_REQUEST);
}

// Check if username already exists
if (username_exists($username)) {
    send_error_response('Username already exists', HTTP_CONFLICT);
}

// Check if email already exists
if (email_exists($email)) {
    send_error_response('Email already exists', HTTP_CONFLICT);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in users/create.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Hash password using SHA1(username . password)
$hashed_password = hash_password($username, $password);

// Insert user into database
$stmt = mysqli_prepare($conn, 
    "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    log_error('Failed to prepare statement in users/create.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in users/create.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

$new_user_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Log activity
log_activity('user_created', get_current_user_id(), "Admin created user: $username", $_SERVER['REMOTE_ADDR'] ?? null);

// Get created user data (without password)
$user = get_user_by_id($new_user_id);

// Return success response
send_success_response($user, 'User created successfully', HTTP_CREATED);

