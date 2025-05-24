<?php
include 'db_connection.php';

$serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
$case_number = mysqli_real_escape_string($conn, $_POST['case_number']);

$sql = "SELECT *, test_result FROM testing_data
        WHERE serial_number = '$serial_number'
        AND case_number = '$case_number'
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$data = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>