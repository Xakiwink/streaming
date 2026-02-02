<?php
/**
 * Get Single User API (Admin Only)
 * GET /api/users/get.php?id={user_id}
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require admin role
require_role(ROLE_ADMIN);

// Get user ID
$user_id = get_get('id', null);

if ($user_id === null || !is_numeric($user_id)) {
    send_error_response('User ID is required', HTTP_BAD_REQUEST);
}

$user_id = (int)$user_id;

// Get user
$user = get_user_by_id($user_id);

if (!$user) {
    send_error_response('User not found', HTTP_NOT_FOUND);
}

// Return success response
send_success_response($user, 'User retrieved successfully', HTTP_OK);

