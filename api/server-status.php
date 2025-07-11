<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Подключение к базе данных (замените параметры на свои)
$db = new mysqli('localhost', 'mangos', 'mangos', 'characters'); // Был 'character'

// Проверка соединения с БД
if ($db->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Ошибка подключения к базе данных'
    ]));
}

// 1. Проверка статуса сервера
$serverOnline = isServerOnline('127.0.0.1', 8085);

// 2. Получение данных из базы
$status = [
    'status' => $serverOnline ? 'online' : 'offline',
    'players_online' => $serverOnline ? getCurrentPlayers($db) : 0,
    'max_online' => getMaxOnline($db),
    'total_accounts' => getTotalAccounts($db),
    'uptime' => getServerUptime(),
    'message' => $serverOnline 
        ? 'Сервер работает стабильно' 
        : 'Сервер временно недоступен',
    'timestamp' => time()
];

echo json_encode($status, JSON_UNESCAPED_UNICODE);

// Функции для работы с данными:

// Проверка доступности сервера
function isServerOnline($ip, $port, $timeout = 2) {
    $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($socket) {
        fclose($socket);
        return true;
    }
    return false;
}

// Текущие игроки онлайн
function getCurrentPlayers($db) {
    $query = "SELECT COUNT(*) as count FROM characters WHERE online = 1";
    $result = $db->query($query);
    return $result->fetch_assoc()['count'];
}

// Максимальный онлайн
function getMaxOnline($db) {
    $query = "SELECT max_online FROM server_stats ORDER BY id DESC LIMIT 1";
    $result = $db->query($query);
    return $result->fetch_assoc()['max_online'] ?? 1000;
}

// Всего аккаунтов
function getTotalAccounts($db) {
    $query = "SELECT COUNT(*) as count FROM accounts";
    $result = $db->query($query);
    return $result->fetch_assoc()['count'];
}

// Время работы сервера
function getServerUptime() {
    // Если сервер на Linux, можно использовать:
    // $uptime = shell_exec('uptime -p');
    // return trim($uptime);
    
    // Или для Windows:
    // $uptime = shell_exec('net stats server | find "Статистика"');
    // return $uptime;
    
    // Временная заглушка:
    return '2д 5ч';
}

$db->close();
?>