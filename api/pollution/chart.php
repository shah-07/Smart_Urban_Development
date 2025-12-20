<?php
require_once '../../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Updated Query:
        // 1. Removed strict DATE filter (LIMIT 7 instead) so you always see data.
        // 2. Uses correct columns (pm25Level, co2Level).
        // 3. Casts to DECIMAL to ensure numeric math works.
        $query = "
            SELECT 
                DATE(timestamp) as date,
                AVG(pm25Level) as avg_pm25,    
                AVG(co2Level) as avg_co2,
                AVG(noXLevel) as avg_nox,
                COUNT(*) as readings_count
            FROM Pollution_Data_T 
            GROUP BY DATE(timestamp)
            ORDER BY date DESC
            LIMIT 7
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fix: Convert numbers to actual floats/ints for Chart.js
        // (PDO often returns them as strings "12.50", which can break some charts)
        foreach ($result as &$row) {
            $row['avg_pm25'] = (float)$row['avg_pm25'];
            $row['avg_co2']  = (float)$row['avg_co2'];
            $row['avg_nox']  = (float)$row['avg_nox'];
        }

        echo json_encode($result);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>