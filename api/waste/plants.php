<?php
header("Content-Type: application/json");
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

function updatePlantTypes($pdo, $plantId, $typesInput) {
    // Clear existing associations
    $stmt = $pdo->prepare("DELETE FROM WasteType_Accepts_Recycling_Plant_T WHERE plantID = ?");
    $stmt->execute([$plantId]);

    // If typesInput is empty string or null, just return
    if (empty($typesInput)) {
        return;
    }
    
    // Handle both string (comma-separated) and array inputs
    if (is_string($typesInput)) {
        $typeIds = array_filter(array_map('trim', explode(',', $typesInput)));
    } elseif (is_array($typesInput)) {
        $typeIds = $typesInput;
    } else {
        return;
    }
    
    if (empty($typeIds)) {
        return;
    }

    $insertStmt = $pdo->prepare("INSERT INTO WasteType_Accepts_Recycling_Plant_T (wasteTypeID, plantID) VALUES (?, ?)");
    foreach ($typeIds as $typeId) {
        if (is_numeric($typeId)) {
            $insertStmt->execute([$typeId, $plantId]);
        }
    }
}

try {
    if ($method === 'GET') {
    $sql = "SELECT 
                p.plantID as id, 
                p.name, 
                p.location, 
                p.capacity, 
                -- This combines names like 'Plastic, Metal' for the table
                COALESCE(GROUP_CONCAT(wt.name SEPARATOR ', '), 'None') as acceptedTypes,
                -- This combines IDs like '1,2' for the Edit Logic
                COALESCE(GROUP_CONCAT(wt.wasteTypeID SEPARATOR ','), '') as acceptedTypeIds
            FROM Recycling_Plant_T p
            LEFT JOIN WasteType_Accepts_Recycling_Plant_T wta ON p.plantID = wta.plantID
            LEFT JOIN WasteType_T wt ON wta.wasteTypeID = wt.wasteTypeID
            GROUP BY p.plantID, p.name, p.location, p.capacity";
            
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());
}
    elseif ($method === 'POST') {
    // 1. Validation
    if (empty($input['name']) || empty($input['location'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and Location are required']);
        exit;
    }

    // 2. Sensor Handling (Required by Database Constraint)
    $sensorID = $pdo->query("SELECT sensorID FROM IOT_SENSOR_T LIMIT 1")->fetchColumn();
    if (!$sensorID) {
        // Create a dummy sensor if none exists so the Plant insert doesn't fail
        $pdo->query("INSERT INTO IOT_SENSOR_T (type, status, location) VALUES ('PlantSensor', 'Active', 'Plant')");
        $sensorID = $pdo->lastInsertId();
    }

    // 3. Insert Plant
    $stmt = $pdo->prepare("INSERT INTO Recycling_Plant_T (name, location, capacity, sensorID) VALUES (?, ?, ?, ?)");
    $stmt->execute([$input['name'], $input['location'], $input['capacity'], $sensorID]);
    $newPlantId = $pdo->lastInsertId();

    // 4. Update Waste Types
    if (!empty($input['acceptedTypes'])) {
        updatePlantTypes($pdo, $newPlantId, $input['acceptedTypes']);
    }

    echo json_encode(['message' => 'Plant added successfully']);
}
    elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    // 1. Update Plant Details
    $stmt = $pdo->prepare("UPDATE Recycling_Plant_T SET name=?, location=?, capacity=? WHERE plantID=?");
    $stmt->execute([$input['name'], $input['location'], $input['capacity'], $input['id']]);
    
    // 2. Update Waste Types
    if (isset($input['acceptedTypes'])) {
        updatePlantTypes($pdo, $input['id'], $input['acceptedTypes']);
    }
    
    echo json_encode(['message' => 'Plant updated successfully']);
}
    elseif ($method === 'DELETE') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM WasteType_Accepts_Recycling_Plant_T WHERE plantID=?")->execute([$id]);
        $pdo->prepare("DELETE FROM Waste_Collection_T WHERE plantID=?")->execute([$id]); // Clear history to allow delete
        $pdo->prepare("DELETE FROM Recycling_Plant_T WHERE plantID=?")->execute([$id]);
        echo json_encode(['message' => 'Plant deleted']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>