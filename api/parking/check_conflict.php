<?php
// api/parking/check_conflict.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include_once '../../config.php';

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
    
    // Required fields validation
    $required = ['spotID', 'startTime', 'endTime'];
    foreach ($required as $field) {
        if (!isset($input->$field)) {
            echo json_encode(['error' => "Missing required field: $field"]);
            exit();
        }
    }
    
    $spotID = $input->spotID;
    $startTime = $input->startTime;
    $endTime = $input->endTime;
    $excludeReservationID = isset($input->excludeReservationID) ? $input->excludeReservationID : 0;
    
    // Convert to datetime
    $startDateTime = date('Y-m-d H:i:s', strtotime($startTime));
    $endDateTime = date('Y-m-d H:i:s', strtotime($endTime));
    
    // Validate datetime conversion
    if ($startDateTime === false || $endDateTime === false) {
        echo json_encode(['error' => 'Invalid date/time format']);
        exit();
    }
    
    // Check if spot exists and is available
    $spotCheck = $pdo->prepare("SELECT status FROM Parking_Spot_T WHERE spotID = ?");
    $spotCheck->execute([$spotID]);
    $spot = $spotCheck->fetch();
    
    if (!$spot) {
        echo json_encode(['error' => 'Parking spot does not exist']);
        exit();
    }
    
    if ($spot['status'] === 'Maintenance') {
        echo json_encode(['error' => 'Parking spot is under maintenance']);
        exit();
    }
    
    // ENHANCED OVERLAP CHECKING
    // Check for ANY overlapping reservations (Pending or Reserved)
    $sql = "SELECT 
                r.reservationID,
                r.citizenID,
                CONCAT(c.fname, ' ', c.lname) as citizenName,
                r.startTime,
                r.endTime,
                r.status,
                TIMESTAMPDIFF(MINUTE, ?, r.endTime) as minutes_overlap_start,
                TIMESTAMPDIFF(MINUTE, r.startTime, ?) as minutes_overlap_end
            FROM Reservation_T r
            LEFT JOIN Citizen_T c ON r.citizenID = c.citizenID
            WHERE r.spotID = ? 
            AND r.status IN ('Pending', 'Reserved')
            AND r.reservationID != ?
            AND (
                -- 4 overlap scenarios:
                -- 1. New reservation starts during existing reservation
                (? BETWEEN r.startTime AND DATE_SUB(r.endTime, INTERVAL 1 SECOND))
                OR
                -- 2. New reservation ends during existing reservation
                (? BETWEEN DATE_ADD(r.startTime, INTERVAL 1 SECOND) AND r.endTime)
                OR
                -- 3. New reservation completely contains existing reservation
                (? <= r.startTime AND ? >= r.endTime)
                OR
                -- 4. Existing reservation completely contains new reservation
                (r.startTime <= ? AND r.endTime >= ?)
            )
            ORDER BY r.startTime ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $startDateTime,  // ? for minutes_overlap_start
        $endDateTime,    // ? for minutes_overlap_end
        $spotID,
        $excludeReservationID,
        $startDateTime,  // Scenario 1
        $endDateTime,    // Scenario 2
        $startDateTime,  // Scenario 3 start
        $endDateTime,    // Scenario 3 end
        $startDateTime,  // Scenario 4 start
        $endDateTime     // Scenario 4 end
    ]);
    
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    
    // Return conflict details
    echo json_encode([
        'hasConflict' => count($conflicts) > 0,
        'conflictCount' => count($conflicts),
        'conflictingReservations' => $conflicts,
        'requestedTime' => [
            'start' => $startDateTime,
            'end' => $endDateTime,
            'duration_minutes' => $durationMinutes
        ],
        'spotStatus' => $spot['status']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>