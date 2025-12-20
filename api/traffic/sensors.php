<?php
// api/traffic/sensors.php
error_reporting(0);
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Fetch sensors. We filter by 'Camera' or 'Induction Loop' or 'Radar' if available.
    // If you just want all sensors, remove the WHERE clause.
    $sql = "SELECT sensorID, location, type FROM Iot_Sensor_T";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>