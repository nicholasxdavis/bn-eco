<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database credentials for bn-eco
$host_eco = 'f08cwk48kso8wo84wk0ow840';
$dbname_eco = 'default';
$user_eco = 'mariadb';
$pass_eco = 'k8VUnt2oZhIgKebpi226TaRT9nwJN7B9kKGvhXTdqBNdzfnLe5r3hPmgLIVPZLYm';

// Database credentials for bn-client-dashboard (authentication)
$host_auth = 'roscwoco0sc8w08kwsko8ko8';
$dbname_auth = 'default';
$user_auth = 'mariadb';
$pass_auth = 'JswmqQok4swQf1JDKQD1WE311UPXBBE6NYJv6jRSP91dbkZDYj5sMc5sehC1LQTu';

// NEW: Database credentials for bn-outreach admin users
$host_admin = 'f08cwk48kso8wo84wk0ow840';
$dbname_admin = 'default';
$user_admin = 'mariadb';
$pass_admin = 'k8VUnt2oZhIgKebpi226TaRT9nwJN7B9kKGvhXTdqBNdzfnLe5r3hPmgLIVPZLYm';


try {
    // Connection for bn-eco
    $pdo = new PDO("mysql:host=$host_eco;dbname=$dbname_eco;charset=utf8mb4", $user_eco, $pass_eco);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Connection for authentication
    $pdo_auth = new PDO("mysql:host=$host_auth;dbname=$dbname_auth;charset=utf8mb4", $user_auth, $pass_auth);
    $pdo_auth->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_auth->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // NEW: Connection for admin users
    $pdo_admin = new PDO("mysql:host=$host_admin;dbname=$dbname_admin;charset=utf8mb4", $user_admin, $pass_admin);
    $pdo_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_admin->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>
