<?php
require_once 'db_connect.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all invoices
            $stmt = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC LIMIT 5");
            $invoices = $stmt->fetchAll();
            
            echo json_encode($invoices);
            break;
            
        case 'POST':
            // Create new invoice
            $input = json_decode(file_get_contents('php://input'), true);
            
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