<?php
// api/emergency/vehicles.php
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM Emergency_Vehicle_T");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $pdo->beginTransaction();
        // 1. Ensure ID exists in parent Vehicle_T first
        $pdo->prepare("INSERT IGNORE INTO Vehicle_T (registrationNumber) VALUES (?)")->execute([$input->registrationNumber]);
        
        // 2. Insert into Emergency_Vehicle_T
        $stmt = $pdo->prepare("INSERT INTO Emergency_Vehicle_T (registrationNumber, unitType, status, assignedIncidentID) VALUES (?, ?, ?, NULL)");
        $stmt->execute([$input->registrationNumber, $input->unitType, $input->status]);
        
        $pdo->commit();
        echo json_encode(['message' => 'Vehicle Added']);
    }
    elseif ($method === 'PUT') {
        // Handle NULL for assignedIncidentID if empty
        $assigned = ($input->assignedIncidentID === "" || $input->assignedIncidentID === "-") ? NULL : $input->assignedIncidentID;
        
        $sql = "UPDATE Emergency_Vehicle_T SET unitType=?, status=?, assignedIncidentID=? WHERE registrationNumber=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input->unitType, $input->status, $assigned, $input->registrationNumber]);
        echo json_encode(['message' => 'Vehicle Updated']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM Emergency_Vehicle_T WHERE registrationNumber = ?")->execute([$id]);
        echo json_encode(['message' => 'Vehicle Deleted']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>