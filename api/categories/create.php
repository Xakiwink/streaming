<?php
/**
 * Create Category API
 * POST /api/categories/create.php
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
$name = isset($input['name']) ? sanitize_string($input['name']) : '';
$description = isset($input['description']) ? sanitize_string($input['description']) : null;

// Validate category name
$name_validation = validate_category_name($name);
if (!$name_validation['valid']) {
    send_error_response($name_validation['error'], HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in categories/create.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Check if category name already exists
$stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE name = ?");
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    mysqli_stmt_close($stmt);
    send_error_response('Category name already exists', HTTP_CONFLICT);
}
mysqli_stmt_close($stmt);

// Insert category
$stmt = mysqli_prepare($conn, 
    "INSERT INTO categories (name, description) VALUES (?, ?)"
);

if (!$stmt) {
    log_error('Failed to prepare statement in categories/create.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "ss", $name, $description);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in categories/create.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

$category_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Log activity
log_activity('category_created', get_current_user_id(), "Created category: $name", $_SERVER['REMOTE_ADDR'] ?? null);

// Get created category
$stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Return success response
send_success_response($category, 'Category created successfully', HTTP_CREATED);

