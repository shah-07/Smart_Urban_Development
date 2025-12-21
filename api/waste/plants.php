<?php
// api/waste/plants.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'db.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log for debugging (remove in production)
    error_log("Method: $method, Input: " . json_encode($input));

    if ($method === 'GET') {
        // Get all plants with their accepted waste types
        $sql = "SELECT 
                    rp.plantID as id, 
                    rp.name, 
                    rp.location, 
                    rp.capacity,
                    COALESCE(GROUP_CONCAT(wt.wasteTypeID), '') as acceptedTypeIds,
                    COALESCE(GROUP_CONCAT(wt.name SEPARATOR ', '), 'None') as acceptedTypes
                FROM Recycling_Plant_T rp
                LEFT JOIN WasteType_Accepts_Recycling_Plant_T map ON rp.plantID = map.plantID
                LEFT JOIN WasteType_T wt ON map.wasteTypeID = wt.wasteTypeID
                GROUP BY rp.plantID, rp.name, rp.location, rp.capacity
                ORDER BY rp.plantID";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetchAll();
        
        // Ensure all fields exist
        foreach ($result as &$row) {
            $row['acceptedTypes'] = $row['acceptedTypes'] ?? 'None';
            $row['acceptedTypeIds'] = $row['acceptedTypeIds'] ?? '';
        }
        
        echo json_encode($result);
    } 
    elseif ($method === 'POST') {
        // Validate required fields
        if (empty($input['name']) || empty($input['location']) || empty($input['capacity'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: name, location, capacity']);
            exit();
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Add new plant
            $stmt = $pdo->prepare("INSERT INTO Recycling_Plant_T (name, location, capacity) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($input['name']), 
                trim($input['location']), 
                intval($input['capacity'])
            ]);
            
            $newPlantId = $pdo->lastInsertId();
            
            // Add accepted waste types to junction table
            if (!empty($input['wasteTypeIds']) && is_array($input['wasteTypeIds'])) {
                $insertStmt = $pdo->prepare("INSERT INTO WasteType_Accepts_Recycling_Plant_T (plantID, wasteTypeID) VALUES (?, ?)");
                foreach ($input['wasteTypeIds'] as $wasteTypeId) {
                    $cleanId = intval(trim($wasteTypeId));
                    if ($cleanId > 0) {
                        $insertStmt->execute([$newPlantId, $cleanId]);
                    }
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'message' => 'Plant added successfully', 
                'id' => $newPlantId,
                'wasteTypeIds' => $input['wasteTypeIds'] ?? []
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    elseif ($method === 'PUT') {
        // Validate required fields
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Plant ID is required for update']);
            exit();
        }
        
        $plantId = intval($input['id']);
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update plant basic info
            $stmt = $pdo->prepare("UPDATE Recycling_Plant_T SET name = ?, location = ?, capacity = ? WHERE plantID = ?");
            $stmt->execute([
                trim($input['name']), 
                trim($input['location']), 
                intval($input['capacity']), 
                $plantId
            ]);
            
            // Update accepted waste types - remove old ones first
            $pdo->prepare("DELETE FROM WasteType_Accepts_Recycling_Plant_T WHERE plantID = ?")->execute([$plantId]);
            
            // Add new selections
            if (!empty($input['wasteTypeIds']) && is_array($input['wasteTypeIds'])) {
                $insertStmt = $pdo->prepare("INSERT INTO WasteType_Accepts_Recycling_Plant_T (plantID, wasteTypeID) VALUES (?, ?)");
                foreach ($input['wasteTypeIds'] as $wasteTypeId) {
                    $cleanId = intval(trim($wasteTypeId));
                    if ($cleanId > 0) {
                        $insertStmt->execute([$plantId, $cleanId]);
                    }
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'message' => 'Plant updated successfully',
                'wasteTypeIds' => $input['wasteTypeIds'] ?? []
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID parameter is required']);
            exit();
        }
        
        $plantId = intval($id);
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Delete from junction table first
            $pdo->prepare("DELETE FROM WasteType_Accepts_Recycling_Plant_T WHERE plantID = ?")->execute([$plantId]);
            
            // Then delete plant
            $stmt = $pdo->prepare("DELETE FROM Recycling_Plant_T WHERE plantID = ?");
            $stmt->execute([$plantId]);
            
            $pdo->commit();
            
            echo json_encode(['message' => 'Plant deleted successfully']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Plants API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error occurred',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>