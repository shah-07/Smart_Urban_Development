<?php
// api/emergency/incidents.php
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        // Selecting 'incidentTime' explicitly
        $sql = "SELECT incidentID, incidentType, location, incidentTime, priority, status, latitude, longitude, description 
                FROM Incident_T ORDER BY incidentTime DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    // ... POST, PUT, DELETE same as before ...
    elseif ($method === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO Incident_T (incidentType, location, latitude, longitude, description, incidentTime, priority, status) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'Active')");
        $stmt->execute([$input->incidentType, $input->location, $input->latitude, $input->longitude, $input->description, $input->priority]);
        echo json_encode(['message' => 'Reported']);
    } 
    elseif ($method === 'PUT') {
        $stmt = $pdo->prepare("UPDATE Incident_T SET incidentType=?, location=?, latitude=?, longitude=?, priority=?, status=?, description=? WHERE incidentID=?");
        $stmt->execute([$input->incidentType, $input->location, $input->latitude, $input->longitude, $input->priority, $input->status, $input->description, $input->incidentID]);
        echo json_encode(['message' => 'Updated']);
    }
    elseif ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM Incident_T WHERE incidentID = ?")->execute([$_GET['id']]);
        echo json_encode(['message' => 'Deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>