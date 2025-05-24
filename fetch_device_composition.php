<?php
// fetch_device_composition.php
include 'db_connection.php';
header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['device_id'])) {
    $device_id = intval($_POST['device_id']);
    $query = "SELECT * FROM device_composition WHERE device_id = $device_id ORDER BY id ASC";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo json_encode(['error' => 'Ошибка запроса: ' . mysqli_error($conn)]);
        exit;
    }

    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Не указан идентификатор прибора']);
}
?>