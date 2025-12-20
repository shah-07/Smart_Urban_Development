<?php
// api/traffic/stats.php
error_reporting(0);
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Fetch timestamp and trafficFlow for the chart (Ordered by time ASC)
    $sql = "SELECT timestamp, trafficFlow FROM Traffic_Data_T ORDER BY timestamp ASC LIMIT 50";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>