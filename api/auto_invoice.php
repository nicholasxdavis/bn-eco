<?php
require_once 'db_connect.php';

// --- SERVER-SIDE EMAIL FUNCTION USING EmailJS REST API ---
function sendInvoiceEmail($client, $invoice) {
    // --- Your EmailJS Details ---
    $service_id = 'service_g3x1dzf';   // <-- PASTE YOUR SERVICE ID HERE
    $template_id = 'template_ptf2tzg';   // <-- PASTE YOUR TEMPLATE ID HERE
    $user_id = 'mGAM0CatzjBKJTVe9';       // Your Public Key
    $accessToken = 'vvI_4F6Wh4tCZKXB8r2Kx'; // Your Private Key

    // These parameters must match the variables in your EmailJS template
    // e.g., {{customerName}}, {{invoiceNumber}}, etc.
    $template_params = [
        'customerName' => $client['contact'],
        'email' => $client['email'],
        'invoiceNumber' => $invoice['id'],
        'dateIssued' => date("M d, Y", strtotime($invoice['issued'])),
        'service' => $invoice['service'],
        'price' => number_format($invoice['amount'], 2),
        'dueDate' => date("M d, Y", strtotime($invoice['due_date'])),
        'from_name' => 'Blacnova Development'
    ];

    // Prepare the data for the API request
    $data = [
        'service_id' => $service_id,
        'template_id' => $template_id,
        'user_id' => $user_id,
        'template_params' => $template_params,
        'accessToken' => $accessToken
    ];

    $payload = json_encode($data);

    // Use cURL to send the request to the EmailJS API
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // --- TEMPORARY DEBUGGING ---
    echo "<h2>EmailJS Debug Info:</h2>";
    echo "<p><strong>HTTP Status Code:</strong> " . $httpcode . "</p>";
    echo "<p><strong>EmailJS Response:</strong> " . $response . "</p>";
    // --- END TEMPORARY DEBUGGING ---


    // Log the result for debugging
    if ($httpcode == 200) {
        error_log("EmailJS API Success: Email sent to " . $client['email']);
    } else {
        error_log("EmailJS API Error: Failed to send email to " . $client['email'] . ". Response: " . $response);
    }
}

// --- MAIN SCRIPT LOGIC ---

/*
// Only run on the 1st or 15th day of the month.
$dayOfMonth = date('j');
if ($dayOfMonth != 1 && $dayOfMonth != 15) {
    echo "Not a scheduled invoice day. Exiting.\n";
    exit;
}
*/

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
        'issued' => $today,
        'due_date' => $dueDate
    ];
    
    sendInvoiceEmail($client, $invoiceData);
    $invoicedClients++;
}

echo "Invoicing task completed. " . $invoicedClients . " invoices were processed.\n";
?>
