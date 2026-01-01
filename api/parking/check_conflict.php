<?php
// api/parking/check_conflict.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include_once '../../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $input = json_decode(file_get_contents("php://input"));
    
    if (!$input) {
        echo json_encode(['error' => 'Invalid input']);
        exit();
    }
    
    $spotID = $input->spotID;
    $startTime = $input->startTime;
    $endTime = $input->endTime;
    $excludeReservationID = isset($input->excludeReservationID) ? $input->excludeReservationID : 0;
    
    // Convert input to datetime format for SQL
    $startDateTime = date('Y-m-d H:i:s', strtotime($startTime));
    $endDateTime = date('Y-m-d H:i:s', strtotime($endTime));
    
    // VALIDATION 3: Check for date AND time conflicts with both Pending and Reserved statuses
    // This checks if the new reservation overlaps with ANY existing reservation
    $sql = "SELECT reservationID, startTime, endTime, status
            FROM Reservation_T 
            WHERE spotID = ? 
            AND status IN ('Pending', 'Reserved')
            AND reservationID != ?
            AND NOT (
                -- No overlap condition: new reservation ends before existing starts OR starts after existing ends
                ? <= startTime OR ? >= endTime
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $spotID,
        $excludeReservationID,
        $endDateTime, // new reservation ends before existing starts
        $startDateTime // new reservation starts after existing ends
    ]);
    
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'hasConflict' => count($conflicts) > 0,
        'conflictCount' => count($conflicts),
        'conflictingReservations' => $conflicts
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>