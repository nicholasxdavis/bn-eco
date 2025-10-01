<?php
require_once 'db_connect.php';

// --- A simple security check to prevent accidental deletion ---
// You must access this script with ?confirm=YES_DELETE in the URL
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'YES_DELETE') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Confirmation not provided. Please append "?confirm=YES_DELETE" to the URL to proceed.'
    ]);
    exit;
}

// --- MAIN SCRIPT LOGIC ---
try {
    // Get the current month and year to target the correct invoices
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Prepare the SQL statement to delete invoices from the current month and year
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE MONTH(issued) = :month AND YEAR(issued) = :year");
    
    // Execute the deletion
    $stmt->execute([
        ':month' => $currentMonth,
        ':year' => $currentYear
    ]);

    // Get the number of invoices that were deleted
    $deletedCount = $stmt->rowCount();

    // Send a success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Deletion successful. {$deletedCount} test invoices from the current month have been removed."
    ]);

} catch (PDOException $e) {
    // Handle any database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
