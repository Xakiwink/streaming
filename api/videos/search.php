<?php
/**
 * Search Videos API (new endpoint – does not replace list.php)
 * GET /api/videos/search.php?q=term&page=1&limit=20
 *
 * Searches videos by title and description. Same response shape as list.php.
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

    $q = trim((string)get_get('q', ''));
    $page = max(1, (int)get_get('page', 1));
    $limit = max(1, min(100, (int)get_get('limit', DEFAULT_PAGE_SIZE)));
    $offset = ($page - 1) * $limit;

    $conn = get_db_connection();
    if (!$conn) {
        log_error('Database connection failed in videos/search.php', ['error' => mysqli_connect_error() ?: 'Unknown']);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $videos = [];
    $total = 0;

    if ($q === '') {
        ob_clean();
        send_success_response([
            'videos' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ], 'Search completed', HTTP_OK);
        exit;
    }

    $search_term = '%' . $q . '%';

    $query = "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name,
              v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
              v.created_at, v.updated_at
              FROM videos v
              LEFT JOIN categories c ON v.category_id = c.id
              LEFT JOIN users u ON v.uploaded_by = u.id
              WHERE v.title LIKE ? OR v.description LIKE ?
              ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        log_error('Failed to prepare statement in videos/search.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }
    mysqli_stmt_bind_param($stmt, 'ssii', $search_term, $search_term, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $videos[] = $row;
    }
    mysqli_stmt_close($stmt);

    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM videos WHERE title LIKE ? OR description LIKE ?");
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, 'ss', $search_term, $search_term);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total = (int)mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);
    }

    ob_clean();
    send_success_response([
        'videos' => $videos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $limit > 0 ? (int)ceil($total / $limit) : 0
        ]
    ], 'Search completed', HTTP_OK);

} catch (Exception $e) {
    log_error('Unexpected error in videos/search.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('An unexpected error occurred.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/search.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('A system error occurred.', HTTP_INTERNAL_ERROR);
}
