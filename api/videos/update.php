<?php
/**
 * Update Video API
 * PUT /api/videos/update.php
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

// Require authentication
require_auth();

// Get JSON input
$input = get_json_input();

// Validate required fields
$video_id = isset($input['id']) ? (int)$input['id'] : null;

if ($video_id === null || $video_id <= 0) {
    send_error_response('Video ID is required', HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in videos/update.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Check if video exists and get current data
$stmt = mysqli_prepare($conn, "SELECT * FROM videos WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $video_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_video = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$current_video) {
    send_error_response('Video not found', HTTP_NOT_FOUND);
}

// Check permissions: admin can update any video, instructor can only update their own
$user_id = get_current_user_id();
$user_role = get_current_user_role();

if ($user_role !== ROLE_ADMIN && $current_video['uploaded_by'] != $user_id) {
    send_error_response('You can only update your own videos', HTTP_FORBIDDEN);
}

// Get update fields
$title = isset($input['title']) ? sanitize_string($input['title']) : $current_video['title'];
$description = isset($input['description']) ? sanitize_string($input['description']) : $current_video['description'];
$category_id = isset($input['category_id']) ? (int)$input['category_id'] : $current_video['category_id'];
$video_url = isset($input['video_url']) ? sanitize_string($input['video_url']) : $current_video['video_url'];
$thumbnail_url = isset($input['thumbnail_url']) ? trim((string) $input['thumbnail_url']) : $current_video['thumbnail_url'];
if ($thumbnail_url === '') {
    $thumbnail_url = null;
}
if ($thumbnail_url !== null && strlen($thumbnail_url) > 500) {
    send_error_response('Thumbnail URL must be less than 500 characters', HTTP_BAD_REQUEST);
}

// Validate title if provided
if ($title !== $current_video['title']) {
    $title_validation = validate_video_title($title);
    if (!$title_validation['valid']) {
        send_error_response($title_validation['error'], HTTP_BAD_REQUEST);
    }
}

// Validate video URL if provided
if ($video_url !== $current_video['video_url']) {
    $url_validation = validate_video_url($video_url);
    if (!$url_validation['valid']) {
        send_error_response($url_validation['error'], HTTP_BAD_REQUEST);
    }
}

// Validate category if provided
if ($category_id !== null && $category_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        send_error_response('Category not found', HTTP_BAD_REQUEST);
    }
    mysqli_stmt_close($stmt);
}

// Update video
$stmt = mysqli_prepare($conn, 
    "UPDATE videos SET title = ?, description = ?, category_id = ?, video_url = ?, thumbnail_url = ? 
     WHERE id = ?"
);

if (!$stmt) {
    log_error('Failed to prepare statement in videos/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "ssissi", $title, $description, $category_id, $video_url, $thumbnail_url, $video_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in videos/update.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('video_updated', $user_id, "Updated video: $title", $_SERVER['REMOTE_ADDR'] ?? null);

// Get updated video
$stmt = mysqli_prepare($conn, 
    "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name,
     v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
     v.created_at, v.updated_at
     FROM videos v
     LEFT JOIN categories c ON v.category_id = c.id
     LEFT JOIN users u ON v.uploaded_by = u.id
     WHERE v.id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $video_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$video = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Return success response
send_success_response($video, 'Video updated successfully', HTTP_OK);

