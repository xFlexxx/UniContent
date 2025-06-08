<?php
session_start();
require_once 'newsGenerator.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/output/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $imageUrl = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadFile = $uploadDir . '/' . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
            $imageUrl = 'uploads/' . basename($_FILES['file']['name']);
        } else {
            $error = "Ошибка загрузки файла.";
        }
    }

    if (!isset($error)) {
        $date = DateTime::createFromFormat('Y-m-d', $_POST['date']);
        $formattedDate = $date ? $date->format('d.m.Y') : '';

        $newNews = [
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'date' => $formattedDate,
            'image' => $imageUrl ?: ($_POST['image'] ?? ''),
            'link' => $_POST['link'] ?? '',
            'is_new' => true,
            'imageAttributes' => !empty($imageUrl) ? "style=\"width: {$_POST['image_width']}px; height: {$_POST['image_height']}px; object-fit: contain;\"" : ''
        ];

        $outputDir = __DIR__ . '/output';
        $parseDir = __DIR__ . '/output';
        $allNews = fetchNewsFromCombined($parseDir);
        $allNews[] = $newNews;
        $sortedNews = sortNewsByDate($allNews);

        $templateFile = __DIR__ . '/template_news.html';
        $customTemplate = $_POST['newsTemplate'] ?? null;
        generateNewsPages($templateFile, $sortedNews, $outputDir, $customTemplate);

        // Обновляем susu.php
        $susuIndexFile = __DIR__ . '/susuIndex.php';
        $susuOutputFile = __DIR__ . '/output/susu.html';

        if (file_exists($susuIndexFile)) {
            $latestNews = array_slice($sortedNews, 0, 4);
            $newsHtml1 = '';
            for ($i = 0; $i < 2 && $i < count($latestNews); $i++) {
                $newsItem = $latestNews[$i];
                $imageHtml = !empty($newsItem['image']) ?
                    "<div style=\"flex: 0 0 auto; margin: 10px;\"><img alt=\"\" src=\"{$newsItem['image']}\" {$newsItem['imageAttributes']} /></div>" : '';
                $linkHtml = !empty($newsItem['link']) ?
                    "<div class='link-fullnews'><a href='{$newsItem['link']}'>Подробнее...</a></div>" : '';

                $newsHtml1 .= <<<HTML
<td class="left-colomn">
    <div class="news-date">[{$newsItem['date']}]</div>
    <h1 class="news">{$newsItem['title']}</h1>
    <div class="preview" style="display: flex; align-items: flex-start;">
        {$imageHtml}
        <div style="flex: 1; overflow: hidden;">
            <p style="margin: 0;">{$newsItem['content']}</p>
            {$linkHtml}
        </div>
    </div>
</td>
HTML;
            }

            $newsHtml2 = '';
            for ($i = 2; $i < 4 && $i < count($latestNews); $i++) {
                $newsItem = $latestNews[$i];
                $imageHtml = !empty($newsItem['image']) ?
                    "<div style=\"flex: 0 0 auto; margin: 10px;\"><img alt=\"\" src=\"{$newsItem['image']}\" {$newsItem['imageAttributes']} /></div>" : '';
                $linkHtml = !empty($newsItem['link']) ?
                    "<div class='link-fullnews'><a href='{$newsItem['link']}'>Подробнее...</a></div>" : '';

                $newsHtml2 .= <<<HTML
<td class="left-colomn">
    <div class="news-date">[{$newsItem['date']}]</div>
    <h1 class="news">{$newsItem['title']}</h1>
    <div class="preview" style="display: flex; align-items: flex-start;">
        {$imageHtml}
        <div style="flex: 1; overflow: hidden;">
            <p style="margin: 0;">{$newsItem['content']}</p>
            {$linkHtml}
        </div>
    </div>
</td>
HTML;
            }

            $templateContent = file_get_contents($susuIndexFile);
            $outputContent = str_replace('<!-- NewsPlaceHolder1 -->', $newsHtml1, $templateContent);
            $outputContent = str_replace('<!-- NewsPlaceHolder2 -->', $newsHtml2, $outputContent);
            file_put_contents($susuOutputFile, $outputContent);

            $success = "Новость успешно создана.";
        } else {
            $error = "Шаблон susuIndex.php не найден.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Добавить новость</title>
    <link rel="stylesheet" href="main.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2&display=swap" rel="stylesheet">
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
    <h1 style="padding: 0 12px;">Добавить новую новость</h1>
    <div class="preview-container">
        <!-- Левая часть: форма -->
        <div class="form-container">
            <form method="post" enctype="multipart/form-data" id="newsForm">
                <label for="title">Заголовок:</label>
                <input type="text" id="title" name="title" required>

                <label for="content">Содержание:</label>
                <textarea id="content" name="content" rows="5" required></textarea>

                <label for="date">Дата:</label>
                <input type="date" id="date" name="date" required>

                <label for="file">Загрузить изображение с компьютера:</label>
                <input type="file" id="file" name="file" accept="image/*">

                <!-- Ползунки для регулировки размера изображения -->
                <div class="slider-container">
                    <label for="image-width">Ширина изображения (px): <span id="width-value">200</span></label>
                    <input type="range" id="image-width" name="image_width" min="50" max="500" value="200" step="10">
                </div>
                <div class="slider-container">
                    <label for="image-height">Высота изображения (px): <span id="height-value">200</span></label>
                    <input type="range" id="image-height" name="image_height" min="50" max="500" value="200" step="10">
                </div>

                <label for="link">Ссылка "Подробнее":</label>
                <input type="text" id="link" name="link">
                <label for="link"></label>
                <input type="submit" value="Добавить новость" id="addNewsBtn" disabled>
            </form>
        </div>

        <!-- Правая часть: предпросмотр -->
        <div class="code-container">
            <label for="previewOutput">Предпросмотр новости:</label>
            <div id="previewOutput" class="preview"></div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <p style="color: green; padding: 0 12px;"><?php echo htmlspecialchars($success); ?></p>
    <?php elseif (isset($error)): ?>
        <p style="color: red; padding: 0 12px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <script>
        // Логика авторизации
        $(document).ready(function () {
            const loginBtn = $('#loginBtn');
            const loginModal = $('#loginModal');
            const closeLoginModal = $('#closeLoginModal');
            const loginForm = $('#loginForm');
            const errorMessage = $('#errorMessage');
            const userInfo = $('#userInfo');
            const userNameDisplay = $('#userName');
            const logoutBtn = $('#logoutBtn');
            const addNewsBtn = $('#addNewsBtn');

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
                        disableAddNewsButton();
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
                        else disableAddNewsButton();
                    }
                });
            }

            function updateUI(username) {
                loginBtn.css('display', 'none');
                userInfo.css('display', 'flex');
                userNameDisplay.text(username);
                enableAddNewsButton();
            }

            function enableAddNewsButton() {
                addNewsBtn.prop('disabled', false);
                addNewsBtn.css('opacity', '1');
            }

            function disableAddNewsButton() {
                addNewsBtn.prop('disabled', true);
                addNewsBtn.css('opacity', '0.6');
            }

            checkAuth();
        });

        // Функция для обновления предпросмотра
        function updatePreview() {
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            const date = document.getElementById('date').value;
            const link = document.getElementById('link').value;
            const image = document.getElementById('file').files[0];
            const imageWidth = document.getElementById('image-width').value;
            const imageHeight = document.getElementById('image-height').value;

            document.getElementById('width-value').textContent = imageWidth;
            document.getElementById('height-value').textContent = imageHeight;

            let imageUrl = '';
            if (image) {
                imageUrl = URL.createObjectURL(image);
            }

            let htmlCode = `
                <div class="news-date">[${date}]</div>
                <table style="text-align:left; width:100%;">
                    <tr><td style="border-style: none !important; text-align: left;">
                        <h1 class="news">${title}</h1>
                    </td></tr>
                    <tr><td style="border-style: none !important;">
                        ${imageUrl ? `
                            <div style="float: left; margin: 10px;">
                                <img src="${imageUrl}" alt="News image" style="width: ${imageWidth}px; height: ${imageHeight}px; object-fit: contain;" />
                            </div>` : ''
                }
                        <div style="overflow: hidden;">
                            <p>${content}</p>
                        </div>
                    </td></tr>
                    <tr><td style="border-style: none !important;">
                        ${link ? `<div class="link-fullnews"><a href="${link}">Подробнее...</a></div>` : ''}
                    </td></tr>
                </table>
                <hr/>
            `;

            document.getElementById('previewOutput').innerHTML = htmlCode;
        }

        // Обновляем предпросмотр при изменении данных в форме
        document.getElementById('newsForm').addEventListener('input', updatePreview);
        document.getElementById('file').addEventListener('change', updatePreview);
        document.getElementById('image-width').addEventListener('input', updatePreview);
        document.getElementById('image-height').addEventListener('input', updatePreview);

        // Инициализация предпросмотра
        updatePreview();

        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;

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

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
        });

        themeToggle.addEventListener('click', () => {
            const newTheme = themeToggle.checked ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    </script>
</body>

</html>