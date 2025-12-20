<?php
// api/traffic/records.php
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        // Fetch all traffic records
        $sql = "SELECT recordID, sensorID, location, timestamp, trafficFlow, congestionLevel, numberOfRoadAccidents 
                FROM Traffic_Data_T ORDER BY timestamp DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        // Add Record
        $sql = "INSERT INTO Traffic_Data_T (sensorID, location, timestamp, trafficFlow, congestionLevel, numberOfRoadAccidents) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->sensorID,
            $input->location,
            $input->timestamp,
            $input->trafficFlow,
            $input->congestionLevel,
            $input->numberOfRoadAccidents
        ]);
        echo json_encode(['message' => 'Record Added']);
    } 
    elseif ($method === 'PUT') {
        // Update Record
        $sql = "UPDATE Traffic_Data_T SET 
                sensorID=?, location=?, timestamp=?, trafficFlow=?, congestionLevel=?, numberOfRoadAccidents=? 
                WHERE recordID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->sensorID,
            $input->location,
            $input->timestamp,
            $input->trafficFlow,
            $input->congestionLevel,
            $input->numberOfRoadAccidents,
            $input->recordID
        ]);
        echo json_encode(['message' => 'Record Updated']);
    } 
    elseif ($method === 'DELETE') {
        // Delete Record
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM Traffic_Data_T WHERE recordID=?");
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Record Deleted']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>