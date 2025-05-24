<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['thermoprofile', 'metrology', 'calibration', 'kis'];

    $serialNumber = $_POST['serial_number'];
    $caseNumber = $_POST['case_number'];
    $deviceType = $_POST['device_type'];

    $uploadedField = null;
    foreach ($fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $fileName = $_FILES[$field]['name'];
            $fileTmpPath = $_FILES[$field]['tmp_name'];
            $fileData = file_get_contents($fileTmpPath);

            // Проверка на размер файла (пример: ограничение в 10MB)
            if ($_FILES[$field]['size'] > 10 * 1024 * 1024) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка: Размер файла превышает 10MB.'
                ]);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO uploaded_files
                (field_name, file_name, file_data, serial_number, case_number, device_type, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("ssssss", $field, $fileName, $fileData, $serialNumber, $caseNumber, $deviceType);
            if ($stmt->execute()) {
                $uploadedField = $field;
            }
            break; // Загружаем только один файл за раз
        }
    }

    if ($uploadedField) {
        echo json_encode([
            'success' => true,
            'message' => 'Файл успешно загружен!',
            'field' => $uploadedField // Возвращаем поле для перенаправления
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при загрузке файла.  Не удалось загрузить файл.',
        ]);
    }
    exit;
}
?>