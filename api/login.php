<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$remember = $data['remember'] ?? false;

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    // Step 1: Authenticate the user against the primary 'bn-client-dashboard' database
    $stmt = $pdo_auth->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password if user is found
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // Step 2: Check if the authenticated user's email also exists as a username in the 'bn-outreach admin users' database
        $admin_stmt = $pdo_admin->prepare('SELECT id FROM users WHERE username = ?');
        $admin_stmt->execute([$email]);
        $admin_user = $admin_stmt->fetch();

        if ($admin_user) {
            // Success: User exists in both databases, proceed with login
            unset($user['password_hash']);
            
            $token = bin2hex(random_bytes(32));

            // Store session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['token'] = $token;

            echo json_encode([
                'success' => true, 
                'user' => $user,
                'token' => $token
            ]);
        } else {
            // Failure: User is not in the admin database
            echo json_encode(['success' => false, 'message' => 'You are not authorized to access this dashboard']);
        }
    } else {
        // Failure: Invalid credentials in the primary database
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} catch (\PDOException $e) {
    // Handle potential database connection errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
