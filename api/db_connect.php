<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        if ($input['action'] === 'start') {
            // Start a 5-minute timer
            $endTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            try {
                $stmt = $pdo->prepare("INSERT INTO timers (name, end_time) 
                                      VALUES ('test_invoice', :end_time)
                                      ON DUPLICATE KEY UPDATE end_time = :end_time");
                $stmt->execute([':end_time' => $endTime]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Timer started',
                    'end_time' => $endTime
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to start timer: ' . $e->getMessage()]);
            }
        }
        elseif ($input['action'] === 'status') {
            // Get timer status
            try {
                $stmt = $pdo->prepare("SELECT end_time FROM timers WHERE name = 'test_invoice'");
                $stmt->execute();
                $timer = $stmt->fetch(PDO::FETCH_ASSOC);
                
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
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to get timer status: ' . $e->getMessage()]);
            }
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>