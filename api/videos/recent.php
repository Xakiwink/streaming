<?php
/**
 * Recent Videos API (new endpoint – does not replace list.php)
 * GET /api/videos/recent.php?limit=10
 *
 * Returns the latest N videos. Same response shape as list.php for app compatibility.
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

try {
    require_auth();

    $limit = max(1, min(100, (int)get_get('limit', 20)));

    $conn = get_db_connection();
    if (!$conn) {
        log_error('Database connection failed in videos/recent.php', ['error' => mysqli_connect_error() ?: 'Unknown']);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $query = "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name,
              v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
              v.created_at, v.updated_at
              FROM videos v
              LEFT JOIN categories c ON v.category_id = c.id
              LEFT JOIN users u ON v.uploaded_by = u.id
              ORDER BY v.created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        log_error('Failed to prepare statement in videos/recent.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $videos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $videos[] = $row;
    }
    mysqli_stmt_close($stmt);

    $count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM videos");
    $total = (int)mysqli_fetch_assoc($count_result)['total'];

    ob_clean();
    send_success_response([
        'videos' => $videos,
        'pagination' => [
            'page' => 1,
            'limit' => $limit,
            'total' => $total,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 0
        ]
    ], 'Recent videos retrieved successfully', HTTP_OK);

} catch (Exception $e) {
    log_error('Unexpected error in videos/recent.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('An unexpected error occurred.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/recent.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('A system error occurred.', HTTP_INTERNAL_ERROR);
}
