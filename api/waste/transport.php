<?php
header("Content-Type: application/json");
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // 1. POPULATE DROPDOWN
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'vehicles') {
        $stmt = $pdo->query("SELECT registrationNumber as id, registrationNumber as name FROM Waste_Transport_T");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 2. GET all collection records (existing code)
    if ($method === 'GET') {
        $sql = "SELECT 
                    wc.registrationNumber as vehicleId, 
                    wt.registrationNumber as vehicleName,
                    wt.wasteCapacity as capacity,
                    wc.collectionVolume as collected,
                    CONCAT(wc.collectionDate, ' ', wc.collectionTime) as timestamp,
                    rp.name as plantName,
                    sb.location as binLocation
                FROM Waste_Collection_T wc
                JOIN Waste_Transport_T wt ON wc.registrationNumber = wt.registrationNumber
                LEFT JOIN Recycling_Plant_T rp ON wc.plantID = rp.plantID
                LEFT JOIN SmartBin_T sb ON wc.binID = sb.binID
                ORDER BY wc.collectionDate DESC, wc.collectionTime DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll());
        exit;
    }
    elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['vehicleId']) || empty($input['timestamp']) || !isset($input['collected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: vehicleId, timestamp, or collected amount']);
    exit;
}
    
    // Check if vehicle exists
    $vehicleCheck = $pdo->prepare("SELECT COUNT(*) FROM Waste_Transport_T WHERE registrationNumber = ?");
    $vehicleCheck->execute([$input['vehicleId']]);
    if ($vehicleCheck->fetchColumn() == 0) {
        // Create vehicle if it doesn't exist
        $pdo->prepare("INSERT INTO Waste_Transport_T (registrationNumber, wasteCapacity) VALUES (?, 1000)")
            ->execute([$input['vehicleId']]);
    }
    
    // Get or create default plant
    $plant = $pdo->query("SELECT plantID FROM Recycling_Plant_T LIMIT 1")->fetch();
    if (!$plant) {
        // Create dummy plant
        $pdo->query("INSERT INTO Recycling_Plant_T (plantID, name, capacity, location) VALUES (1, 'Main Plant', 1000, 'City Center')");
        $plant = ['plantID' => 1];
    }
    
    // Get or create default bin
    $bin = $pdo->query("SELECT binID FROM SmartBin_T LIMIT 1")->fetch();
    if (!$bin) {
        // Create dummy bin
        $pdo->query("INSERT INTO SmartBin_T (binID, location, capacity, status) VALUES (1, 'Street 1', 100, 'Active')");
        $bin = ['binID' => 1];
    }
    
    $dt = new DateTime($input['timestamp']);
    
    $stmt = $pdo->prepare("INSERT INTO Waste_Collection_T 
        (registrationNumber, plantID, binID, collectionDate, collectionTime, collectionVolume) 
        VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $input['vehicleId'], 
        $plant['plantID'], 
        $bin['binID'], 
        $dt->format('Y-m-d'), 
        $dt->format('H:i:s'), 
        $input['collected']
    ]);
    
    echo json_encode(['message' => 'Collection recorded', 'id' => $input['vehicleId']]);
}

elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    // 1. Get New Values
    $newId = $input['vehicleId'];
    $newVol = $input['collected'];
    $newDt = new DateTime($input['timestamp']);

    // 2. Get Original Values (Identify the row)
    $oldId = $input['originalVehicleId'];
    $oldTs = new DateTime($input['originalTimestamp']);

    // 3. Update using Original Values in WHERE clause
    $sql = "UPDATE Waste_Collection_T 
            SET registrationNumber = ?, collectionDate = ?, collectionTime = ?, collectionVolume = ?
            WHERE registrationNumber = ? AND collectionDate = ? AND collectionTime = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $newId, 
        $newDt->format('Y-m-d'), $newDt->format('H:i:s'), 
        $newVol,
        $oldId, 
        $oldTs->format('Y-m-d'), $oldTs->format('H:i:s')
    ]);
    
    echo json_encode(['message' => 'Updated successfully']);
}

    elseif ($method === 'DELETE') {
    // Get parameters - using $_GET since frontend sends query string
    $vehicleId = $_GET['id'] ?? null;
    $timestamp = $_GET['ts'] ?? null;
    
    if (!$vehicleId || !$timestamp) {
        http_response_code(400);
        echo json_encode(['error' => 'Vehicle ID and Timestamp are required']);
        exit();
    }
    
    try {
        $dt = new DateTime($timestamp);
        
        $stmt = $pdo->prepare("DELETE FROM Waste_Collection_T 
                               WHERE registrationNumber = ? 
                               AND collectionDate = ? 
                               AND collectionTime = ?");
        $stmt->execute([
            $vehicleId, 
            $dt->format('Y-m-d'), 
            $dt->format('H:i:s')
        ]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
        } else {
            echo json_encode(['message' => 'Deleted successfully']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Delete failed: ' . $e->getMessage()]);
    }
}
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>