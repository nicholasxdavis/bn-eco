<?php
require_once 'db_connect.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['action'])) {
                if ($input['action'] === 'start') {
                    // Get duration from input, default to 5 minutes
                    $minutes = isset($input['duration']) ? (int)$input['duration'] : 5;

                    // Start a timer using UTC to avoid timezone issues
                    $endTime = new DateTime("now", new DateTimeZone("UTC"));
                    $endTime->add(new DateInterval("PT{$minutes}M"));
                    $endTimeStr = $endTime->format('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO timers (name, end_time) 
                                          VALUES ('test_invoice', :end_time)
                                          ON DUPLICATE KEY UPDATE end_time = :end_time");
                    $stmt->execute([':end_time' => $endTimeStr]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Timer started for {$minutes} minutes",
                        'end_time' => $endTimeStr
                    ]);
                }
                elseif ($input['action'] === 'status') {
                    // Get timer status
                    $stmt = $pdo->prepare("SELECT end_time FROM timers WHERE name = 'test_invoice'");
                    $stmt->execute();
                    $timer = $stmt->fetch();
                    
                    if ($timer) {
                        // Compare times in UTC
                        $currentTime = new DateTime("now", new DateTimeZone("UTC"));
                        $endTime = new DateTime($timer['end_time'], new DateTimeZone("UTC"));
                        
                        if ($currentTime < $endTime) {
                            echo json_encode([
                                'active' => true,
                                'end_time' => $timer['end_time']
                            ]);
                        } else {
                            echo json_encode([
                                'active' => false,
                                'message' => 'Timer completed'
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'active' => false,
                            'message' => 'No active timer'
                        ]);
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action parameter is required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>