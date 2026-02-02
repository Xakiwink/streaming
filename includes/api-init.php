<?php
/**
 * API bootstrap
 * Used at the top of every API endpoint: buffer, errors, JSON header
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
ob_clean();
