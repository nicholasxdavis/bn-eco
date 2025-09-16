<?php
require_once 'db_connect.php';

// IMPORTANT: This script is designed to be run by a server cron job.

// --- SERVER-SIDE EMAIL CONFIGURATION ---
// You CANNOT use EmailJS on the server. You need a server-side email solution
// like PHPMailer or an API from a service like SendGrid or Mailgun.

function sendInvoiceEmail($client, $invoice) {
    // This is a placeholder function. You must replace this with your actual
    // server-side email sending logic.
    
    $to = $client['email'];
    $subject = "New Invoice from Blacnova Development";
    
    $body = "Hello " . $client['contact'] . ",\n\n";
    $body .= "This is your monthly invoice for " . $invoice['service'] . ".\n\n";
    $body .= "Invoice #: " . $invoice['id'] . "\n";
    $body .= "Amount Due: $" . number_format($invoice['amount'], 2) . "\n";
    $body .= "Due Date: " . date("M d, Y", strtotime($invoice['due_date'])) . "\n\n";
    $body .= "Thank you for your business!\n";
    $body .= "Blacnova Development";

    // For now, we will log the email to the server's error log for testing.
    // Check your server's PHP error logs to see this output.
    error_log("--- NEW INVOICE (SIMULATED EMAIL) ---");
    error_log("To: " . $to);
    error_log("Subject: " . $subject);
    error_log("Body: \n" . $body);
    error_log("------------------------------------");
}


// --- MAIN SCRIPT LOGIC ---

// Only run on the 1st or 15th day of the month.
$dayOfMonth = date('j');
if ($dayOfMonth != 1 && $dayOfMonth != 15) {
    echo "Not a scheduled invoice day. Exiting.\n";
    exit;
}

// Get all active clients
$stmt = $pdo->query("SELECT * FROM clients WHERE status = 'active'");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($clients) === 0) {
    echo "No active clients to invoice. Exiting.\n";
    exit;
}

$invoicedClients = 0;

// Create invoices and send emails for all active clients
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

    $invoiceData = [
        'id' => $invoiceId,
        'amount' => $client['monthly_fee'],
        'service' => $client['service'],
        'due_date' => $dueDate
    ];
    
    sendInvoiceEmail($client, $invoiceData);
    $invoicedClients++;
}

echo "Invoicing task completed. " . $invoicedClients . " invoices were created and sent.\n";
?>