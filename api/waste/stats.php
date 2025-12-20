<?php
// api/waste/stats.php
header("Content-Type: application/json");
require 'db.php';

try {
    // Count number of Smart Bins assigned to each Waste Type
    $sql = "SELECT 
                wt.name as type, 
                COUNT(sb.binID) as count 
            FROM WasteType_T wt
            LEFT JOIN SmartBin_T sb ON wt.wasteTypeID = sb.wasteTypeID
            GROUP BY wt.wasteTypeID, wt.name";
            
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>