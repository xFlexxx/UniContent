<?php
session_start();
// Проверка сообщения об успехе из сессии
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); 
}
require_once 'profile_functions.php';

// Загрузка существующих профилей
$profiles = getProfilesData();
$default_phone = '(351) 267-90-89';
$default_email = '@susu.ru';

// Если форма отправлена, используем введённые данные. Иначе — значения по умолчанию.
$phone_value = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : $default_phone;
$email_value = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : $default_email;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handleProfileForm($profiles);

    if ($result['success']) {
        $success = $result['message'];
        $profiles = getProfilesData();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Создание личной страницы</title>
    <link rel="stylesheet" href="main.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2&display=swap" rel="stylesheet">
    <style>
        .slider-container {
            margin: 10px 0;
        }

        .slider-container label {
            display: block;
            margin-bottom: 5px;
        }

        .slider-container input[type="range"] {
            width: 100%;
        }
    </style>
</head>

<body class="light-theme">
    <!-- Header -->
    <header>
        <a href="index.php" style="color:white">
            <h1>UniContent</h1>
        </a>
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
            <span class="close-btn" id="closeLoginModal">×</span>
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

    <!-- Основной контент -->
    <h1 style="padding: 0 12px;">Создание личной страницы</h1>
    <div class="preview-container">
        <!-- Форма -->
        <div class="form-container">
            <form method="post" id="profileForm" enctype="multipart/form-data">
                <label for="fio">ФИО*</label>
                <input type="text" id="fio" name="fio" required
                    value="<?= isset($_POST['fio']) ? htmlspecialchars($_POST['fio']) : '' ?>">

                <div class="add-btn-container">
                    <label>Вариации фамилии для поиска публикаций</label>
                    <button type="button" class="add-btn" onclick="addSurnameVariation()">+</button>
                </div>
                <div id="surname-variations-container">
                    <?php if (isset($_POST['surname_variations']) && !empty($_POST['surname_variations'])): ?>
                        <?php foreach ($_POST['surname_variations'] as $variation): ?>
                            <div class="variation">
                                <input type="text" name="surname_variations[]" value="<?= htmlspecialchars($variation) ?>">
                                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="variation">
                            <input type="text" name="surname_variations[]" placeholder="Вариация фамилии">
                            <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                        </div>
                    <?php endif; ?>
                </div>

                <label for="position">Должность*</label>
                <input type="text" id="position" name="position" required
                    value="<?= isset($_POST['position']) ? htmlspecialchars($_POST['position']) : '' ?>">

                <label for="degree">Ученая степень, звание</label>
                <input type="text" id="degree" name="degree"
                    value="<?= isset($_POST['degree']) ? htmlspecialchars($_POST['degree']) : '' ?>">

                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" value="<?= $phone_value ?>">

                <label for="email">Email*</label>
                <input type="email" id="email" name="email" required value="<?= $email_value ?>">

                <label for="photo">Фотография</label>
                <input type="file" id="photo" name="photo" accept="image/*">

                <!-- Ползунки для регулировки размера изображения -->
                <div class="slider-container">
                    <label for="photo-width">Ширина изображения (px): <span id="width-value">250</span></label>
                    <input type="range" id="photo-width" name="photo_width" min="50" max="500" value="250" step="10">
                </div>
                <div class="slider-container">
                    <label for="photo-height">Высота изображения (px): <span id="height-value">250</span></label>
                    <input type="range" id="photo-height" name="photo_height" min="50" max="500" value="250" step="10">
                </div>

                <div class="add-btn-container">
                    <label>Достижения, поощрения и награды</label>
                    <button type="button" class="add-btn" onclick="addAward()">+</button>
                </div>
                <div id="awards-container">
                    <?php if (isset($_POST['awards']) && is_array($_POST['awards'])): ?>
                        <?php foreach ($_POST['awards'] as $index => $award): ?>
                            <div class="award">
                                <input type="text" name="awards[<?= $index ?>][name]"
                                    value="<?= htmlspecialchars($award['name'] ?? '') ?>" placeholder="Название награды">
                                <input type="text" name="awards[<?= $index ?>][link]"
                                    value="<?= htmlspecialchars($award['link'] ?? '') ?>"
                                    placeholder="Ссылка или путь к награде (необязательно)">
                                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="award">
                            <input type="text" name="awards[0][name]" placeholder="Название награды">
                            <input type="text" name="awards[0][link]"
                                placeholder="Ссылка или путь к награде (необязательно)">
                            <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                        </div>
                    <?php endif; ?>
                </div>

                <label for="interests">Область научных интересов</label>
                <textarea id="interests" name="interests"
                    rows="5"><?= isset($_POST['interests']) ? htmlspecialchars($_POST['interests']) : '' ?></textarea>

                <input type="submit" value="Создать профиль" id="saveProfileBtn" disabled>
                <!-- Сообщения об успехе или ошибке -->
                <?php if (isset($success)): ?>
                    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
                <?php elseif (isset($error)): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            </form>
        </div>

        <!-- Предпросмотр -->
        <div class="code-container">
            <label for="previewOutput">Предпросмотр профиля:</label>
            <div id="previewOutput" class="preview"></div>
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
            const saveProfileBtn = $('#saveProfileBtn');

            loginBtn.on('click', () => loginModal.css('display', 'block'));
            closeLoginModal.on('click', () => loginModal.css('display', 'none'));

            loginForm.on('submit', function (e) {
                e.preventDefault();
                const username = $('#username').val();
                const password = $('#password').val();
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

            logoutBtn.on('click', function () {
                $.ajax({
                    url: 'auth.php?logout',
                    method: 'GET',
                    success: function () {
                        loginBtn.css('display', 'block');
                        userInfo.css('display', 'none');
                        disableSaveProfileButton();
                    }
                });
            });

            function checkAuth() {
                $.ajax({
                    url: 'auth.php',
                    method: 'GET',
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.username) updateUI(data.username);
                        else disableSaveProfileButton();
                    }
                });
            }

            function updateUI(username) {
                loginBtn.css('display', 'none');
                userInfo.css('display', 'flex');
                userNameDisplay.text(username);
                enableSaveProfileButton();
            }

            function enableSaveProfileButton() {
                saveProfileBtn.prop('disabled', false);
                saveProfileBtn.css('opacity', '1');
            }

            function disableSaveProfileButton() {
                saveProfileBtn.prop('disabled', true);
                saveProfileBtn.css('opacity', '0.6');
            }

            checkAuth();
        });

        function addSurnameVariation() {
            const container = document.getElementById('surname-variations-container');
            const div = document.createElement('div');
            div.classList.add('variation');
            div.innerHTML = `
        <input type="text" name="surname_variations[]" placeholder="Вариация фамилии">
        <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
    `;
            // Вставляем новое поле перед кнопкой добавления
            container.insertBefore(div, container.querySelector('.add-btn'));
            updatePreview();
        }

        function addAward() {
            const container = document.getElementById('awards-container');
            const index = container.querySelectorAll('.award').length;
            const div = document.createElement('div');
            div.classList.add('award');
            div.innerHTML = `
                <input type="text" name="awards[${index}][name]" placeholder="Название награды">
                <input type="text" name="awards[${index}][link]" placeholder="Ссылка на награду (необязательно)">
                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
            `;
            container.appendChild(div);
            updatePreview();
        }

        function removeAward(btn) {
            btn.parentNode.remove();
            updatePreview();
        }

        function updatePreview() {
            const fio = document.getElementById('fio').value || 'Не указано';
            const position = document.getElementById('position').value || 'Не указано';
            const degree = document.getElementById('degree').value;
            const phone = document.getElementById('phone').value;
            const email = document.getElementById('email').value || '@susu.ru';
            const interests = document.getElementById('interests').value;
            const photo = document.getElementById('photo').files[0];
            const photoWidth = document.getElementById('photo-width').value;
            const photoHeight = document.getElementById('photo-height').value;

            // Обновляем значения ширины и высоты в подписях
            document.getElementById('width-value').textContent = photoWidth;
            document.getElementById('height-value').textContent = photoHeight;

            // Обработка фотографии
            let photoUrl = photo ? URL.createObjectURL(photo) : '';

            // Условные HTML-блоки
            let degreeHtml = degree ? `<p><strong>Ученая степень:</strong> ${degree}</p>` : '';
            let phoneHtml = phone ? `<p><strong>Телефон:</strong> ${phone}</p>` : '';
            let awardsHtml = '';
            let interestsHtml = interests ? `<p><strong>Область научных интересов:</strong> ${interests}</p>` : '';
            let publicationsHtml = '<div class="publications-section"><h3>Публикации</h3><p>Публикации не загружены</p></div>';
            let headline = `<h1>${position} ${fio}</h1>`;

            // Обработка наград
            const awards = Array.from(document.querySelectorAll('.award')).map(award => ({
                name: award.querySelector('input[name*="[name]"]').value,
                link: award.querySelector('input[name*="[link]"]').value
            })).filter(award => award.name);

            if (awards.length > 0) {
                awardsHtml = `<p><strong>Достижения, поощрения и награды:</strong></p><ul class="awards-list">`;
                awards.forEach(award => {
                    // Проверяем, является ли ссылка относительным путем
                    if (award.link) {
                        const isRelativePath = !award.link.match(/^https?:\/\//i) && award.link.match(/^[a-zA-Z0-9\/\.\-_]+$/);
                        const href = isRelativePath ? award.link : award.link;
                        awardsHtml += `<li>${award.link ? `<a href="${href}" target="_blank">${award.name}</a>` : award.name}</li>`;
                    } else {
                        awardsHtml += `<li>${award.name}</li>`;
                    }
                });
                awardsHtml += `</ul>`;
            }

            // Генерация полного HTML
            let htmlCode = `
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        .none-border { border: none; padding: 5px; }
        table { width: 100%; max-width: 800px; margin: 0 auto; }
        img { max-width: 100%; height: auto; border-radius: 8px; }
        hr { margin: 20px 0; }
        .profile-container { display: flex; gap: 20px; margin-bottom: 20px; }
        .profile-photo { flex: 0 0 auto; }
        .profile-info { flex: 1; }
        .awards-list { margin-top: 10px; }
        .publications-section { margin-top: 30px; }
        .publications-section ol { padding-left: 20px; }
        .publications-section li { margin-bottom: 10px; }
    </style>
</head>
<body>
    ${headline}
    <div class="profile-container">
        <div class="profile-photo">
            ${photoUrl ? `<img src="${photoUrl}" width="${photoWidth}" height="${photoHeight}" alt="${fio}">` : ''}
        </div>
        <div class="profile-info">
            <p><strong>Должность:</strong> ${position}</p>
            ${degreeHtml}
            ${phoneHtml}
            <p><strong>Email:</strong> <a href="mailto:${email}">${email}</a></p>
            ${awardsHtml}
        </div>
    </div>
    <hr>
    ${interestsHtml}
    <hr>
    ${publicationsHtml}
    <hr>
</body>
</html>
            `;

            document.getElementById('previewOutput').innerHTML = htmlCode;
        }

        // Обновление предпросмотра при вводе
        document.getElementById('profileForm').addEventListener('input', updatePreview);
        document.getElementById('photo').addEventListener('change', updatePreview);
        document.getElementById('photo-width').addEventListener('input', updatePreview);
        document.getElementById('photo-height').addEventListener('input', updatePreview);

        // Инициализация предпросмотра
        updatePreview();

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