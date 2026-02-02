<?php
/**
 * Get Single Video API
 * GET /api/videos/get.php?id={video_id}
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Wrap in try-catch to handle any unexpected errors
try {
    // Require authentication
    require_auth();

    // Get video ID
    $video_id = get_get('id', null);

    if ($video_id === null || !is_numeric($video_id)) {
        ob_clean();
        send_error_response('Video ID is required', HTTP_BAD_REQUEST);
    }

    $video_id = (int)$video_id;

    // Get database connection
    $conn = get_db_connection();
    if (!$conn) {
        $error_msg = mysqli_connect_error() ?: 'Unknown database error';
        log_error('Database connection failed in videos/get.php', ['error' => $error_msg]);
        ob_clean();
        send_error_response('Database connection failed: ' . $error_msg, HTTP_INTERNAL_ERROR);
    }

    // Get video
    $stmt = mysqli_prepare($conn, 
        "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name,
         v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
         v.created_at, v.updated_at
         FROM videos v
         LEFT JOIN categories c ON v.category_id = c.id
         LEFT JOIN users u ON v.uploaded_by = u.id
         WHERE v.id = ?"
    );

    if (!$stmt) {
        log_error('Failed to prepare statement in videos/get.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    mysqli_stmt_bind_param($stmt, "i", $video_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $video = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$video) {
        ob_clean();
        send_error_response('Video not found', HTTP_NOT_FOUND);
    }

    // Log activity
    log_activity('video_viewed', get_current_user_id(), "Viewed video: {$video['title']}", $_SERVER['REMOTE_ADDR'] ?? null);

    // Clear output buffer before sending response
    ob_clean();

    // Return success response
    send_success_response($video, 'Video retrieved successfully', HTTP_OK);
    
} catch (Exception $e) {
    log_error('Unexpected error in videos/get.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('An unexpected error occurred. Please try again later.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/get.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('A system error occurred. Please contact support.', HTTP_INTERNAL_ERROR);
}

