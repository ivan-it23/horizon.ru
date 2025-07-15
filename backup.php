<?php
session_start();
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
// Для сайта на Beget
$dbHost = DB_HOST;
$dbUser = DB_USERNAME;
$dbPass = DB_PASSWORD;
$dbName = DB_NAME;
// Для удалённого доступа используйте:
// $dbHost = DB_REMOTE_HOST;
// $dbUser = DB_REMOTE_USER;
// $dbPass = DB_REMOTE_PASS;
// $dbName = DB_NAME;

// Формирование имени файла резервной копии с отметкой времени
$backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

// Команда для создания резервной копии с помощью mysqldump
$command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$backupFile}";
system($command, $returnVar);

if ($returnVar === 0 && file_exists($backupFile)) {
    // Отправка заголовков для скачивания файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
    header('Content-Length: ' . filesize($backupFile));
    flush();
    readfile($backupFile);
    // Удаляем файл после скачивания
    unlink($backupFile);
    exit;
} else {
    echo "Ошибка создания резервной копии базы данных.";
}
?>
