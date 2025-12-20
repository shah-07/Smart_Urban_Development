<?php
require_once '../../config.php';

// GET all pollution readings
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $query = "
            SELECT 
                pd.sensorID,
                pd.timestamp,
                
                -- CRITICAL FIX: Cast AQI to CHAR (String) so .includes() works in frontend
                CAST(pd.airQuality AS CHAR) AS airQuality,
                
                -- Ensure other decimal values are also sent as strings
                CAST(pd.noiseLevel AS CHAR) AS noiseLevel,
                CAST(pd.pm25Level AS CHAR) AS pm25Level,
                CAST(pd.co2Level AS CHAR) AS co2Level,
                CAST(pd.noXLevel AS CHAR) AS noXLevel,
                
                -- Location data
                iot.road,
                iot.area,
                iot.latitude,
                iot.longitude
            FROM Pollution_Data_T pd
            LEFT JOIN Iot_Sensor_T iot ON pd.sensorID = iot.sensorID
            ORDER BY pd.timestamp DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($result);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// POST new reading (Admin only)
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $query = "INSERT INTO Pollution_Data_T 
                  (sensorID, timestamp, airQuality, noiseLevel, pm25Level, noXLevel, co2Level) 
                  VALUES (:sensorID, :timestamp, :airQuality, :noiseLevel, :pm25Level, :noXLevel, :co2Level)";
        
        $stmt = $pdo->prepare($query);
        
        $stmt->bindParam(':sensorID', $data['sensorID']);
        $stmt->bindParam(':timestamp', $data['timestamp']);
        $stmt->bindParam(':airQuality', $data['airQuality']);
        $stmt->bindParam(':noiseLevel', $data['noiseLevel']);
        $stmt->bindParam(':pm25Level', $data['pm25Level']);
        $stmt->bindParam(':noXLevel', $data['noXLevel']);
        $stmt->bindParam(':co2Level', $data['co2Level']);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Reading added successfully"]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>