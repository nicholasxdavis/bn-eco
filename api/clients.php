<?php
require_once 'db_connect.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all clients
            $stmt = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
            $clients = $stmt->fetchAll();
            
            echo json_encode($clients);
            break;
            
        case 'POST':
            // Create new client
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("INSERT INTO clients 
                                  (company, contact, email, service, monthly_fee, status, join_date) 
                                  VALUES (:company, :contact, :email, :service, :monthly_fee, :status, :join_date)");
            $stmt->execute([
                ':company' => $input['company'],
                ':contact' => $input['contact'],
                ':email' => $input['email'],
                ':service' => $input['service'],
                ':monthly_fee' => $input['monthly_fee'],
                ':status' => 'active',
                ':join_date' => date('Y-m-d')
            ]);
            
            $clientId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'client_id' => $clientId
            ]);
            break;
            
        case 'DELETE':
            // Delete client
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['id'])) {
                $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
                $stmt->execute([':id' => $input['id']]);
                
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Client ID is required']);
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
}
?>