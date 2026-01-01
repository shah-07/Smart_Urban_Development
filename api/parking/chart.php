<?php
// api/parking/chart.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once '../../config.php';

try {
    // Group reservations by Week and Sum the Amount
    $sql = "SELECT 
                YEARWEEK(startTime) as week_number,
                CONCAT('Week ', WEEK(startTime), ' - ', DATE_FORMAT(MIN(startTime), '%b %d')) as week_label,
                SUM(amount) as total_revenue,
                COUNT(*) as total_bookings
            FROM Reservation_T 
            WHERE startTime IS NOT NULL
            GROUP BY YEARWEEK(startTime)
            ORDER BY week_number DESC
            LIMIT 12"; // Last 12 weeks
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Reverse to show chronological order (oldest to newest)
    echo json_encode(array_reverse($data));
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>