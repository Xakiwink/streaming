<?php
/**
 * List Users API (Admin Only)
 * GET /api/users/list.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require admin role
require_role(ROLE_ADMIN);

// Get query parameters
$page = max(1, (int)get_get('page', 1));
$limit = max(1, min(100, (int)get_get('limit', DEFAULT_PAGE_SIZE)));
$offset = ($page - 1) * $limit;
$role_filter = get_get('role', null);

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in users/list.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Build query
$query = "SELECT id, username, email, role, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if ($role_filter !== null && in_array($role_filter, [ROLE_ADMIN, ROLE_INSTRUCTOR, ROLE_STUDENT])) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Execute query
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    log_error('Failed to prepare statement in users/list.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

mysqli_stmt_close($stmt);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users";
if ($role_filter !== null) {
    $count_query .= " WHERE role = '" . db_escape($role_filter) . "'";
}
$count_result = mysqli_query($conn, $count_query);
$total = mysqli_fetch_assoc($count_result)['total'];

// Return success response
send_success_response([
    'users' => $users,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'pages' => ceil($total / $limit)
    ]
], 'Users retrieved successfully', HTTP_OK);

