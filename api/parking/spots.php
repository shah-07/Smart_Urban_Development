<?php
header("Content-Type: application/json");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        // Joins with Parking_Lot_T to get Location & Capacity info
        $sql = "SELECT S.spotID, S.sensorID, S.status, 
                       S.lotID, L.road, L.area, L.city, L.capacity
                FROM Parking_Spot_T S
                LEFT JOIN Parking_Lot_T L ON S.lotID = L.lotID
                ORDER BY S.spotID ASC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        // Insert new spot
        if (!empty($input->spotID)) {
            $stmt = $pdo->prepare("INSERT INTO Parking_Spot_T (spotID, lotID, sensorID, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input->spotID, $input->lotID, $input->sensorID, $input->status]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO Parking_Spot_T (lotID, sensorID, status) VALUES (?, ?, ?)");
            $stmt->execute([$input->lotID, $input->sensorID, $input->status]);
        }
        echo json_encode(['message' => 'Created']);
    } 
    elseif ($method === 'PUT') {
        // Update existing spot (Now supports Edit)
        $stmt = $pdo->prepare("UPDATE Parking_Spot_T SET lotID=?, sensorID=?, status=? WHERE spotID=?");
        $stmt->execute([$input->lotID, $input->sensorID, $input->status, $input->spotID]);
        echo json_encode(['message' => 'Updated']);
    }
    elseif ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM Parking_Spot_T WHERE spotID = ?")->execute([$_GET['id']]);
        echo json_encode(['message' => 'Deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>