<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Главная страница</title>
    <link rel="stylesheet" href="main.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2&display=swap" rel="stylesheet">
</head>

<body class="light-theme">
    <!-- Header -->
    <header>
        <!-- Логотип слева -->
        <a href="index.php" style="color:white">
            <h1>UniContent</h1>
        </a>

        <!-- Меню справа -->
        <nav>
            <ul>
                <li><a href="news.php">Новости</a></li>
                <li><a href="publications.php">Научная работа</a></li>
                <li><a href="profiles.php">Личные страницы</a></li>
            </ul>
        </nav>

        <div class="theme-switcher">
            <span>Темное оформление</span>
            <input type="checkbox" id="theme-toggle" class="theme-toggle">
            <!-- Кнопка для входа/выхода -->
            <div id="authBtnContainer">
                <button id="loginBtn">Войти</button>
                <div id="userInfo" style="display:none;">
                    <span id="userName"></span>
                    <button id="logoutBtn">⇐</button>
                </div>
            </div>
        </div>
    </header>
    <!-- Основной контент -->
    <main>
        <section class="welcome-section">
            <div class="welcome-content">
                <div class="text-content">
                    <h2>Добро пожаловать в UniContent</h2>
                    <p>UniContent - это инструмент, предназначенный для удобного и эффективного управления сайтом
                        кафедры системного программирования. Интуитивно понятный интерфейс и гибкие настройки делают
                        процесс работы с сайтом простым и удобным, а система автоматических обновлений гарантирует, что
                        все изменения будут синхронизированы в реальном времени.</p>
                    <div class="action-buttons">
                        <a href="news.php">
                            <button class="primary-btn">Начать работу</button>
                        </a>
                        <a href="more.php">
                            <button class="secondary-btn">Подробнее</button>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Основные возможности -->
        <section class="features-section">
            <h3>Основные возможности:</h3>
            <div class="features-cards">
                <!-- Карточка 1 -->
                <a href="news.php" class="feature-card">
                    <div class="feature-image">
                        <img src="news.jpg" alt="Управление новостями">
                    </div>
                    <div class="feature-title">
                        Управление новостями и объявлениями
                    </div>
                </a>

                <!-- Карточка 2 -->
                <a href="publications.php" class="feature-card">
                    <div class="feature-image">
                        <img src="publications.jpg" alt="Публикации научных работ">
                    </div>
                    <div class="feature-title">
                        Обновление информации о публикациях научных работ и статей
                    </div>
                </a>

                <!-- Карточка 3 -->
                <a href="profiles.php" class="feature-card">
                    <div class="feature-image">
                        <img src="profiles.jpg" alt="Личные страницы преподавателей">
                    </div>
                    <div class="feature-title">
                        Управление личными страницами преподавателей
                    </div>
                </a>
            </div>

            </div>
        </section>
    </main>

    <!-- Модальное окно для авторизации -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeLoginModal">&times;</span>
            <h2>Авторизация</h2>
            <form id="loginForm">
                <label for="username">Логин:</label>
                <input type="login" id="username" name="username" required>
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                <input type="submit" value="Войти">
            </form>
            <p id="errorMessage" style="color:red; display:none;">Неверный логин или пароль.</p>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            const loginBtn = $('#loginBtn');
            const loginModal = $('#loginModal');
            const closeLoginModal = $('#closeLoginModal');
            const loginForm = $('#loginForm');
            const errorMessage = $('#errorMessage');
            const userInfo = $('#userInfo');
            const userNameDisplay = $('#userName');
            const logoutBtn = $('#logoutBtn');

            // Открытие модального окна
            loginBtn.on('click', () => {
                loginModal.css('display', 'block');
            });

            // Закрытие модального окна
            closeLoginModal.on('click', () => {
                loginModal.css('display', 'none');
            });

            // Обработчик отправки формы
            loginForm.on('submit', function (e) {
                e.preventDefault();
                const username = $('#username').val();
                const password = $('#password').val();

                // Отправка данных на сервер
                $.ajax({
                    url: 'auth.php',
                    method: 'POST',
                    data: { username, password },
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            loginModal.css('display', 'none');
                            updateUI(username);
                        } else {
                            errorMessage.css('display', 'block');
                        }
                    }
                });
            });

            // Выход из аккаунта
            logoutBtn.on('click', function () {
                $.ajax({
                    url: 'auth.php?logout',
                    method: 'GET',
                    success: function () {
                        // Обновляем интерфейс без перезагрузки страницы
                        loginBtn.css('display', 'block');
                        userInfo.css('display', 'none');
                    }
                });
            });

            // Проверка состояния авторизации при загрузке страницы
            function checkAuth() {
                $.ajax({
                    url: 'auth.php',
                    method: 'GET',
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.username) {
                            updateUI(data.username);
                        }
                    }
                });
            }

            // Обновление UI после авторизации
            function updateUI(username) {
                loginBtn.css('display', 'none');
                userInfo.css('display', 'flex');
                userNameDisplay.text(username);
            }

            // Проверка авторизации при загрузке страницы
            checkAuth();
        });
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;

        // Функция для применения темы
        function applyTheme(theme) {
            if (theme === 'dark') {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                themeToggle.checked = true;
            } else {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                themeToggle.checked = false;
            }
        }

        // Загрузка сохранённой темы при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
        });

        // Переключение темы при клике
        themeToggle.addEventListener('click', () => {
            const newTheme = themeToggle.checked ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    </script>
</body>

</html>