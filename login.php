<?php
header('Content-Type: application/json');

// Пример проверки данных (замените на реальную проверку из базы данных)
$validEmail = 'manina20@mail.ru';
$validPassword = '123123';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($email === $validEmail && $password === $validPassword) {
    echo json_encode([
        'success' => true,
        'message' => 'Вход выполнен успешно!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный email или пароль'
    ]);
}
?>