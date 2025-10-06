<?php
require_once 'db_connect.php'; // This will now include $pdo_admin

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo_admin->query("SELECT id, username FROM users ORDER BY id DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['username']) || empty($data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                exit;
            }

            $stmt = $pdo_admin->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'User already exists']);
                exit;
            }

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $pdo_admin->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$data['username'], $password_hash]);

            echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
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
