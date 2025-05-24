<?php
include 'db_connection.php';
// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем серийный номер и номер корпуса из POST-запроса
$serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : '';
$case_number = isset($_POST['case_number']) ? mysqli_real_escape_string($conn, $_POST['case_number']) : '';
// SQL-запрос для выборки истории по серийному номеру и номеру корпуса
$sql = "SELECT * FROM testing_data WHERE serial_number = '$serial_number' AND case_number = '$case_number' ORDER BY id DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo '<table class="data-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Дата</th>';
    echo '<th>Создатель</th>';
    echo '<th>Результат испытаний</th>';
    echo '<th>СКО шума приемника (400Гц)</th>';
    echo '<th>СКО шума приемника (2000Гц)</th>';
    echo '<th>МДС передатчика (400Гц)</th>';
    echo '<th>МДС передатчика (2000Гц)</th>';
    echo '<th>Вторая гармоника (400Гц)</th>';
    echo '<th>Вторая гармоника (2000Гц)</th>';
    echo '<th>Третья гармоника (400Гц)</th>';
    echo '<th>Третья гармоника (2000Гц)</th>';
    echo '<th>Негармонические компоненты (400Гц)</th>';
    echo '<th>Негармонические компоненты (2000Гц)</th>';
    echo '<th>Дрейф разности фаз (400Гц)</th>';
     echo '<th>Дрейф разности фаз (2000Гц)</th>';
     echo '<th>Температурные испытания</th>';
     echo '<th>Виброиспытания</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
         echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . $row['created_at'] . '</td>';
         echo '<td>' . $row['creator'] . '</td>';
         echo '<td>' . $row['test_result'] . '</td>';
         echo '<td>X1: ' . $row['noise_sko_x1_400'] . ', Y1: ' . $row['noise_sko_y1_400'] . ', Z1: ' . $row['noise_sko_z1_400'] . ', X2: ' . $row['noise_sko_x2_400'] . ', Y2: ' . $row['noise_sko_y2_400'] . ', Z2: ' . $row['noise_sko_z2_400'] . '</td>';
        echo '<td>X1: ' . $row['noise_sko_x1_2000'] . ', Y1: ' . $row['noise_sko_y1_2000'] . ', Z1: ' . $row['noise_sko_z1_2000'] . ', X2: ' . $row['noise_sko_x2_2000'] . ', Y2: ' . $row['noise_sko_y2_2000'] . ', Z2: ' . $row['noise_sko_z2_2000'] . '</td>';
        echo '<td>1: ' . $row['mds_transmitter_1_400'] . ', 2: ' . $row['mds_transmitter_2_400'] . ', 3: ' . $row['mds_transmitter_3_400'] . ', 4: ' . $row['mds_transmitter_4_400'] . ', 5: ' . $row['mds_transmitter_5_400'] . '</td>';
        echo '<td>1: ' . $row['mds_transmitter_1_2000'] . ', 2: ' . $row['mds_transmitter_2_2000'] . ', 3: ' . $row['mds_transmitter_3_2000'] . ', 4: ' . $row['mds_transmitter_4_2000'] . ', 5: ' . $row['mds_transmitter_5_2000'] . '</td>';
        echo '<td>1: ' . $row['second_harmonic_1_400'] . ', 2: ' . $row['second_harmonic_2_400'] . ', 3: ' . $row['second_harmonic_3_400'] . ', 4: ' . $row['second_harmonic_4_400'] . ', 5: ' . $row['second_harmonic_5_400'] . '</td>';
         echo '<td>1: ' . $row['second_harmonic_1_2000'] . ', 2: ' . $row['second_harmonic_2_2000'] . ', 3: ' . $row['second_harmonic_3_2000'] . ', 4: ' . $row['second_harmonic_4_2000'] . ', 5: ' . $row['second_harmonic_5_2000'] . '</td>';
        echo '<td>1: ' . $row['third_harmonic_1_400'] . ', 2: ' . $row['third_harmonic_2_400'] . ', 3: ' . $row['third_harmonic_3_400'] . ', 4: ' . $row['third_harmonic_4_400'] . ', 5: ' . $row['third_harmonic_5_400'] . '</td>';
        echo '<td>1: ' . $row['third_harmonic_1_2000'] . ', 2: ' . $row['third_harmonic_2_2000'] . ', 3: ' . $row['third_harmonic_3_2000'] . ', 4: ' . $row['third_harmonic_4_2000'] . ', 5: ' . $row['third_harmonic_5_2000'] . '</td>';
        echo '<td>1: ' . $row['non_harmonic_components_1_400'] . ', 2: ' . $row['non_harmonic_components_2_400'] . ', 3: ' . $row['non_harmonic_components_3_400'] . ', 4: ' . $row['non_harmonic_components_4_400'] . ', 5: ' . $row['non_harmonic_components_5_400'] . '</td>';
        echo '<td>1: ' . $row['non_harmonic_components_1_2000'] . ', 2: ' . $row['non_harmonic_components_2_2000'] . ', 3: ' . $row['non_harmonic_components_3_2000'] . ', 4: ' . $row['non_harmonic_components_4_2000'] . ', 5: ' . $row['non_harmonic_components_5_2000'] . '</td>';
        echo '<td>1: ' . $row['phase_difference_drift_1_400'] . ', 2: ' . $row['phase_difference_drift_2_400'] . ', 3: ' . $row['phase_difference_drift_3_400'] . ', 4: ' . $row['phase_difference_drift_4_400'] . ', 5: ' . $row['phase_difference_drift_5_400'] . '</td>';
        echo '<td>1: ' . $row['phase_difference_drift_1_2000'] . ', 2: ' . $row['phase_difference_drift_2_2000'] . ', 3: ' . $row['phase_difference_drift_3_2000'] . ', 4: ' . $row['phase_difference_drift_4_2000'] . ', 5: ' . $row['phase_difference_drift_5_2000'] . '</td>';
        echo '<td>' . $row['temperature_test'] . '</td>';
        echo '<td>' . $row['vibration_test'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo "<p>Нет записей для данного прибора.</p>";
}
$conn->close();
?>