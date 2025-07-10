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
$username = $conn->real_escape_string($data['username'] ?? '');
$email = $conn->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Все поля обязательны для заполнения'
    ]);
    exit;
}

try {
    // Проверяем, существует ли уже такой логин
    $stmt = $conn->prepare("SELECT id FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь с таким логином уже существует'
        ]);
        exit;
    }
    
    // Генерируем хеш пароля (Mangos/TrinityCore формат)
    $sha_pass_hash = strtoupper(sha1(strtoupper($username . ':' . $password)));
    
    // Создаем аккаунт
    $stmt = $conn->prepare("INSERT INTO account (username, sha_pass_hash, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $sha_pass_hash, $email);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Аккаунт успешно создан',
            'username' => $username
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при создании аккаунта'
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