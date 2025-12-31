<?php
// api/traffic/stats.php
error_reporting(0);
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Fetch weekly average traffic data
    $sql = "SELECT 
                DATE_FORMAT(MIN(timestamp), '%d-%m') as week_start,
                DATE_FORMAT(MAX(timestamp), '%d-%m') as week_end,
                CONCAT(DATE_FORMAT(MIN(timestamp), '%d-%m'), ' to ', DATE_FORMAT(MAX(timestamp), '%d-%m')) as week_range,
                AVG(trafficFlow) as avg_traffic,
                WEEK(timestamp) as week_number
            FROM Traffic_Data_T 
            GROUP BY WEEK(timestamp), YEAR(timestamp)
            ORDER BY timestamp ASC
            LIMIT 10";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>