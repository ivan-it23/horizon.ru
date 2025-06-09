<?php
// Файл: fetch_user_role.php (НОВЫЙ)
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user']['is_admin'])) {
    echo json_encode(['is_admin' => (bool)$_SESSION['user']['is_admin']]);
} else {
    echo json_encode(['is_admin' => false]);
}
?>