<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get all clients
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($clients);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch clients: ' . $e->getMessage()]);
    }
}

// Create new client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
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
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create client: ' . $e->getMessage()]);
    }
}

// Delete client
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete client: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Client ID is required']);
    }
}
?>