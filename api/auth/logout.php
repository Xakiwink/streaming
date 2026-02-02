<?php
/**
 * User Logout API
 * POST /api/auth/logout.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Check if user is logged in
if (!is_logged_in()) {
    send_error_response('Not logged in', HTTP_UNAUTHORIZED);
}

$user_id = get_current_user_id();
$username = get_logged_in_user()['username'] ?? 'unknown';

// Log activity
log_activity('user_logout', $user_id, "User logged out: $username", $_SERVER['REMOTE_ADDR'] ?? null);

// Logout user
logout_user();

// Return success response
send_success_response(null, 'Logout successful', HTTP_OK);

