<?php
// api/emergency/stats.php
error_reporting(0);
header("Content-Type: application/json");
include_once '../../config.php';

try {
    // Chart 1: Incidents by Type
    $stmt1 = $pdo->query("SELECT incidentType as type, COUNT(*) as count FROM Incident_T GROUP BY incidentType");
    $byType = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Chart 2: Avg Response Time
    $byResponse = [];
    $check = $pdo->query("SHOW TABLES LIKE 'Response_Times_T'");
    if($check->rowCount() > 0) {
        $sql2 = "SELECT V.unitType, 
                 AVG(TIMESTAMPDIFF(MINUTE, R.dispatchTime, R.arrivalTime)) as avgTime
                 FROM Response_Times_T R
                 JOIN Emergency_Vehicle_T V ON R.registrationNumber = V.registrationNumber
                 WHERE R.dispatchTime IS NOT NULL AND R.arrivalTime IS NOT NULL
                 GROUP BY V.unitType";
        $stmt2 = $pdo->query($sql2);
        $byResponse = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['byType' => $byType, 'byResponse' => $byResponse]);
} catch (PDOException $e) { echo json_encode(['error' => $e->getMessage()]); }
?>