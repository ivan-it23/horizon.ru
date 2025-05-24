<?php
include 'db_connection.php';

$deviceType = isset($_POST['device_type']) && !empty(trim($_POST['device_type'])) ? mysqli_real_escape_string($conn, trim($_POST['device_type'])) : null;
$nominalDiameter = isset($_POST['nominal_diameter']) && !empty(trim($_POST['nominal_diameter'])) ? mysqli_real_escape_string($conn, trim($_POST['nominal_diameter'])) : null;
$transmitterCount = isset($_POST['transmitter_count']) && !empty(trim($_POST['transmitter_count'])) ? mysqli_real_escape_string($conn, trim($_POST['transmitter_count'])) : null;
$deviceId = isset($_POST['device_id']) && !empty(trim($_POST['device_id'])) ? mysqli_real_escape_string($conn, trim($_POST['device_id'])) : null;

$sql = "SELECT * FROM device_identification WHERE 1=1";

if ($deviceId) {
    $sql .= " AND id = '$deviceId'";
} else {
    if ($deviceType) {
        $sql .= " AND device_type = '$deviceType'";
    }
    if ($nominalDiameter) {
        $sql .= " AND nominal_diameter = '$nominalDiameter'";
    }
    if ($transmitterCount) {
        $sql .= " AND transmitter_count = '$transmitterCount'";
    }
}

$result = $conn->query($sql);

$devices = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($devices);

$conn->close();
?>