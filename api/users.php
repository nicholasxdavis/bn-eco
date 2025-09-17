<?php
require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo_auth->query("SELECT id, email, full_name, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            $stmt = $pdo_auth->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'User already exists']);
                exit;
            }

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = in_array($data['role'], ['admin', 'editor', 'viewer']) ? $data['role'] : 'viewer';

            $stmt = $pdo_auth->prepare("INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['email'], $password_hash, $data['full_name'], $role]);

            echo json_encode(['success' => true, 'message' => 'User created successfully']);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit;
            }

            $stmt = $pdo_auth->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$data['user_id']]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>