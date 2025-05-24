<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $fileId = $_GET['id'];

    $stmt = $conn->prepare("SELECT file_name, file_data FROM uploaded_files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($fileName, $fileData);
    $stmt->fetch();

    if ($fileData) {
        // Установка заголовков для скачивания файла
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($fileData));
        echo $fileData;
    } else {
        echo "Файл не найден.";
    }
    exit;
} else {
    echo "Идентификатор файла не указан.";
}
?>
