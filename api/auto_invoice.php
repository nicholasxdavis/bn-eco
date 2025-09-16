<?php
require_once 'api/db_connect.php';

// Get all active clients
$stmt = $pdo->query("SELECT * FROM clients WHERE status = 'active'");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create invoices for all active clients
foreach ($clients as $client) {
    $today = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+14 days'));
    
    $stmt = $pdo->prepare("INSERT INTO invoices 
                          (client_id, client_name, amount, issued, due_date, status, service) 
                          VALUES (:client_id, :client_name, :amount, :issued, :due_date, :status, :service)");
    $stmt->execute([
        ':client_id' => $client['id'],
        ':client_name' => $client['company'],
        ':amount' => $client['monthly_fee'],
        ':issued' => $today,
        ':due_date' => $dueDate,
        ':status' => 'pending',
        ':service' => $client['service']
    ]);
    
    $invoiceId = $pdo->lastInsertId();
    
    // Send email (you would need to implement this)
    // sendInvoiceEmail($client, $invoiceId);
}

echo "Monthly invoicing completed for " . count($clients) . " clients.\n";
?>