<?php
/**
 * Thumbnail File Upload API
 * POST /api/videos/upload_thumbnail.php
 * Handles thumbnail image uploads; returns URL for use in create/update video.
 * No application-level size limits; server PHP limits apply.
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

try {
    require_role([ROLE_INSTRUCTOR, ROLE_ADMIN]);

    if (!isset($_FILES['thumbnail_file'])) {
        $content_length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        log_error('Thumbnail upload: no thumbnail_file in request', [
            'content_length' => $content_length,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? ''
        ]);
        ob_clean();
        $hint = $content_length > 0
            ? ' Request may exceed server limits. Fix: (1) In php.ini (e.g. /etc/php/7.4/fpm/php.ini) set upload_max_filesize=512M and post_max_size=520M, then restart php7.4-fpm. (2) If you use Nginx, add client_max_body_size 520M; in server/location and reload nginx.'
            : '';
        send_error_response('No file received. Select a thumbnail image and try again.' . $hint, HTTP_BAD_REQUEST);
    }

    $file = $_FILES['thumbnail_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Upload error. Try again.';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $msg = 'File is too large for the server.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $msg = 'File was only partially uploaded. Try again.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $msg = 'No file selected. Please choose an image.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $msg = 'Server error: missing temp folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $msg = 'Server error: could not save file.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $msg = 'Upload blocked. Use JPEG, PNG, GIF, or WebP.';
                break;
        }
        log_error('Thumbnail upload error', ['error' => $file['error'], 'message' => $msg]);
        ob_clean();
        send_error_response($msg, HTTP_BAD_REQUEST);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        ob_clean();
        send_error_response('Invalid file type. Allowed: JPEG, PNG, GIF, WebP', HTTP_BAD_REQUEST);
    }

    $upload_dir = __DIR__ . '/../../uploads/thumbnails/';
    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) {
            log_error('Failed to create thumbnail directory', ['dir' => $upload_dir]);
            ob_clean();
            send_error_response('Upload directory missing. Create uploads/thumbnails/ and run: chown -R www-data:www-data uploads && chmod -R 755 uploads', HTTP_INTERNAL_ERROR);
        }
    }
    if (!is_writable($upload_dir)) {
        log_error('Thumbnail directory not writable', ['dir' => $upload_dir]);
        ob_clean();
        send_error_response('Upload directory is not writable. From project root run: chown -R www-data:www-data uploads && chmod -R 755 uploads', HTTP_INTERNAL_ERROR);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    $filename = 'thumb_' . uniqid('', true) . '_' . time() . '.' . $ext;
    $path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        log_error('Failed to move uploaded thumbnail', ['tmp' => $file['tmp_name'], 'dest' => $path]);
        ob_clean();
        send_error_response('Failed to save uploaded file. Make uploads writable: chown -R www-data:www-data uploads && chmod -R 755 uploads', HTTP_INTERNAL_ERROR);
    }

    $url = '/streaming/uploads/thumbnails/' . $filename;
    ob_clean();
    send_success_response([
        'filename' => $filename,
        'url' => $url,
        'size' => (int) $file['size'],
        'type' => $mime_type
    ], 'Thumbnail uploaded successfully', HTTP_CREATED);

} catch (Exception $e) {
    log_error('Thumbnail upload exception', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    ob_clean();
    send_error_response('An unexpected error occurred. Please try again.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Thumbnail upload error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    ob_clean();
    send_error_response('A system error occurred. Please contact support.', HTTP_INTERNAL_ERROR);
}
