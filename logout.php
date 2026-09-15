<?php
/**
 * logout.php
 * -----------------------------------------------------
 * Menghancurkan session dan mengarahkan ke halaman login.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
