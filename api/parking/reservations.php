<?php
header("Content-Type: application/json");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

try {
    if ($method === 'GET') {
        // Fetches all required columns including Citizen Name
        $sql = "SELECT R.reservationID, R.spotID, R.citizenID, 
                       CONCAT(C.fname, ' ', C.lname) as citizenName,
                       R.startTime, R.endTime, R.amount, 
                       R.paymentGateway, R.paymentDate
                FROM Reservation_T R
                LEFT JOIN Citizen_T C ON R.citizenID = C.citizenID
                ORDER BY R.startTime DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $sql = "INSERT INTO Reservation_T (citizenID, spotID, startTime, endTime, amount, paymentGateway, paymentDate) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->citizenID, 
            $input->spotID, 
            $input->startTime, 
            $input->endTime, 
            $input->amount, 
            $input->paymentGateway
        ]);
        echo json_encode(['message' => 'Created']);
    } 
    elseif ($method === 'PUT') {
        $sql = "UPDATE Reservation_T SET 
                citizenID=?, spotID=?, startTime=?, endTime=?, amount=?, paymentGateway=? 
                WHERE reservationID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->citizenID, 
            $input->spotID, 
            $input->startTime, 
            $input->endTime, 
            $input->amount, 
            $input->paymentGateway,
            $input->reservationID
        ]);
        echo json_encode(['message' => 'Updated']);
    } 
    elseif ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM Reservation_T WHERE reservationID = ?")->execute([$_GET['id']]);
        echo json_encode(['message' => 'Deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>