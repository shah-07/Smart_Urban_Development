<?php
header("Content-Type: application/json");
include_once '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"));

// Get user role from request or session
$userRole = isset($input->userRole) ? $input->userRole : 'Admin';

try {
    if ($method === 'GET') {
        // Fetches all required columns including Citizen Name
        $sql = "SELECT R.reservationID, R.spotID, R.citizenID, 
                       CONCAT(C.fname, ' ', C.lname) as citizenName,
                       R.startTime, R.endTime, R.amount, 
                       R.paymentGateway, R.paymentDate,
                       R.status, R.created_by
                FROM Reservation_T R
                LEFT JOIN Citizen_T C ON R.citizenID = C.citizenID
                ORDER BY R.startTime DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        // Determine status based on who is creating
        $status = ($userRole === 'Citizen') ? 'Pending' : 'Reserved';
        
        $sql = "INSERT INTO Reservation_T (citizenID, spotID, startTime, endTime, amount, paymentGateway, paymentDate, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input->citizenID, 
            $input->spotID, 
            $input->startTime, 
            $input->endTime, 
            $input->amount, 
            $input->paymentGateway,
            $status,
            $userRole
        ]);
        echo json_encode(['message' => 'Created', 'status' => $status]);
    } 
    elseif ($method === 'PUT') {
        // Check if this is a status update or regular edit
        if (isset($input->newStatus) && $input->newStatus === 'Reserved') {
            // Confirm reservation (status update only)
            $sql = "UPDATE Reservation_T SET status = ? WHERE reservationID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['Reserved', $input->reservationID]);
            echo json_encode(['message' => 'Reservation Confirmed']);
        } else {
            // Regular edit (admin only)
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
    } 
    elseif ($method === 'DELETE') {
        $pdo->prepare("DELETE FROM Reservation_T WHERE reservationID = ?")->execute([$_GET['id']]);
        echo json_encode(['message' => 'Deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>