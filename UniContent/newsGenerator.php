<?php

// Функция для сбора новостей из папки output
function fetchNewsFromCombined($folderPath)
{
    $allNews = [];
    $uniqueNews = [];

    foreach (glob($folderPath . '/*.html') as $file) {
        $htmlContent = file_get_contents($file);
        if ($htmlContent === false) {
            continue;
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent); // Добавляем кодировку
        libxml_clear_errors();

        $newsItems = $dom->getElementsByTagName('div');
        foreach ($newsItems as $newsItem) {
            if ($newsItem->getAttribute('class') !== 'news-date') {
                continue;
            }

            $dateText = trim($newsItem->nodeValue);
            $date = preg_replace('/[^\d.-]/', '', $dateText);

            $table = $newsItem->nextSibling;
            while ($table && $table->nodeName !== 'table') {
                $table = $table->nextSibling;
            }
            if (!$table) continue;

            $titleNode = $table->getElementsByTagName('h1')->item(0);
            $title = $titleNode ? trim($titleNode->nodeValue) : 'Без заголовка';

            $newsKey = $date . '|' . $title;
            if (isset($uniqueNews[$newsKey])) {
                continue;
            }
            $uniqueNews[$newsKey] = true;

            $contentNode = $table->getElementsByTagName('td')->item(1);
            $contentHtml = '';
            if ($contentNode) {
                // Извлекаем только текстовое содержимое и разрешенные теги
                $contentHtml = $dom->saveHTML($contentNode);
                $contentHtml = strip_tags(html_entity_decode($contentHtml), '<a><b><strong><i><em><u><p>');
                // Удаляем лишние пробелы и переносы строк
                $contentHtml = preg_replace('/\s+/', ' ', trim($contentHtml));
            }

            $imgNode = $contentNode ? $contentNode->getElementsByTagName('img')->item(0) : null;
            $imageUrl = $imgNode ? $imgNode->getAttribute('src') : '';
            $imageAttributes = [];
            if ($imgNode) {
                foreach (['style', 'width', 'height', 'alt'] as $attr) {
                    if ($imgNode->hasAttribute($attr)) {
                        $imageAttributes[] = "$attr=\"{$imgNode->getAttribute($attr)}\"";
                    }
                }
            }
            $imageAttributesStr = implode(' ', $imageAttributes);

            $linkNode = $table->getElementsByTagName('a')->item(0);
            $linkUrl = $linkNode ? $linkNode->getAttribute('href') : '';

            $allNews[] = [
                'date' => $date,
                'title' => $title,
                'content' => $contentHtml,
                'image' => $imageUrl,
                'imageAttributes' => $imageAttributesStr,
                'link' => $linkUrl,
            ];
        }
    }

    return $allNews;
}

// Функция для сортировки новостей по дате
function sortNewsByDate($news)
{
    usort($news, function ($a, $b) {
        $dateA = DateTime::createFromFormat('d.m.Y', $a['date']);
        $dateB = DateTime::createFromFormat('d.m.Y', $b['date']);
        return $dateB <=> $dateA;
    });
    return $news;
}

// Функция для генерации HTML-страниц
function generateNewsPages($templateFile, $news, $outputDir, $customTemplate = null)
{
    $newsPerPageFirst = 8;
    $newsPerPageOther = 8;
    $pageNumber = 1;
    $totalPages = ceil((count($news) - $newsPerPageFirst) / $newsPerPageOther) + 1;

    while (!empty($news)) {
        $newsSubset = ($pageNumber === 1) ?
            array_slice($news, 0, $newsPerPageFirst) :
            array_slice($news, 0, $newsPerPageOther);
        $news = array_slice($news, count($newsSubset));

        if (!file_exists($templateFile)) {
            return ['success' => false, 'message' => "Шаблонный файл не найден: $templateFile"];
        }

        $templateContent = file_get_contents($templateFile);
        if ($templateContent === false) {
            return ['success' => false, 'message' => "Ошибка чтения шаблонного файла: $templateFile"];
        }

        $newsHtml = '';
        foreach ($newsSubset as $newsItem) {
            $imageUrl = $newsItem['image'] ?? '';
            $imageAttributesStr = $newsItem['imageAttributes'] ?? '';
            $linkHtml = $newsItem['link'] ? "<div class='link-fullnews'><a href='{$newsItem['link']}'>Подробнее...</a></div>" : '';

            // Убедимся, что стили изображения корректны
            $imageAttributesStr = str_replace(
                ['width:', 'height:'],
                ['width:', 'height:'],
                $imageAttributesStr
            );

            $newsHtml .= "<div class='news-date'>[{$newsItem['date']}]</div>
                <table style='text-align:left; width:100%; border-collapse: collapse;'>
                    <tr><td style='border-style: none !important; text-align: left;'>
                        <h1 class='news'>{$newsItem['title']}</h1>
                    </td></tr>
                    <tr><td style='border-style: none !important;'>
                        <div style='display: flex; align-items: flex-start;'>";
            if ($imageUrl) {
                $newsHtml .= "<div style='flex: 0 0 auto; margin: 10px;'>
                                <img src='{$imageUrl}' alt='News image' {$imageAttributesStr} />
                              </div>";
            }
            $newsHtml .= "<div style='flex: 1; overflow: hidden;'>
                            <p style='margin: 0;'>{$newsItem['content']}</p>
                          </div>
                        </div>
                    </td></tr>
                    <tr><td style='border-style: none !important;'>
                        {$linkHtml}
                    </td></tr>
                </table>
                <hr/>";
        }

        $navigationHtml = 'Страницы: ';
        for ($i = 1; $i <= $totalPages; $i++) {
            $navigationHtml .= ($i == $pageNumber) ? "[$i] " : "<a href='./page_$i.html'>[$i]</a> ";
        }
        $navigationHtml .= "<hr/>";

        $pageContent = str_replace('<!-- NAVIGATION_MENU -->', $navigationHtml, $templateContent);
        $pageContent = str_replace('<!-- NEWS_PLACEHOLDER -->', $newsHtml, $pageContent);
        $pageContent = mb_convert_encoding($pageContent, 'UTF-8', 'auto');

        $outputFile = "$outputDir/page_$pageNumber.html";
        if (file_put_contents($outputFile, $pageContent) === false) {
            return ['success' => false, 'message' => "Ошибка записи файла: $outputFile"];
        }

        $pageNumber++;
    }
    return ['success' => true, 'message' => 'Страницы успешно сгенерированы'];
}
?>