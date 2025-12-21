<?php
// api/waste/transport.php
header("Content-Type: application/json");
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        if ($action === 'vehicles') {
            $stmt = $pdo->query("SELECT registrationNumber as id, registrationNumber as name FROM Waste_Transport_T");
            echo json_encode($stmt->fetchAll());
        } 
        else {
            $sql = "SELECT 
                        wc.collectionID as id,
                        wc.registrationNumber as vehicleId,
                        wt.registrationNumber as vehicleName,
                        wt.wasteCapacity as capacity,
                        wc.collectionVolume as collected,
                        DATE_FORMAT(wc.collectionDate, '%Y-%m-%d') as collectionDate,
                        TIME_FORMAT(wc.collectionTime, '%H:%i:%s') as collectionTime,
                        CONCAT(wc.collectionDate, ' ', wc.collectionTime) as timestamp
                    FROM Waste_Collection_T wc
                    JOIN Waste_Transport_T wt ON wc.registrationNumber = wt.registrationNumber
                    ORDER BY wc.collectionDate DESC, wc.collectionTime DESC";
            
            $stmt = $pdo->query($sql);
            echo json_encode($stmt->fetchAll());
        }
    }
    elseif ($method === 'POST') {
        $ts = $input['timestamp']; 
        $date = date('Y-m-d', strtotime($ts));
        $time = date('H:i:s', strtotime($ts));

        $stmt = $pdo->prepare("INSERT INTO Waste_Collection_T (registrationNumber, collectionVolume, collectionDate, collectionTime) VALUES (?, ?, ?, ?)");
        $stmt->execute([$input['vehicleId'], $input['collected'], $date, $time]);
        
        echo json_encode(['message' => 'Recorded successfully']);
    }
    elseif ($method === 'PUT') {
        // For update, we need original values to identify the record
        $originalVehicleId = $input['originalVehicleId'] ?? '';
        $originalTimestamp = $input['originalTimestamp'] ?? '';
        
        // Parse original timestamp to date/time
        $originalDate = date('Y-m-d', strtotime($originalTimestamp));
        $originalTime = date('H:i:s', strtotime($originalTimestamp));
        
        // Parse new timestamp
        $newDate = date('Y-m-d', strtotime($input['timestamp']));
        $newTime = date('H:i:s', strtotime($input['timestamp']));
        
        $stmt = $pdo->prepare("UPDATE Waste_Collection_T 
                              SET registrationNumber = ?, collectionVolume = ?, collectionDate = ?, collectionTime = ?
                              WHERE registrationNumber = ? AND collectionDate = ? AND collectionTime = ?");
        
        $stmt->execute([
            $input['vehicleId'], 
            $input['collected'], 
            $newDate, 
            $newTime,
            $originalVehicleId,
            $originalDate,
            $originalTime
        ]);
        
        echo json_encode(['message' => 'Updated successfully']);
    }
    elseif ($method === 'DELETE') {
        // Need vehicleId and timestamp to identify record
        $vehicleId = $_GET['id'] ?? '';
        $timestamp = $_GET['ts'] ?? '';
        
        if (!$vehicleId || !$timestamp) {
            http_response_code(400);
            echo json_encode(['error' => 'Vehicle ID and timestamp are required']);
            exit();
        }
        
        $date = date('Y-m-d', strtotime($timestamp));
        $time = date('H:i:s', strtotime($timestamp));
        
        $stmt = $pdo->prepare("DELETE FROM Waste_Collection_T WHERE registrationNumber = ? AND collectionDate = ? AND collectionTime = ?");
        $stmt->execute([$vehicleId, $date, $time]);
        
        echo json_encode(['message' => 'Deleted successfully']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>