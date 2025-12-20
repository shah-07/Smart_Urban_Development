<?php
// api/parking/chart.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once '../../config.php';

try {
    // Group reservations by Date and Sum the Amount
    $sql = "SELECT DATE(startTime) as date, SUM(amount) as total_revenue, COUNT(*) as total_bookings
            FROM Reservation_T 
            WHERE startTime IS NOT NULL
            GROUP BY DATE(startTime) 
            ORDER BY date ASC 
            LIMIT 30";
            
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>