<?php
// api/energy/lists.php
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Get valid sensors to populate the dropdown
    $stmt = $pdo->query("SELECT sensorID, road FROM Iot_Sensor_T ORDER BY sensorID ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>