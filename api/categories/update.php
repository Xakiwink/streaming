<?php
/**
 * Update Category API
 * PUT /api/categories/update.php
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
$category_id = isset($input['id']) ? (int)$input['id'] : null;

if ($category_id === null || $category_id <= 0) {
    send_error_response('Category ID is required', HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in categories/update.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Check if category exists
$stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$current_category) {
    send_error_response('Category not found', HTTP_NOT_FOUND);
}

// Get update fields
$name = isset($input['name']) ? sanitize_string($input['name']) : $current_category['name'];
$description = isset($input['description']) ? sanitize_string($input['description']) : $current_category['description'];

// Validate category name if changed
if ($name !== $current_category['name']) {
    $name_validation = validate_category_name($name);
    if (!$name_validation['valid']) {
        send_error_response($name_validation['error'], HTTP_BAD_REQUEST);
    }
    
    // Check if new name already exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE name = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        send_error_response('Category name already exists', HTTP_CONFLICT);
    }
    mysqli_stmt_close($stmt);
}

// Update category
$stmt = mysqli_prepare($conn, 
    "UPDATE categories SET name = ?, description = ? WHERE id = ?"
);

if (!$stmt) {
    log_error('Failed to prepare statement in categories/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $category_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in categories/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('category_updated', get_current_user_id(), "Updated category: $name", $_SERVER['REMOTE_ADDR'] ?? null);

// Get updated category
$stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Return success response
send_success_response($category, 'Category updated successfully', HTTP_OK);

