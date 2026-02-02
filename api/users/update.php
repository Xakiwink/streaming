<?php
/**
 * Update User API (Admin Only)
 * PUT /api/users/update.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow PUT method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require admin role
require_role(ROLE_ADMIN);

// Get JSON input
$input = get_json_input();

// Validate required fields
$user_id = isset($input['id']) ? (int)$input['id'] : null;

if ($user_id === null || $user_id <= 0) {
    send_error_response('User ID is required', HTTP_BAD_REQUEST);
}

// Get current user data
$current_user = get_user_by_id($user_id);

if (!$current_user) {
    send_error_response('User not found', HTTP_NOT_FOUND);
}

// Get update fields
$username = isset($input['username']) ? sanitize_string($input['username']) : $current_user['username'];
$email = isset($input['email']) ? sanitize_string($input['email']) : $current_user['email'];
$role = isset($input['role']) ? sanitize_string($input['role']) : $current_user['role'];
$password = isset($input['password']) ? $input['password'] : null;

// Validate username if changed
if ($username !== $current_user['username']) {
    $username_validation = validate_username($username);
    if (!$username_validation['valid']) {
        send_error_response($username_validation['error'], HTTP_BAD_REQUEST);
    }
    
    if (username_exists($username, $user_id)) {
        send_error_response('Username already exists', HTTP_CONFLICT);
    }
}

// Validate email if changed
if ($email !== $current_user['email']) {
    $email_validation = validate_email($email);
    if (!$email_validation['valid']) {
        send_error_response($email_validation['error'], HTTP_BAD_REQUEST);
    }
    
    if (email_exists($email, $user_id)) {
        send_error_response('Email already exists', HTTP_CONFLICT);
    }
}

// Validate role
$role_validation = validate_role($role);
if (!$role_validation['valid']) {
    send_error_response($role_validation['error'], HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in users/update.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Update user
if ($password !== null && !empty($password)) {
    // Update with password
    $password_validation = validate_password($password);
    if (!$password_validation['valid']) {
        send_error_response($password_validation['error'], HTTP_BAD_REQUEST);
    }
    
    $hashed_password = hash_password($username, $password);
    $stmt = mysqli_prepare($conn, 
        "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssi", $username, $email, $hashed_password, $role, $user_id);
} else {
    // Update without password
    $stmt = mysqli_prepare($conn, 
        "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $role, $user_id);
}

if (!$stmt) {
    log_error('Failed to prepare statement in users/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in users/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('user_updated', get_current_user_id(), "Admin updated user: $username", $_SERVER['REMOTE_ADDR'] ?? null);

// Get updated user
$user = get_user_by_id($user_id);

// Return success response
send_success_response($user, 'User updated successfully', HTTP_OK);

