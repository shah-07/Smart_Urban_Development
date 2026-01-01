<?php
// api/parking/chart.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once '../../config.php';

try {
    // Test query to see what data we have
    $testSql = "SELECT 
                    reservationID, 
                    startTime, 
                    endTime, 
                    amount, 
                    status,
                    DATE_FORMAT(startTime, '%Y-%m-%d') as start_date
                FROM Reservation_T 
                WHERE status = 'Reserved'
                ORDER BY startTime DESC
                LIMIT 10";
    
    $testStmt = $pdo->query($testSql);
    $testData = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if we have any reserved data
    if (empty($testData)) {
        echo json_encode([
            'data' => [],
            'debug' => 'No reserved reservations found',
            'test_data' => $testData
        ]);
        exit();
    }
    
    // Weekly revenue query
    $sql = "SELECT 
                YEAR(startTime) as year,
                WEEK(startTime) as week_number,
                CONCAT('Week ', WEEK(startTime), ' (', 
                       DATE_FORMAT(MIN(startTime), '%b %d'),
                       ')') as week_label,
                SUM(amount) as total_revenue,
                COUNT(*) as total_bookings
            FROM Reservation_T 
            WHERE status = 'Reserved'
            GROUP BY YEAR(startTime), WEEK(startTime)
            ORDER BY year ASC, week_number ASC
            LIMIT 12";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'data' => $data,
        'debug' => [
            'test_sample' => $testData,
            'weekly_data_count' => count($data)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>