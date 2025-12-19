<?php
require_once '../../config.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $query = "
            SELECT 
                DATE(timestamp) as date,
                AVG(CAST(pm25Level AS DECIMAL(10,2))) as avg_pm25,
                AVG(CAST(co2Level AS DECIMAL(10,2))) as avg_co2,
                AVG(CAST(noXLevel AS DECIMAL(10,2))) as avg_nox,
                COUNT(*) as readings_count
            FROM Pollution_Data_T 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>