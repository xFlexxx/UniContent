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
        <!-- Логотип -->
        <a href="index.php" style="color:white">
            <h1>UniContent</h1>
        </a>

        <!-- Меню -->
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
    <main>
        <!-- Подробнее о UniContent -->
        <section class="details-section">
            <h3>Подробнее о UniContent</h3>
            <div class="details-content">
                <p>
                    Веб-приложение <strong>UniContent</strong> разработано для удобного и централизованного управления
                    содержимым сайта кафедры системного программирования ЮУрГУ. Оно ориентировано на пользователей без
                    технической подготовки и позволяет быстро редактировать ключевые разделы сайта через простой и
                    интуитивно понятный интерфейс.
                </p>
                <h4>Основные возможности:</h4>
                <ul>
                    <li><strong>Создание и редактирование новостей</strong> — преподаватели и сотрудники могут
                        оперативно публиковать объявления, отчёты о мероприятиях, научные достижения и другую актуальную
                        информацию.</li>
                    <li><strong>Личные страницы преподавателей</strong> — для каждого сотрудника можно создать
                        персональную страницу с биографией, научными интересами, контактной информацией и списком
                        публикаций.</li>
                    <li><strong>Обновление научных публикаций</strong> — в отдельном разделе удобно добавлять и
                        структурировать список научных трудов, конференций, статей и других результатов
                        исследовательской деятельности.</li>
                </ul>
                <h4>Техническая реализация:</h4>
                <p>
                    UniContent создан с использованием технологий <strong>PHP</strong> и <strong>CSS</strong> без
                    применения баз данных. Вся информация хранится в виде структурированных PHP-файлов, что делает
                    архитектуру лёгкой и прозрачной. Такой подход упрощает резервное копирование, перенос данных и
                    снижает нагрузку на сервер.
                </p>
                <p>
                    Каждый функциональный модуль (новости, публикации, страницы преподавателей) реализован как
                    самостоятельный PHP-скрипт с чётко определённой логикой и интерфейсом. Контент организован по
                    отдельным директориям на сервере, что обеспечивает логичную структуру хранения данных и облегчает
                    сопровождение проекта.
                </p>
                <h4>Преимущества:</h4>
                <ul>
                    <li>Простота установки и поддержки</li>
                    <li>Отказ от СУБД как способ повышения надёжности и минимизации зависимостей</li>
                    <li>Быстрая адаптация под нужды кафедры</li>
                    <li>Возможность расширения функциональности за счёт модульной архитектуры</li>
                </ul>
                <p>
                    Таким образом, <strong>UniContent</strong> обеспечивает кафедре эффективное средство управления
                    сайтом без необходимости привлекать специалистов по веб-разработке для внесения рутинных изменений.
                </p>
            </div>
        </section>

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