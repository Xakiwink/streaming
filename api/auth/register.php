<?php
/**
 * User Registration API
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Wrap in try-catch to handle any unexpected errors
try {
    // Get JSON input
    $input = get_json_input();

    // Validate required fields
    $username = isset($input['username']) ? sanitize_string($input['username']) : '';
    $email = isset($input['email']) ? sanitize_string($input['email']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $role = isset($input['role']) ? sanitize_string($input['role']) : ROLE_STUDENT;

    // Validate username
    $username_validation = validate_username($username);
    if (!$username_validation['valid']) {
        ob_clean();
        send_error_response($username_validation['error'], HTTP_BAD_REQUEST);
    }

    // Validate email
    $email_validation = validate_email($email);
    if (!$email_validation['valid']) {
        ob_clean();
        send_error_response($email_validation['error'], HTTP_BAD_REQUEST);
    }

    // Validate password
    $password_validation = validate_password($password);
    if (!$password_validation['valid']) {
        ob_clean();
        send_error_response($password_validation['error'], HTTP_BAD_REQUEST);
    }

    // Validate role
    $role_validation = validate_role($role);
    if (!$role_validation['valid']) {
        ob_clean();
        send_error_response($role_validation['error'], HTTP_BAD_REQUEST);
    }

    // Check if username already exists
    if (username_exists($username)) {
        ob_clean();
        send_error_response('Username already exists', HTTP_CONFLICT);
    }

    // Check if email already exists
    if (email_exists($email)) {
        ob_clean();
        send_error_response('Email already exists', HTTP_CONFLICT);
    }

    // Get database connection
    $conn = get_db_connection();
    if (!$conn) {
        $error_msg = mysqli_connect_error() ?: 'Unknown database error';
        log_error('Database connection failed in register.php', ['error' => $error_msg]);
        ob_clean(); // Clear any output
        send_error_response('Database connection failed: ' . $error_msg, HTTP_INTERNAL_ERROR);
    }

    // Hash password using SHA1(username . password)
    $hashed_password = hash_password($username, $password);

    // Insert user into database
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        log_error('Failed to prepare statement in register.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        log_error('Failed to execute statement in register.php', ['error' => $error]);
        ob_clean(); // Clear any output
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Log activity
    log_activity('user_registered', $user_id, "User registered: $username", $_SERVER['REMOTE_ADDR'] ?? null);

    // Get created user data (without password)
    $user = get_user_by_id($user_id);

    if (!$user) {
        log_error('Failed to retrieve created user in register.php', ['user_id' => $user_id]);
        ob_clean(); // Clear any output
        send_error_response('User created but failed to retrieve user data', HTTP_INTERNAL_ERROR);
    }

    // Clear output buffer before sending response
    ob_clean();

    // Return success response
    send_success_response($user, 'User registered successfully', HTTP_CREATED);
    
} catch (Exception $e) {
    // Catch any unexpected errors
    log_error('Unexpected error in register.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    ob_clean();
    send_error_response('An unexpected error occurred. Please try again later.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    // Catch fatal errors
    log_error('Fatal error in register.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('A system error occurred. Please contact support.', HTTP_INTERNAL_ERROR);
}

