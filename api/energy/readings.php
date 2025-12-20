<?php
// api/energy/readings.php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        // Fetch all columns required
        $sql = "SELECT sensorID, energyType, timestamp, totalConsumedUnit, lastRechargedDate, lastRechargedAmount 
                FROM Energy_Consumption_Data_T 
                ORDER BY timestamp DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $sql = "INSERT INTO Energy_Consumption_Data_T (sensorID, energyType, timestamp, totalConsumedUnit, lastRechargedDate, lastRechargedAmount) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->sensorID,
            $input->energyType,
            $input->timestamp,
            $input->totalConsumedUnit,
            $input->lastRechargedDate,
            $input->lastRechargedAmount
        ]);
        echo json_encode(['message' => 'Record added']);
    } 
    elseif ($method === 'PUT') {
        // Identify record by OLD sensorID and timestamp (Composite Key)
        $sql = "UPDATE Energy_Consumption_Data_T SET 
                sensorID=?, energyType=?, timestamp=?, totalConsumedUnit=?, lastRechargedDate=?, lastRechargedAmount=? 
                WHERE sensorID=? AND timestamp=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->sensorID,
            $input->energyType,
            $input->timestamp,
            $input->totalConsumedUnit,
            $input->lastRechargedDate,
            $input->lastRechargedAmount,
            $input->originalSensorID, 
            $input->originalTimestamp
        ]);
        echo json_encode(['message' => 'Record updated']);
    } 
    elseif ($method === 'DELETE') {
        $sid = $_GET['sensorID'];
        $ts = $_GET['timestamp'];
        $stmt = $pdo->prepare("DELETE FROM Energy_Consumption_Data_T WHERE sensorID=? AND timestamp=?");
        $stmt->execute([$sid, $ts]);
        echo json_encode(['message' => 'Record deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>