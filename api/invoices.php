<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get all invoices
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC LIMIT 5");
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($invoices);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch invoices: ' . $e->getMessage()]);
    }
}

// Create new invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO invoices 
                              (client_id, client_name, amount, issued, due_date, status, service) 
                              VALUES (:client_id, :client_name, :amount, :issued, :due_date, :status, :service)");
        $stmt->execute([
            ':client_id' => $input['client_id'],
            ':client_name' => $input['client_name'],
            ':amount' => $input['amount'],
            ':issued' => $input['issued'],
            ':due_date' => $input['due_date'],
            ':status' => 'pending',
            ':service' => $input['service']
        ]);
        
        $invoiceId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'invoice_id' => $invoiceId
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
    }
}
?>