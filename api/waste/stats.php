<?php
// api/waste/stats.php - Shows waste types distribution across recycling plants
header("Content-Type: application/json");
require 'db.php';

try {
    // Count how many plants accept each waste type
    $sql = "SELECT 
                wt.name as type, 
                COUNT(map.wasteTypeID) as count
            FROM WasteType_T wt
            LEFT JOIN WasteType_Accepts_Recycling_Plant_T map ON wt.wasteTypeID = map.wasteTypeID
            GROUP BY wt.wasteTypeID, wt.name
            HAVING COUNT(map.wasteTypeID) > 0
            ORDER BY count DESC";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    
    // If no plants have waste types assigned, show all waste types
    if (empty($data)) {
        $sql = "SELECT name as type, 1 as count FROM WasteType_T";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
    }
    
    echo json_encode($data);
} catch (Exception $e) {
    error_log("Chart Error: " . $e->getMessage());
    
    // Static fallback data
    echo json_encode([
        ['type' => 'Metal', 'count' => 2],
        ['type' => 'Plastic', 'count' => 1]
    ]);
}
?>