<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

class DatabaseSync {
    private $connections = [];
    
    public function __construct() {
        echo "<h2>Попытка подключения к базам данных...</h2>";
        
        // Список баз данных для подключения
        $databases = ['characters', 'realmd', 'mangos', 'scriptdev3'];
        
        foreach ($databases as $db) {
            try {
                $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, $db);
                if ($conn->connect_error) {
                    throw new Exception("Не удалось подключиться к БД $db: " . $conn->connect_error);
                }
                $this->connections[$db] = $conn;
                echo "<p style='color:green'>Успешное подключение к БД: $db</p>";
            } catch (Exception $e) {
                echo "<p style='color:orange'>Предупреждение: ".$e->getMessage()."</p>";
            }
        }
    }
    
    // Аутентификация пользователя (использует realmd)
    public function authenticateUser($username, $password) {
        if (!isset($this->connections['realmd'])) {
            echo "<p style='color:red'>Нет подключения к БД realmd</p>";
            return false;
        }
        
        $username = $this->connections['realmd']->real_escape_string($username);
        $sql = "SELECT id, username, sha_pass_hash FROM account WHERE username = '$username' LIMIT 1";
        
        echo "<p>Выполняем запрос аутентификации: <code>".htmlspecialchars($sql)."</code></p>";
        
        $result = $this->connections['realmd']->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Проверка пароля для Mangos/TrinityCore
            $expected_hash = strtoupper(sha1(strtoupper($username.':'.$password)));
            if (strtoupper($user['sha_pass_hash']) === $expected_hash) {
                echo "<p style='color:green'>Аутентификация успешна для пользователя: ".htmlspecialchars($user['username'])."</p>";
                return $user;
            } else {
                echo "<p style='color:red'>Неверный пароль для пользователя: ".htmlspecialchars($username)."</p>";
            }
        } else {
            echo "<p style='color:red'>Пользователь не найден: ".htmlspecialchars($username)."</p>";
        }
        
        return false;
    }
    
    // Просмотр списка таблиц в указанной базе данных
    public function showTables($database) {
        if (!isset($this->connections[$database])) {
            echo "<p style='color:red'>Нет подключения к БД $database</p>";
            return;
        }
        
        $result = $this->connections[$database]->query("SHOW TABLES");
        echo "<h3>Таблицы в базе $database:</h3><ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>".$row[0]."</li>";
        }
        echo "</ul>";
    }
    
    // Закрытие всех соединений
    public function __destruct() {
        foreach ($this->connections as $conn) {
            $conn->close();
        }
        echo "<p>Соединения с БД закрыты</p>";
    }
}

// HTML-шапка
echo "<!DOCTYPE html>
<html>
<head>
    <title>Тест подключения к БД</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        code { background: #f0f0f0; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>Тестирование подключения к базам данных</h1>";

// Создаем экземпляр класса и тестируем
try {
    $dbSync = new DatabaseSync();
    
    // Показываем таблицы во всех базах данных
    $databases = ['characters', 'realmd', 'mangos', 'scriptdev3'];
    foreach ($databases as $db) {
        $dbSync->showTables($db);
    }
    
    // Тест аутентификации
    echo "<h2>Тест аутентификации</h2>";
    $testUser = 'admin'; // замените на реальный логин
    $testPass = 'admin'; // замените на реальный пароль
    $authResult = $dbSync->authenticateUser($testUser, $testPass);
    
} catch (Exception $e) {
    echo "<p style='color:red'>Ошибка: ".$e->getMessage()."</p>";
}

echo "</body></html>";
?>