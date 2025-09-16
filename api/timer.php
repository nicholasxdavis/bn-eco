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
                    // Start a 5-minute timer
                    $endTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    $stmt = $pdo->prepare("INSERT INTO timers (name, end_time) 
                                          VALUES ('test_invoice', :end_time)
                                          ON DUPLICATE KEY UPDATE end_time = :end_time");
                    $stmt->execute([':end_time' => $endTime]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Timer started',
                        'end_time' => $endTime
                    ]);
                }
                elseif ($input['action'] === 'status') {
                    // Get timer status
                    $stmt = $pdo->prepare("SELECT end_time FROM timers WHERE name = 'test_invoice'");
                    $stmt->execute();
                    $timer = $stmt->fetch();
                    
                    if ($timer) {
                        $currentTime = new DateTime();
                        $endTime = new DateTime($timer['end_time']);
                        
                        if ($currentTime < $endTime) {
                            $remaining = $currentTime->diff($endTime);
                            echo json_encode([
                                'active' => true,
                                'end_time' => $timer['end_time'],
                                'remaining' => $remaining->format('%i minutes %s seconds')
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