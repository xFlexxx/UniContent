<?php
session_start();
require_once 'publication_functions.php';

$authorsList = [
    "Абдуллаев С.М.", "S.M. Abdullaev", "Аботалеб М.С.А.", "Abotaleb M.", "Алаасам А.Б.А.", "Alaasam A.",
    "Алеев Р.Ж.", "Aleev R.Z", "Алеева В.Н.", "Aleeva V.N", "Ан Ф.Т.", "Ахмад Т.", "T Ahmad",
    "Бастрыкина К.В.", "Bastrykina K.V", "Блинова Е.М.", "Blinova E.M.", "Валиулин А.А.", "Valiulin A.A.",
    "Варкентин В.В.", "Varkentin V.V.", "Верман П.Г.", "Verman P.G.", "Володченко И.Д.", "Volodchenko I.D.",
    "Геренштейн А.В.", "Gerenshtein A.V.", "Геренштейн Р.М.", "Gerenshtein R.M.", "Гисс Е.И.", "Giss E.I.",
    "Глизница М.Н.", "Gliznitsa M.N.", "Гоглачев А.И.", "Goglachev A.I.", "Долганина Н.Ю.", "Dolganina N.I.",
    "Жулев А.Э.", "Zhulev A.E.", "Иванов С.А.", "Ivanov S.A.", "Иванова Е.В.", "Ivanova E.V.",
    "Иванова О.Н.", "Ivanova O.N.", "Клебанов И.И.", "Klebanov I.I.", "Краева Я.А.", "Kraeva Y.A.",
    "Латипова А.Т.", "Latipova A.T.", "Макаровских Т.А.", "Makarovskikh T.A.", "Маковецкая Т.Ю.", "Makovetskaya T.Y.",
    "Манатин П.А.", "Manatin P.A.", "Марков Б.А.", "Markov B.A.", "Никольская К.Ю.", "Nikolskaia K.",
    "Ольховский Н.А.", "Olkhovskii N.A.", "Панюков А.В.", "Paniukov A.V.", "Петрова Л.Н.", "Petrova L.N.",
    "Пушкарева М.В.", "Pushkareva M.V.", "Радченко Г.И.", "Radchenko G.I.", "Сахарова А.А.", "Sakharova A.A.",
    "Силкина Н.С.", "Silkina N.S.", "Соколинский Л.Б.", "Sokolinsky L.B.", "Соколов М.П.", "Sokolov M.P.",
    "Старков А.Е.", "Starkov A.E.", "Сухов М.В.", "Sukhov M.", "Турлакова С.У.", "Turlakova S.U.",
    "Фомина А.А.", "Fomina A.A.", "Цымблер М.Л.", "Zymbler M.", "Ческидов П.Д.", "Cheskidov P.D.",
    "Шабанов Т.Ю.", "Shabanov T.Y.", "Юртин А.А.", "Iurtin A.A."
];

// Сформируем options для JS
$authorsOptions = '';
foreach ($authorsList as $author) {
    $authorsOptions .= '<option value="' . htmlspecialchars($author) . '">' . htmlspecialchars($author) . '</option>';
}

// Загрузка данных о публикациях
$filePath = __DIR__ . '/papers.html';
$yearsSections = getPublicationsData($filePath);

// Загрузка публикаций
$pubData = [];
foreach ($yearsSections as $year => $sections) {
    $pubData[$year] = [];
    foreach ($sections as $section) {
        $pubData[$year][$section] = [];
        $dom = loadDom($filePath);
        $xpath = new DOMXPath($dom);
        $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
        if ($yearNode) {
            $current = $yearNode->parentNode->nextSibling;
            while ($current && $current->nodeName !== 'h1') {
                if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
                    $emNode = $xpath->query(".//em", $current)->item(0);
                    if ($emNode && trim($emNode->textContent) === $section) {
                        $next = $current->nextSibling;
                        while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h1') {
                            if ($next->nodeName === 'ol') {
                                foreach ($next->getElementsByTagName('li') as $li) {
                                    $pubData[$year][$section][] = trim($li->textContent);
                                }
                                break;
                            }
                            $next = $next->nextSibling;
                        }
                        break;
                    }
                }
                $current = $current->nextSibling;
            }
        }
    }
}

// Обработка всех форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_publication':
                $result = handlePublicationForm($filePath, $yearsSections);
                break;
            case 'add_year':
                $result = handleAddYear($filePath, $_POST['new_year']);
                break;
            case 'add_section':
                $result = handleAddSection($filePath, $_POST['year'], $_POST['new_section']);
                break;
            case 'delete_year':
                $result = handleDeleteYear($filePath, $_POST['delete_year']);
                break;
            case 'delete_section':
                $result = handleDeleteSection($filePath, $_POST['delete_year'], $_POST['delete_section']);
                break;
            case 'delete_publication':
                $result = handleDeletePublication($filePath, $_POST['delete_year'], $_POST['delete_section'], $_POST['delete_publication']);
                break;
            default:
                $result = ['success' => false, 'message' => 'Неизвестное действие'];
                break;
        }
    } else {
        // Для обратной совместимости с формой добавления публикации
        $result = handlePublicationForm($filePath, $yearsSections);
    }

    if ($result['success']) {
        $success = $result['message'];
        // Обновляем данные после успешного действия
        $yearsSections = getPublicationsData($filePath);
        // Обновляем данные публикаций
        $pubData = [];
        foreach ($yearsSections as $year => $sections) {
            $pubData[$year] = [];
            foreach ($sections as $section) {
                $pubData[$year][$section] = [];
                $dom = loadDom($filePath);
                $xpath = new DOMXPath($dom);
                $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
                if ($yearNode) {
                    $current = $yearNode->parentNode->nextSibling;
                    while ($current && $current->nodeName !== 'h1') {
                        if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
                            $emNode = $xpath->query(".//em", $current)->item(0);
                            if ($emNode && trim($emNode->textContent) === $section) {
                                $next = $current->nextSibling;
                                while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h1') {
                                    if ($next->nodeName === 'ol') {
                                        foreach ($next->getElementsByTagName('li') as $li) {
                                            $pubData[$year][$section][] = trim($li->textContent);
                                        }
                                        break;
                                    }
                                    $next = $next->nextSibling;
                                }
                                break;
                            }
                        }
                        $current = $current->nextSibling;
                    }
                }
            }
        }
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Научные публикации</title>
    <link rel="stylesheet" href="main.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2&display=swap" rel="stylesheet">
</head>
<body class="light-theme">
    <!-- Header -->
    <header>
        <a href="index.php" style="color:white"><h1>UniContent</h1></a>
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
    <div class="preview-container">
        <!-- Левая часть: форма -->
        <div class="form-container">
            <!-- Управление годами и разделами -->
            <div class="management-section-columns">
                <!-- Колонка создания -->
                <div class="management-column">
                    <h3>Добавление публикации</h3>
                    <form method="post" id="publicationForm">
                        <input type="hidden" name="action" value="add_publication">
                        <label for="year">Год публикации*</label>
                        <select name="year" id="year-select" required>
                            <option value="">Выберите год</option>
                            <?php foreach ($yearsSections as $year => $sections): ?>
                                <option value="<?= htmlspecialchars($year) ?>" <?= isset($_POST['year']) && $_POST['year'] == $year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="section">Раздел*</label>
                        <select name="section" id="section-select" required>
                            <option value="">Сначала выберите год</option>
                            <?php if (isset($_POST['year']) && isset($yearsSections[$_POST['year']])): ?>
                                <?php
                                $sections = array_unique($yearsSections[$_POST['year']]);
                                foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section) ?>" <?= isset($_POST['section']) && $_POST['section'] == $section ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($section) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <label>Авторы*</label>
                        <div id="authors-container">
                            <?php if (isset($_POST['authors'])): ?>
                                <?php foreach ($_POST['authors'] as $index => $author): ?>
                                    <div class="author">
                                        <select name="authors[]">
                                            <option value="">Выберите автора</option>
                                            <?php foreach ($authorsList as $item): ?>
                                                <option value="<?= htmlspecialchars($item) ?>" <?= ($author === $item) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($item) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="custom" <?= (!in_array($author, $authorsList)) ? 'selected' : '' ?>>
                                                Другой...
                                            </option>
                                        </select>
                                        <input type="text" name="custom_authors[]" placeholder="Введите автора"
                                            value="<?= (!in_array($author, $authorsList)) ? htmlspecialchars($author) : '' ?>"
                                            style="<?= (!in_array($author, $authorsList)) ? 'display:block;' : 'display:none;' ?>">
                                        <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="author">
                                    <select name="authors[]">
                                        <option value="">Выберите автора</option>
                                        <?php foreach ($authorsList as $author): ?>
                                            <option value="<?= htmlspecialchars($author) ?>"><?= htmlspecialchars($author) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="custom">Другой...</option>
                                    </select>
                                    <input type="text" name="custom_authors[]" placeholder="Введите автора" style="display:none;">
                                    <button type="button" class="add-btn" onclick="addAuthor()">+</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <label for="title">Название статьи*</label>
                        <input type="text" name="title" required placeholder="Название статьи"
                            value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">

                        <label for="source">Источник*</label>
                        <input type="text" name="source" required placeholder="Журнал, конференция и пр."
                            value="<?= isset($_POST['source']) ? htmlspecialchars($_POST['source']) : '' ?>">

                        <label for="doi">DOI</label>
                        <input type="text" name="doi" placeholder="10.1134/S1995080224605745"
                            value="<?= isset($_POST['doi']) ? htmlspecialchars($_POST['doi']) : '' ?>">

                        <label for="link">Ссылка на PDF</label>
                        <input type="url" name="link" placeholder="https://example.com/article.pdf"
                            value="<?= isset($_POST['link']) ? htmlspecialchars($_POST['link']) : '' ?>">

                        <label>Рейтинг издания</label>
                        <div id="ratings-container">
                            <?php if (isset($_POST['ratings'])): ?>
                                <?php foreach ($_POST['ratings'] as $index => $rating): ?>
                                    <div class="rating">
                                        <input type="text" name="ratings[]" placeholder="Название рейтинга"
                                            value="<?= htmlspecialchars($rating) ?>">
                                        <input type="url" name="links[]" placeholder="Ссылка (если есть)"
                                            value="<?= isset($_POST['links'][$index]) ? htmlspecialchars($_POST['links'][$index]) : '' ?>">
                                        <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="rating">
                                    <input type="text" name="ratings[]" placeholder="Название рейтинга">
                                    <input type="url" name="links[]" placeholder="Ссылка (если есть)">
                                    <button type="button" class="add-btn" onclick="addRating()">+</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <input type="submit" value="Добавить публикацию" id="addPublicationBtn" disabled>
                    </form>
                </div>

                <!-- Колонка управления (добавление/удаление года и раздела, удаление публикации) -->
                <div class="management-column">
                    <h3>Управление годами и разделами</h3>
                    <label for="new_year">Добавление года:</label>
                    <form method="post" class="management-form">
                        <input type="hidden" name="action" value="add_year">
                        <input type="number" name="new_year" placeholder="Новый год" required>
                        <button type="submit" class="add-btn">+</button>
                    </form>

                    <label for="year">Добавление раздела:</label>
                    <form method="post" class="management-form">
                        <input type="hidden" name="action" value="add_section">
                        <select name="year" required>
                            <option value="">Выберите год</option>
                            <?php foreach ($yearsSections as $year => $sections): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_section" placeholder="Новый раздел" required>
                        <button type="submit" class="add-btn">+</button>
                    </form>

                    <label for="delete_year">Удаление года:</label>
                    <form method="post" class="management-form">
                        <input type="hidden" name="action" value="delete_year">
                        <select name="delete_year" required>
                            <option value="">Выберите год</option>
                            <?php foreach ($yearsSections as $year => $sections): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="remove-btn"
                            onclick="return confirm('Вы уверены? Это удалит все публикации за этот год.')">-</button>
                    </form>

                    <label for="delete_year">Удаление раздела:</label>
                    <form method="post" class="management-form">
                        <input type="hidden" name="action" value="delete_section">
                        <select name="delete_year" id="delete-year-section" required
                            onchange="updateDeleteSectionSelect(this.value)">
                            <option value="">Выберите год</option>
                            <?php foreach ($yearsSections as $year => $sections): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="delete_section" id="delete-section-select" required>
                            <option value="">Выберите раздел</option>
                        </select>
                        <button type="submit" class="remove-btn"
                            onclick="return confirm('Вы уверены? Это удалит все публикации в этом разделе.')">-</button>
                    </form>

                    <label for="delete_year">Удаление публикации:</label>
                    <form method="post" class="management-form">
                        <input type="hidden" name="action" value="delete_publication">
                        <select name="delete_year" id="delete-year-publication" required
                            onchange="updateDeleteSectionSelect(this.value, 'delete-section-publication'); updateDeletePublicationSelect(this.value, '')">
                            <option value="">Выберите год</option>
                            <?php foreach ($yearsSections as $year => $sections): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="delete_section" id="delete-section-publication" required
                            onchange="updateDeletePublicationSelect(document.getElementById('delete-year-publication').value, this.value)">
                            <option value="">Выберите раздел</option>
                        </select>
                        <select name="delete_publication" id="delete-publication-select" required>
                            <option value="">Выберите публикацию</option>
                        </select>
                        <button type="submit" class="remove-btn"
                            onclick="return confirm('Вы уверены? Это удалит выбранную публикацию.')">-</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php elseif (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
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
            const addPublicationBtn = $('#addPublicationBtn');

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

            const authorsOptions = `<?= $authorsOptions ?>`;

            window.addAuthor = function () {
                const container = document.getElementById('authors-container');
                const div = document.createElement('div');
                div.classList.add('author');
                div.innerHTML = `
                    <select name="authors[]">
                        <option value="">Выберите автора</option>
                        ${authorsOptions}
                        <option value="custom">Другой...</option>
                    </select>
                    <input type="text" name="custom_authors[]" placeholder="Введите автора" style="display:none;">
                    <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
                `;
                container.appendChild(div);
            }

            document.addEventListener('change', function (e) {
                if (e.target && e.target.tagName === 'SELECT' && e.target.name === 'authors[]') {
                    const customInput = e.target.nextElementSibling;
                    if (e.target.value === 'custom') {
                        customInput.style.display = 'block';
                    } else {
                        customInput.style.display = 'none';
                        customInput.value = '';
                    }
                }
            });

            // Выход из аккаунта
            logoutBtn.on('click', function () {
                $.ajax({
                    url: 'auth.php?logout',
                    method: 'GET',
                    success: function () {
                        loginBtn.css('display', 'block');
                        userInfo.css('display', 'none');
                        disableAddPublicationButton();
                    }
                });
            });

            // Проверка состояния авторизации
            function checkAuth() {
                $.ajax({
                    url: 'auth.php',
                    method: 'GET',
                    success: function (response) {
                        const data = JSON.parse(response);
                        if (data.username) {
                            updateUI(data.username);
                        } else {
                            disableAddPublicationButton();
                        }
                    }
                });
            }

            // Обновление UI после авторизации
            function updateUI(username) {
                loginBtn.css('display', 'none');
                userInfo.css('display', 'flex');
                userNameDisplay.text(username);
                enableAddPublicationButton();
            }

            function enableAddPublicationButton() {
                addPublicationBtn.prop('disabled', false);
                addPublicationBtn.css('opacity', '1');
            }

            function disableAddPublicationButton() {
                addPublicationBtn.prop('disabled', true);
                addPublicationBtn.css('opacity', '0.6');
            }

            checkAuth();
        });

        // Данные о годах, разделах и публикациях
        const yearsSections = <?= json_encode($yearsSections, JSON_UNESCAPED_UNICODE) ?>;
        const publications = <?= json_encode($pubData, JSON_UNESCAPED_UNICODE) ?>;

        // Обработка выбора года для добавления публикации
        document.getElementById('year-select').addEventListener('change', function () {
            const year = this.value;
            const sectionSelect = document.getElementById('section-select');
            sectionSelect.innerHTML = '<option value="">Выберите раздел</option>';

            if (yearsSections[year]) {
                yearsSections[year].forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionSelect.appendChild(option);
                });
            }
        });

        // Обновление списка разделов для форм удаления
        function updateDeleteSectionSelect(year, sectionSelectId = 'delete-section-select') {
            const sectionSelect = document.getElementById(sectionSelectId);
            sectionSelect.innerHTML = '<option value="">Выберите раздел</option>';
            const sections = yearsSections[year] || [];
            sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
            });
            if (sectionSelectId === 'delete-section-publication') {
                document.getElementById('delete-publication-select').innerHTML = '<option value="">Выберите публикацию</option>';
            }
        }

        // Обновление списка публикаций для формы удаления
        function updateDeletePublicationSelect(year, section) {
            const publicationSelect = document.getElementById('delete-publication-select');
            publicationSelect.innerHTML = '<option value="">Выберите публикацию</option>';
            const pubs = publications[year]?.[section] || [];
            pubs.forEach(pub => {
                const option = document.createElement('option');
                option.value = pub;
                option.textContent = pub.length > 100 ? pub.substring(0, 100) + '...' : pub;
                publicationSelect.appendChild(option);
            });
        }

        // Добавление поля рейтинга
        function addRating() {
            const ratingsContainer = document.getElementById('ratings-container');
            const newRatingDiv = document.createElement('div');
            newRatingDiv.classList.add('rating');
            newRatingDiv.innerHTML = `
                <input type="text" name="ratings[]" placeholder="Название рейтинга">
                <input type="url" name="links[]" placeholder="Ссылка (если есть)">
                <button type="button" class="remove-btn" onclick="this.parentNode.remove()">-</button>
            `;
            ratingsContainer.appendChild(newRatingDiv);
        }

        // Переключение темы
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