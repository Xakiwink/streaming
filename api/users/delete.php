<?php
/**
 * Delete User API (Admin Only)
 * DELETE /api/users/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require admin role
require_role(ROLE_ADMIN);

// Get JSON input (some clients send DELETE with body)
$input = get_json_input();

// Get user ID from input or query string
$user_id = isset($input['id']) ? (int)$input['id'] : (int)get_get('id', 0);

if ($user_id <= 0) {
    send_error_response('User ID is required', HTTP_BAD_REQUEST);
}

// Prevent admin from deleting themselves
$current_user_id = get_current_user_id();
if ($user_id == $current_user_id) {
    send_error_response('You cannot delete your own account', HTTP_BAD_REQUEST);
}

// Get user data before deletion
$user = get_user_by_id($user_id);

if (!$user) {
    send_error_response('User not found', HTTP_NOT_FOUND);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in users/delete.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Delete user (cascade will handle videos)
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");

if (!$stmt) {
    log_error('Failed to prepare statement in users/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in users/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('user_deleted', $current_user_id, "Admin deleted user: {$user['username']}", $_SERVER['REMOTE_ADDR'] ?? null);

// Return success response
send_success_response(null, 'User deleted successfully', HTTP_OK);

