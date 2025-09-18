<?php
require_once 'db_connect.php';

// Set the correct content type header
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($data['action'])) {
        throw new Exception('Action is required.');
    }

    // Action to request a password reset link
    if ($data['action'] === 'request_reset') {
        if (empty($data['email'])) {
            throw new Exception('Email is required.');
        }

        $email = $data['email'];

        $stmt = $pdo_auth->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        // We check if the user exists before proceeding
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32));
            $expires = new DateTime('now', new DateTimeZone('UTC'));
            $expires->add(new DateInterval('PT1H')); // Token expires in 1 hour
            $expires_str = $expires->format('Y-m-d H:i:s');

            // Delete any existing tokens for this email to invalidate old links
            $del_stmt = $pdo_auth->prepare("DELETE FROM password_resets WHERE email = ?");
            $del_stmt->execute([$email]);

            // Insert the new token into the database
            $ins_stmt = $pdo_auth->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins_stmt->execute([$email, $token, $expires_str]);

            // The actual email sending is handled by the frontend.
            // We return the token so the frontend can build the reset link.
            echo json_encode(['success' => true, 'token' => $token]);
        } else {
             // To prevent user enumeration, we send a generic success response even if the email is not found.
             echo json_encode(['success' => true, 'token' => null, 'message' => 'If a user with that email exists, a reset link will be sent.']);
        }

    // Action to perform the password update with a valid token
    } elseif ($data['action'] === 'perform_reset') {
        if (empty($data['token']) || empty($data['password'])) {
            throw new Exception('Token and new password are required.');
        }

        $token = $data['token'];
        $new_password = $data['password'];

        $stmt = $pdo_auth->prepare("SELECT * FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch();

        if (!$reset_request) {
            throw new Exception('Invalid or expired token.');
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $expires = new DateTime($reset_request['expires_at'], new DateTimeZone('UTC'));

        // Check if the token has expired
        if ($now > $expires) {
            // Clean up the expired token from the database
            $del_stmt = $pdo_auth->prepare("DELETE FROM password_resets WHERE token = ?");
            $del_stmt->execute([$token]);
            throw new Exception('Token has expired.');
        }
        
        // If token is valid, hash the new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the user's password in the users table
        $update_stmt = $pdo_auth->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $update_stmt->execute([$password_hash, $reset_request['email']]);

        // Delete the token so it cannot be reused
        $del_stmt = $pdo_auth->prepare("DELETE FROM password_resets WHERE token = ?");
        $del_stmt->execute([$token]);

        echo json_encode(['success' => true, 'message' => 'Password has been reset successfully.']);

    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
