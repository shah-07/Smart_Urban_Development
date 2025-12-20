<?php
// api/energy/chart.php
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Group consumption by Date
    $sql = "SELECT DATE(timestamp) as date, SUM(totalConsumedUnit) as total 
            FROM Energy_Consumption_Data_T 
            GROUP BY DATE(timestamp) 
            ORDER BY date ASC LIMIT 10";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>