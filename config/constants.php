<?php
/**
 * Application constants
 * Roles, HTTP codes, messages, session, upload, pagination, logs
 */

// ---------------------------------------------------------------------------
// Roles
// ---------------------------------------------------------------------------

define('ROLE_ADMIN', 'admin');
define('ROLE_INSTRUCTOR', 'instructor');
define('ROLE_STUDENT', 'student');

// ---------------------------------------------------------------------------
// HTTP status codes
// ---------------------------------------------------------------------------

define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_CONFLICT', 409);
define('HTTP_INTERNAL_ERROR', 500);

// ---------------------------------------------------------------------------
// Response messages
// ---------------------------------------------------------------------------

define('MSG_SUCCESS', 'Success');
define('MSG_ERROR', 'Error');
define('MSG_UNAUTHORIZED', 'Unauthorized access');
define('MSG_FORBIDDEN', 'Access forbidden');
define('MSG_NOT_FOUND', 'Resource not found');
define('MSG_INVALID_INPUT', 'Invalid input data');
define('MSG_DATABASE_ERROR', 'Database error occurred');

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------

define('SESSION_LIFETIME', 3600); // 1 hour

// ---------------------------------------------------------------------------
// File upload (allowed MIME types; no app-level size limits)
// ---------------------------------------------------------------------------

define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

define('DEFAULT_PAGE_SIZE', 20);

// ---------------------------------------------------------------------------
// Log file paths
// ---------------------------------------------------------------------------

define('LOG_ERROR', __DIR__ . '/../logs/error.log');
define('LOG_ACCESS', __DIR__ . '/../logs/access.log');
define('LOG_ACTIVITY', __DIR__ . '/../logs/activity.log');
