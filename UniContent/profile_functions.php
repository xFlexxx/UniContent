<?php

// Путь к файлу с данными профилей
const PROFILES_FILE = __DIR__ . '/profiles.json';
const UPLOAD_DIR = __DIR__ . '/profiles_html/uploads/profiles/';
const HTML_DIR = __DIR__ . '/profiles_html/';

// Функция для получения данных профилей
function getProfilesData()
{
    if (!file_exists(PROFILES_FILE)) {
        file_put_contents(PROFILES_FILE, json_encode([]));
        return [];
    }

    $jsonData = file_get_contents(PROFILES_FILE);
    return json_decode($jsonData, true) ?: [];
}

// Функция для поиска публикаций
function findPublications($searchNames, $papersUrl = 'http://sp.susu.ru/science/papers.html')
{
    if (empty($searchNames)) {
        return '<p>Публикации не найдены.</p>';
    }

    // Подготовка регулярного выражения для точного поиска фамилии как слова
    $namePatterns = array_map(function ($name) {
        // Экранируем символы и добавляем границы слова
        return '\b' . preg_quote(trim($name), '/') . '\b';
    }, $searchNames);

    // Регулярное выражение для поиска фамилии как отдельного слова
    $nameRegex = '/' . implode('|', $namePatterns) . '/ui';

    // Загрузка страницы с публикациями
    $htmlContent = @file_get_contents($papersUrl);
    if ($htmlContent === false) {
        return '<p>Не удалось загрузить страницу с публикациями.</p>';
    }

    // Парсинг HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($htmlContent);
    libxml_clear_errors();

    $output = '<ol>';
    $found = false;

    // Поиск элементов <li> (публикаций)
    $lis = $dom->getElementsByTagName('li');
    foreach ($lis as $li) {
        // Получение текста публикации
        $innerHTML = '';
        foreach ($li->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }

        // Декодирование и очистка текста
        $textContent = html_entity_decode(strip_tags($innerHTML));

        // Проверка наличия фамилии как отдельного слова
        if (preg_match($nameRegex, $textContent)) {
            // Очистка HTML, сохранение ссылок и форматирования
            $cleanedContent = strip_tags($innerHTML, '<a><strong><em><b><i>');
            $cleanedContent = preg_replace('/<(\w+)[^>]*>\s*<\/\1>/', '', $cleanedContent);
            $cleanedContent = preg_replace('/\s+/', ' ', $cleanedContent);

            $output .= '<li>' . trim($cleanedContent) . '</li>';
            $found = true;
        }
    }

    $output .= '</ol>';
    return $found ? $output : '<p>Публикации не найдены.</p>';
}

// Функция для извлечения данных из HTML профиля
function getProfileData($filePath)
{
    if (!file_exists($filePath)) {
        return [];
    }
    $html = file_get_contents($filePath);
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $data = [];
    $data['fio'] = ($xpath->query('//h2')->item(0)) ? $xpath->query('//h2')->item(0)->textContent : '';
    $data['position'] = ($xpath->query('//p[strong[text()="Должность:"]]')->item(0)) ?
        trim(str_replace('Должность:', '', $xpath->query('//p[strong[text()="Должность:"]]')->item(0)->textContent)) : '';
    $data['degree'] = ($xpath->query('//p[strong[text()="Ученая степень:"]]')->item(0)) ?
        trim(str_replace('Ученая степень:', '', $xpath->query('//p[strong[text()="Ученая степень:"]]')->item(0)->textContent)) : '';
    $data['phone'] = ($xpath->query('//p[strong[text()="Телефон:"]]')->item(0)) ?
        trim(str_replace('Телефон:', '', $xpath->query('//p[strong[text()="Телефон:"]]')->item(0)->textContent)) : '';
    $data['email'] = ($xpath->query('//p[strong[text()="Email:"]]/a')->item(0)) ?
        $xpath->query('//p[strong[text()="Email:"]]/a')->item(0)->textContent : '';
    $data['interests'] = ($xpath->query('//p[strong[text()="Область научных интересов:"]]')->item(0)) ?
        trim(str_replace('Область научных интересов:', '', $xpath->query('//p[strong[text()="Область научных интересов:"]]')->item(0)->textContent)) : '';

    $awards = [];
    $awardNodes = $xpath->query('//ul[@class="awards-list"]/li');
    foreach ($awardNodes as $index => $node) {
        $link = $xpath->query('.//a', $node)->item(0);
        $awards[] = [
            'name' => $link ? $link->textContent : $node->textContent,
            'link' => $link ? $link->getAttribute('href') : ''
        ];
    }
    $data['awards'] = $awards;

    $data['surname_variations'] = [];

    $photoNode = $xpath->query('//div[@class="profile-photo"]/img')->item(0);
    $data['photo'] = $photoNode ? $photoNode->getAttribute('src') : '';
    $data['photo_width'] = $photoNode ? $photoNode->getAttribute('width') : '250';
    $data['photo_height'] = $photoNode ? $photoNode->getAttribute('height') : '250';

    return $data;
}

// Функция для генерации HTML страницы профиля с использованием шаблона
function generateProfileHtml($profileId, $profileData)
{
    if (!is_dir(HTML_DIR)) {
        mkdir(HTML_DIR, 0755, true);
    }

    $htmlFilePath = HTML_DIR . $profileId . '.html';
    $templatePath = __DIR__ . '/template_profile.html';

    if (!file_exists($templatePath)) {
        return false;
    }

    $fio = htmlspecialchars($profileData['fio'] ?? '');
    $email = htmlspecialchars($profileData['email'] ?? '');
    $phone = htmlspecialchars($profileData['phone'] ?? '');
    $position = htmlspecialchars($profileData['position'] ?? '');
    $degree = htmlspecialchars($profileData['degree'] ?? '');
    $interests = htmlspecialchars($profileData['interests'] ?? '');
    $photo = isset($profileData['photo']) && $profileData['photo'] ? htmlspecialchars($profileData['photo']) : 'images/placeholder.jpg';
    $photoWidth = isset($profileData['photo_width']) ? (int)$profileData['photo_width'] : 250;
    $photoHeight = isset($profileData['photo_height']) ? (int)$profileData['photo_height'] : 250;
    $awards = $profileData['awards'] ?? [];

    // Генерация HTML контента профиля
    $awardsHtml = '';
    if (!empty($awards)) {
        $awardsHtml = '<p><strong>Достижения, поощрения и награды:</strong></p><ul class="awards-list">';
        foreach ($awards as $award) {
            $awardName = htmlspecialchars($award['name'] ?? '');
            $awardLink = !empty($award['link']) ? htmlspecialchars($award['link']) : '';
            $awardsHtml .= $awardLink ? "<li><a href=\"$awardLink\" target=\"_blank\">$awardName</a></li>" : "<li>$awardName</li>";
        }
        $awardsHtml .= '</ul>';
    }

    $interestsHtml = $interests ? "<p><strong>Область научных интересов:</strong> $interests</p>" : '';
    $degreeHtml = $degree ? "<p><strong>Ученая степень:</strong> $degree</p>" : '';
    $phoneHtml = $phone ? "<p><strong>Телефон:</strong> $phone</p>" : '';

    $publicationsHtml = '';
    if (!empty($profileData['surname_variations'])) {
        $publications = findPublications($profileData['surname_variations']);
        $publicationsHtml = '<div class="publications-section"><h3>Научные публикации</h3>' . $publications . '</div>';
    } else {
        $publicationsHtml = '<div class="publications-section"><h3>Научные публикации</h3><p>Публикации не найдены.</p></div>';
    }

    // Генерация содержимого профиля
    $profileContent = <<<HTML
<DIV class="tpl-headline"><IMG src="./../_images/marker_headline.gif" class="tpl-marker-headline"> $position кафедры системного программирования $fio</div>
<div class="profile-container">
    <div class="profile-photo">
        <img src="$photo" width="$photoWidth" height="$photoHeight" alt="$fio">
    </div>
    <div class="profile-info">
        <p><strong>Должность:</strong> $position</p>
        $degreeHtml
        $phoneHtml
        <p><strong>Email:</strong> <a href="mailto:$email">$email</a></p>
        $awardsHtml
    </div>
</div>
<hr>
$interestsHtml
<hr>
$publicationsHtml
<hr>
HTML;

    // Загрузка шаблона и замена плейсхолдера
    $templateContent = file_get_contents($templatePath);
    $fullHtml = str_replace('<!-- PROFILE_PLACEHOLDER -->', $profileContent, $templateContent);

    // Сохранение результата
    $result = file_put_contents($htmlFilePath, $fullHtml);
    return $result !== false;
}

// Функция для обработки формы профиля
function handleProfileForm($profiles, $profileFile = null)
{
    if (!isset($_SESSION['username'])) {
        return ['success' => false, 'message' => 'Требуется авторизация'];
    }

    $fio = trim($_POST['fio'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $awardsRaw = $_POST['awards'] ?? [];
    $awards = [];
    $interests = trim($_POST['interests'] ?? '');
    $surnameVariations = array_filter(array_map('trim', $_POST['surname_variations'] ?? []));

    // Проверка на существование профиля
    $profileId = $profileFile ? pathinfo($profileFile, PATHINFO_FILENAME) : strtolower(str_replace(' ', '_', $fio));
    if (!$profileFile && isset($profiles[$profileId])) {
        return ['success' => false, 'message' => 'Профиль с таким ФИО уже существует'];
    }

    foreach ($awardsRaw as $award) {
        $name = trim($award['name'] ?? '');
        $link = trim($award['link'] ?? '');

        if ($name === '') {
            continue;
        }

        // Проверяем, является ли ссылка относительным путем
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) {
            // Если не URL, проверяем, может это относительный путь
            if (!preg_match('/^[a-zA-Z0-9\/\.\-_]+$/', $link)) {
                return ['success' => false, 'message' => "Некорректная ссылка или путь для награды: $name"];
            }
            
            // Если это относительный путь, добавляем базовый путь
            $link = ltrim($link, '/');
        }

        $awards[] = [
            'name' => $name,
            'link' => $link ?: null
        ];
    }

    if (empty($fio)) {
        return ['success' => false, 'message' => 'Поле ФИО обязательно для заполнения'];
    }
    if (empty($position)) {
        return ['success' => false, 'message' => 'Поле Должность обязательно для заполнения'];
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Введите корректный email'];
    }

    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileMimeType = $_FILES['photo']['type'];
        $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Допустимы только изображения JPEG, PNG или GIF'];
        }

        if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Размер изображения не должен превышать 5 МБ'];
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $uniqueFileName = uniqid('profile_') . '.' . $fileExtension;
        $destination = UPLOAD_DIR . $uniqueFileName;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Ошибка при загрузке изображения'];
        }

        $photoPath = 'uploads/profiles/' . $uniqueFileName;
    }

    if ($profileFile && file_exists(HTML_DIR . $profileFile)) {
        $existingData = getProfileData(HTML_DIR . $profileFile);
        if (!$photoPath && isset($existingData['photo'])) {
            $photoPath = $existingData['photo'];
        }
    }

    $profileData = [
        'fio' => $fio,
        'surname_variations' => $surnameVariations,
        'position' => $position,
        'degree' => $degree,
        'phone' => $phone,
        'email' => $email,
        'awards' => $awards,
        'interests' => $interests,
        'photo_width' => $_POST['photo_width'] ?? 250,
        'photo_height' => $_POST['photo_height'] ?? 250,
        'last_updated' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['username']
    ];

    if ($photoPath) {
        $profileData['photo'] = $photoPath;
    } elseif (isset($profiles[$profileId]['photo'])) {
        $profileData['photo'] = $profiles[$profileId]['photo'];
    }

    $profiles[$profileId] = $profileData;

    $jsonResult = file_put_contents(PROFILES_FILE, json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($jsonResult === false) {
        return ['success' => false, 'message' => 'Ошибка при сохранении данных JSON'];
    }

    $htmlResult = generateProfileHtml($profileId, $profileData);

    if (!$htmlResult) {
        return ['success' => false, 'message' => 'Ошибка при создании HTML страницы'];
    }
    
    return ['success' => true, 'message' => 'Профиль успешно создан'];
}
?>