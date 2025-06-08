<?php
session_start();

// Массив пользователей
$users = [
    "admin" => "admin",
];

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Проверка логина и пароля
    if (isset($users[$username]) && $users[$username] === $password) {
        // Успешная авторизация
        $_SESSION['username'] = $username; // Сохраняем пользователя в сессии
        echo json_encode(['success' => true]);
    } else {
        // Ошибка авторизации
        echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
    }
    exit();
}

// Выход из аккаунта
if (isset($_GET['logout'])) {
    session_destroy(); // Уничтожаем сессию
    echo json_encode(['success' => true]);
    exit();
}

// Проверка состояния авторизации при загрузке страницы
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['logout'])) {
    if (isset($_SESSION['username'])) {
        echo json_encode(['username' => $_SESSION['username']]);
    } else {
        echo json_encode(['username' => null]);
    }
    exit();
}
?>