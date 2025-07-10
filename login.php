<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$dbHost = 'localhost';
$dbUser = 'mangos';
$dbPass = 'mangos';
$dbName = 'realmd';

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($conn->connect_error) {
        throw new Exception("Ошибка подключения к базе данных: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email и пароль обязательны для заполнения'
    ]);
    exit;
}

try {
    // Ищем пользователя по email
    $stmt = $conn->prepare("SELECT username, sha_pass_hash, email FROM account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь с таким email не найден'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $storedHash = $user['sha_pass_hash'];
    $username = $user['username'];
    
    // Генерируем хеш для проверки (в TrinityCore используется USERNAME:пароль, даже если вход по email)
    $inputHash = strtoupper(sha1(strtoupper($username.':'.$password)));
    
    if ($inputHash === strtoupper($storedHash)) {
        echo json_encode([
            'success' => true,
            'message' => 'Авторизация успешна',
            'username' => $username,
            'email' => $email
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Неверный пароль'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}

$conn->close();
?>