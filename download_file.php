<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user'])) {
    die("Доступ запрещен. Пожалуйста, авторизуйтесь.");
}

if (isset($_GET['id'])) {
    $fileId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    $stmt = $conn->prepare("SELECT file_name, file_data FROM uploaded_files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($fileName, $fileData);
        $stmt->fetch();

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\"");
        header("Content-Length: " . strlen($fileData));

        echo $fileData;
        exit;
    } else {
        die("Файл не найден.");
    }
    $stmt->close();
} else {
    die("Неверный запрос.");
}
?>
