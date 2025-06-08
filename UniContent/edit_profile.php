<?php
session_start();
require_once 'edit_profile_functions.php';

// Обработка AJAX-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'load_profile' && isset($_POST['profile_id'])) {
    ob_clean();
    $profileId = basename($_POST['profile_id']);
    $profiles = getProfilesData();
    header('Content-Type: application/json');
    if (isset($profiles[$profileId])) {
        echo json_encode($profiles[$profileId], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Profile not found: ' . $profileId]);
    }
    exit;
}

// Загрузка данных профилей из profiles.json
$profiles = getProfilesData();

$default_phone = '(351) 267-90-89';
$default_email = '@susu.ru';

// Значения формы по умолчанию или из POST
$phone_value = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : $default_phone;
$email_value = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : $default_email;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile' && isset($_POST['profile_id'])) {
    $result = handleProfileForm($profiles, $_POST['profile_id'] . '.html');
    if ($result['success']) {
        $success = $result['message'];
        $profiles = getProfilesData(); // Обновляем данные после сохранения
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Редактирование профиля</title>
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

    <h1 style="padding: 0 12px;">Редактирование профиля</h1>
    <div class="preview-container">
        <div class="form-container">
            <form method="post" id="profileForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_profile">
                <label for="profile_id">Выберите профиль:</label>
                <select name="profile_id" id="profile_id" onchange="loadProfileData(this.value)">
                    <option value="">Выберите профиль</option>
                    <?php foreach ($profiles as $profileId => $data): ?>
                        <option value="<?= htmlspecialchars($profileId) ?>"><?= htmlspecialchars($data['fio']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="fio">ФИО*</label>
                <input type="text" id="fio" name="fio" required
                    value="<?= isset($_POST['fio']) ? htmlspecialchars($_POST['fio']) : '' ?>">
                <div class="add-btn-container">
                    <label>Вариации фамилии для поиска публикаций</label>
                    <button type="button" class="add-btn" onclick="addSurnameVariation()">+</button>
                </div>
                <div id="surname-variations-container">
                    <?php if (isset($_POST['surname_variations']) && !empty($_POST['surname_variations'])): ?>
                        <?php foreach ($_POST['surname_variations'] as $index => $variation): ?>
                            <div class="variation">
                                <input type="text" name="surname_variations[]" value="<?= htmlspecialchars($variation) ?>">
                                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="variation">
                            <input type="text" name="surname_variations[]">
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
                                    placeholder="Ссылка на награду (необязательно)">
                                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="award">
                            <input type="text" name="awards[0][name]" placeholder="Название награды">
                            <input type="text" name="awards[0][link]" placeholder="Ссылка на награду (необязательно)">
                            <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                        </div>
                    <?php endif; ?>
                </div>

                <label for="interests">Область научных интересов</label>
                <textarea id="interests" name="interests"
                    rows="5"><?= isset($_POST['interests']) ? htmlspecialchars($_POST['interests']) : '' ?></textarea>

                <input type="submit" value="Сохранить изменения" id="saveProfileBtn" disabled>
                <?php if (isset($error)): ?>
                    <p style="color: red; padding: 0 12px;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <p style="color: green; padding: 0 12px;"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
            </form>
        </div>

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
            const profileSelect = $('#profile_id');

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
                        if (data.username) {
                            updateUI(data.username);
                            toggleSaveButton(); // Проверяем состояние кнопки после авторизации
                        } else {
                            disableSaveProfileButton();
                        }
                    }
                });
            }

            function updateUI(username) {
                loginBtn.css('display', 'none');
                userInfo.css('display', 'flex');
                userNameDisplay.text(username);
            }

            function enableSaveProfileButton() {
                saveProfileBtn.prop('disabled', false);
                saveProfileBtn.css('opacity', '1');
            }

            function disableSaveProfileButton() {
                saveProfileBtn.prop('disabled', true);
                saveProfileBtn.css('opacity', '0.6');
            }

            function toggleSaveButton() {
                const profileId = profileSelect.val();
                if (profileId && profileId !== '') {
                    enableSaveProfileButton();
                } else {
                    disableSaveProfileButton();
                }
            }

            // Проверяем состояние кнопки при загрузке страницы
            toggleSaveButton();

            // Проверяем состояние кнопки при изменении профиля
            profileSelect.on('change', function () {
                loadProfileData(this.value);
                toggleSaveButton();
            });

            // Предотвращаем отправку формы, если профиль не выбран
            $('#profileForm').on('submit', function (e) {
                const profileId = profileSelect.val();
                if (!profileId || profileId === '') {
                    e.preventDefault();
                    alert('Пожалуйста, выберите профиль перед сохранением.');
                }
            });

            checkAuth();
        });

        function addSurnameVariation() {
            const container = document.getElementById('surname-variations-container');
            const div = document.createElement('div');
            div.classList.add('variation');
            div.innerHTML = `
                <input type="text" name="surname_variations[]" placeholder="Вариация фамилии">
                <button type="button" class="remove-btn" onclick="this.parentNode.remove(); updatePreview();">-</button>
            `;
            container.appendChild(div);
            updatePreview();
        }

        function addAward() {
            const container = document.getElementById('awards-container');
            const index = container.querySelectorAll('.award').length;
            const div = document.createElement('div');
            div.className = 'award';
            div.innerHTML = `
        <input type="text" name="awards[${index}][name]" placeholder="Название награды">
        <input type="text" name="awards[${index}][link]" placeholder="Ссылка на награду (необязательно)">
        <button type="button" class="remove-btn" onclick="this.parentNode.remove(); updatePreview();">-</button>
    `;
            container.appendChild(div);
            updatePreview();
        }

        function removeAward(btn) {
            btn.parentNode.remove();
            updatePreview();
        }

        function loadProfileData(profileId) {
            if (!profileId) {
                resetForm();
                updatePreview();
                return;
            }
            $.ajax({
                url: 'edit_profile.php',
                method: 'POST',
                dataType: 'json',
                data: { action: 'load_profile', profile_id: profileId },
                success: function (data) {
                    console.log('Parsed profile data:', data);
                    if (data.error) {
                        console.error('Server error:', data.error);
                        return;
                    }

                    // Обновление простых полей
                    const fields = {
                        'fio': data.fio || '',
                        'position': data.position || '',
                        'degree': data.degree || '',
                        'phone': data.phone || '',
                        'email': data.email || '',
                        'interests': data.interests || '',
                        'photo-width': data.photo_width || '250',
                        'photo-height': data.photo_height || '250'
                    };

                    for (const [id, value] of Object.entries(fields)) {
                        const element = document.getElementById(id);
                        if (element) {
                            if (element.tagName.toLowerCase() === 'textarea') {
                                element.textContent = value;
                                element.value = value;
                            } else {
                                element.value = value;
                            }
                            console.log(`Set ${id} to:`, value, 'Actual value:', element.value);
                        } else {
                            console.error(`Element with ID ${id} not found`);
                        }
                    }

                    // Обновление слайдеров
                    const widthSlider = document.getElementById('photo-width');
                    const heightSlider = document.getElementById('photo-height');
                    if (widthSlider) widthSlider.dispatchEvent(new Event('input'));
                    if (heightSlider) heightSlider.dispatchEvent(new Event('input'));

                    // Обновление текстовых индикаторов для слайдеров
                    const widthValue = document.getElementById('width-value');
                    const heightValue = document.getElementById('height-value');
                    if (widthValue) {
                        widthValue.textContent = data.photo_width || '250';
                        console.log('Set width-value to:', data.photo_width || '250', 'Actual text:', widthValue.textContent);
                    }
                    if (heightValue) {
                        heightValue.textContent = data.photo_height || '250';
                        console.log('Set height-value to:', data.photo_height || '250', 'Actual text:', heightValue.textContent);
                    }

                    // Обработка вариаций фамилии
                    const variationsContainer = document.getElementById('surname-variations-container');
                    if (variationsContainer) {
                        variationsContainer.innerHTML = '';
                        if (Array.isArray(data.surname_variations) && data.surname_variations.length > 0) {
                            data.surname_variations.forEach((variation, index) => {
                                const div = document.createElement('div');
                                div.classList.add('variation');
                                const escapedVariation = variation.replace(/"/g, '"').replace(/</g, '<').replace(/>/g, '>');
                                const isLast = index === data.surname_variations.length - 1;
                                div.innerHTML = `
                                    <input type="text" name="surname_variations[]" value="${escapedVariation}" placeholder="Вариация фамилии">
                                    <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                                `;
                                variationsContainer.appendChild(div);
                                console.log(`Added surname variation ${index}:`, variation);
                            });
                        } else {
                            variationsContainer.innerHTML = `
                                <div class="variation">
                                    <input type="text" name="surname_variations[]" placeholder="Вариация фамилии">
                                    <button type="button" class="add-btn" onclick="addSurnameVariation()">+</button>
                                </div>
                            `;
                            console.log('Set default surname variation');
                        }
                    } else {
                        console.error('surname-variations-container not found');
                    }

                    // Обработка наград
                    const awardsContainer = document.getElementById('awards-container');
                    if (awardsContainer) {
                        awardsContainer.innerHTML = '';
                        if (Array.isArray(data.awards) && data.awards.length > 0) {
                            data.awards.forEach((award, index) => {
                                const div = document.createElement('div');
                                div.classList.add('award');
                                const escapedName = (award.name || '').replace(/"/g, '"').replace(/</g, '<').replace(/>/g, '>');
                                const escapedLink = (award.link || '').replace(/"/g, '"').replace(/</g, '<').replace(/>/g, '>');
                                div.innerHTML = `
                                    <input type="text" name="awards[${index}][name]" value="${escapedName}" placeholder="Название награды">
                                    <input type="text" name="awards[${index}][link]" value="${escapedLink}" placeholder="Ссылка на награду (необязательно)">
                                    <button type="button" class="remove-btn" onclick="this.parentNode.remove(); updatePreview();">-</button>
                                `;
                                awardsContainer.appendChild(div);
                                console.log(`Added award ${index}:`, award);
                            });
                        } else {
                            awardsContainer.innerHTML = `
                                <div class="award">
                                    <input type="text" name="awards[0][name]" placeholder="Название награды">
                                    <input type="text" name="awards[0][link]" placeholder="Ссылка на награду (необязательно)">
                                    <button type="button" class="add-btn" onclick="addAward()">+</button>
                                </div>
                            `;
                            console.log('Set default award');
                        }
                    } else {
                        console.error('awards-container not found');
                    }

                    updatePreview();
                    console.log('Preview updated');
                },
                error: function (xhr, status, error) {
                    console.error('Error loading profile data:', status, error, 'Response:', xhr.responseText);
                }
            });
        }

        function resetForm() {
            document.getElementById('profileForm').reset();
            document.getElementById('photo-width').value = '250';
            document.getElementById('photo-height').value = '250';
            document.getElementById('width-value').textContent = '250';
            document.getElementById('height-value').textContent = '250';
            const variationsContainer = document.getElementById('surname-variations-container');
            variationsContainer.innerHTML = `
                <div class="variation">
                    <input type="text" name="surname_variations[]" placeholder="Вариация фамилии">
                    <button type="button" class="add-btn" onclick="addSurnameVariation()">+</button>
                </div>
            `;
            const awardsContainer = document.getElementById('awards-container');
            awardsContainer.innerHTML = `
                <div class="award">
                    <input type="text" name="awards[0][name]" placeholder="Название награды">
                    <input type="text" name="awards[0][link]" placeholder="Ссылка на награду (необязательно)">
                    <button type="button" class="add-btn" onclick="addAward()">+</button>
                </div>
            `;
            // Отключаем кнопку сохранения при сбросе формы
            document.getElementById('saveProfileBtn').disabled = true;
            document.getElementById('saveProfileBtn').style.opacity = '0.6';
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

            document.getElementById('width-value').textContent = photoWidth;
            document.getElementById('height-value').textContent = photoHeight;

            let photoUrl = photo ? URL.createObjectURL(photo) : '';

            let degreeHtml = degree ? `<p><strong>Ученая степень:</strong> ${degree}</p>` : '';
            let phoneHtml = phone ? `<p><strong>Телефон:</strong> ${phone}</p>` : '';
            let awardsHtml = '';
            let interestsHtml = interests ? `<p><strong>Область научных интересов:</strong> ${interests}</p>` : '';
            let publicationsHtml = '<div class="publications-section"><h3>Публикации</h3><p>Публикации не загружены</p></div>';
            let headline = `<h1>${position} ${fio}</h1>`;

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

            // Добавляем вариации фамилии в предпросмотр
            const surnameVariations = Array.from(document.querySelectorAll('#surname-variations-container input')).map(input => input.value).filter(v => v);
            let variationsHtml = '';
            if (surnameVariations.length > 0) {
                variationsHtml = `<p><strong>Вариации фамилии:</strong> ${surnameVariations.join(', ')}</p>`;
            }

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

        document.getElementById('profileForm').addEventListener('input', updatePreview);
        document.getElementById('photo').addEventListener('change', updatePreview);
        document.getElementById('photo-width').addEventListener('input', updatePreview);
        document.getElementById('photo-height').addEventListener('input', updatePreview);
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