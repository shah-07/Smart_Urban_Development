<?php
// api/emergency/responses.php
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        $sql = "SELECT R.responseID, R.incidentID, I.incidentType, I.location, I.incidentTime as reportTime,
                       R.registrationNumber, R.dispatchTime, R.arrivalTime, R.completionTime,
                       TIMESTAMPDIFF(MINUTE, R.dispatchTime, R.arrivalTime) as responseTime,
                       TIMESTAMPDIFF(MINUTE, R.arrivalTime, R.completionTime) as serviceTime
                FROM Response_Times_T R
                LEFT JOIN Incident_T I ON R.incidentID = I.incidentID
                ORDER BY R.dispatchTime DESC";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $t = $row['responseTime'];
            if ($t === null) $row['performance'] = 'Pending';
            elseif ($t <= 10) $row['performance'] = 'Excellent';
            elseif ($t <= 20) $row['performance'] = 'Good';
            else $row['performance'] = 'Delayed';
        }
        echo json_encode($data);
    } 
    elseif ($method === 'POST') {
        // Resolve / Add History
        // Ensure Incident ID exists (simple check)
        $stmt = $pdo->prepare("INSERT INTO Response_Times_T (incidentID, registrationNumber, dispatchTime, arrivalTime, completionTime, outcome) VALUES (?, ?, ?, ?, ?, 'Resolved')");
        $stmt->execute([
            $input->incidentID, 
            $input->registrationNumber, 
            $input->dispatchTime, 
            $input->arrivalTime, 
            $input->completionTime
        ]);
        
        // Auto-update Incident to Resolved
        $pdo->prepare("UPDATE Incident_T SET status='Resolved' WHERE incidentID=?")->execute([$input->incidentID]);
        
        // Auto-free Vehicle
        $pdo->prepare("UPDATE Emergency_Vehicle_T SET status='Available', assignedIncidentID=NULL WHERE registrationNumber=?")->execute([$input->registrationNumber]);
        
        echo json_encode(['message' => 'Saved']);
    }
    elseif ($method === 'PUT') {
        $stmt = $pdo->prepare("UPDATE Response_Times_T SET incidentID=?, registrationNumber=?, dispatchTime=?, arrivalTime=?, completionTime=? WHERE responseID=?");
        $stmt->execute([
            $input->incidentID, 
            $input->registrationNumber, 
            $input->dispatchTime, 
            $input->arrivalTime, 
            $input->completionTime, 
            $input->responseID
        ]);
        echo json_encode(['message' => 'Updated']);
    }
    elseif ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM Response_Times_T WHERE responseID = ?")->execute([$_GET['id']]);
        echo json_encode(['message' => 'Deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>