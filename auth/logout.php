<?php
require_once __DIR__ . "/../includes/funcoes.php";
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
header("Location: " . url("auth/login.php"));
exit;
