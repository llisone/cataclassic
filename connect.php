<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Параметры подключения
$servername = "localhost";
$username = "mangos";
$password = "mangos"; // Убедитесь, что пароль правильный
$dbname = "characters"; // Используем базу characters, так как она существует

try {
    // Создаем соединение
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Ошибка подключения: " . $conn->connect_error);
    }
    echo "Успешное подключение к базе данных '$dbname'<br>";
    
    // Проверка существования таблицы (например, characters)
    $result = $conn->query("SHOW TABLES LIKE 'characters'");
    if ($result->num_rows == 0) {
        throw new Exception("Таблица 'characters' не найдена");
    }

    // Пример запроса к таблице characters
    $sql = "SELECT * FROM characters LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<h3>Первые 5 персонажей:</h3>";
        while($row = $result->fetch_assoc()) {
            echo "ID: " . $row["guid"] . " | Имя: " . $row["name"] . "<br>";
        }
    } else {
        echo "Персонажи не найдены";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>