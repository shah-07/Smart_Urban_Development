<?php
// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle different content types for input
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input && $method === 'POST') {
        // Fallback to form data for POST
        $input = $_POST;
    }
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT wasteTypeID as id, name as type, hazardLevel as hazard FROM WasteType_T");
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($method === 'POST') {
        if (!isset($input['type']) || !isset($input['hazard'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: type and hazard']);
            exit();
        }
        
        // Manual Auto-Increment
        $max = $pdo->query("SELECT MAX(wasteTypeID) FROM WasteType_T")->fetchColumn();
        $nextId = $max ? $max + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO WasteType_T (wasteTypeID, name, hazardLevel) VALUES (?, ?, ?)");
        $stmt->execute([$nextId, $input['type'], $input['hazard']]);
        echo json_encode(['message' => 'Added successfully', 'id' => $nextId]);
    } 
    elseif ($method === 'PUT') {
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required for update']);
            exit();
        }
        
        $stmt = $pdo->prepare("UPDATE WasteType_T SET name = ?, hazardLevel = ? WHERE wasteTypeID = ?");
        $stmt->execute([$input['type'], $input['hazard'], $input['id']]);
        echo json_encode(['message' => 'Updated successfully']);
    } 
    elseif ($method === 'DELETE') {
    // Get ID from query string
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID parameter is required']);
        exit();
    }
    
    // In types.php, inside the DELETE block:

try {
    // 1. Remove associations with Plants
    $pdo->prepare("DELETE FROM WasteType_Accepts_Recycling_Plant_T WHERE wasteTypeID = ?")->execute([$id]);
    
    // 2. [FIX] Unassign this waste type from Smart Bins (Set to NULL to prevent FK error)
    $pdo->prepare("UPDATE SmartBin_T SET wasteTypeID = NULL WHERE wasteTypeID = ?")->execute([$id]);
    
    // 3. Delete the waste type
    $stmt = $pdo->prepare("DELETE FROM WasteType_T WHERE wasteTypeID = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    echo json_encode(['message' => 'Deleted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
} catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Delete failed: ' . $e->getMessage()]);
    }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>