<?php
require_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$sensorID = isset($_GET['sensorID']) ? $_GET['sensorID'] : null;
$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : null;

switch ($method) {
    case 'GET':
        if ($sensorID && $timestamp) {
            try {
                $query = "SELECT * FROM Pollution_Data_T 
                          WHERE sensorID = :sensorID AND timestamp = :timestamp";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':sensorID', $sensorID);
                $stmt->bindParam(':timestamp', $timestamp);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($result);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        }
        break;
        
    case 'PUT':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "UPDATE Pollution_Data_T SET 
                      airQuality = :airQuality,
                      noiseLevel = :noiseLevel,
                      pm25Level = :pm25Level,
                      noXLevel = :noXLevel,
                      co2Level = :co2Level
                      WHERE sensorID = :sensorID AND timestamp = :timestamp";
            
            $stmt = $pdo->prepare($query);
            
            $stmt->bindParam(':airQuality', $data['airQuality']);
            $stmt->bindParam(':noiseLevel', $data['noiseLevel']);
            $stmt->bindParam(':pm25Level', $data['pm25Level']);
            $stmt->bindParam(':noXLevel', $data['noXLevel']);
            $stmt->bindParam(':co2Level', $data['co2Level']);
            $stmt->bindParam(':sensorID', $data['sensorID']);
            $stmt->bindParam(':timestamp', $data['timestamp']);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Reading updated successfully"]);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        if ($sensorID && $timestamp) {
            try {
                $query = "DELETE FROM Pollution_Data_T 
                          WHERE sensorID = :sensorID AND timestamp = :timestamp";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':sensorID', $sensorID);
                $stmt->bindParam(':timestamp', $timestamp);
                
                if ($stmt->execute()) {
                    echo json_encode(["message" => "Reading deleted successfully"]);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        }
        break;
}
?>