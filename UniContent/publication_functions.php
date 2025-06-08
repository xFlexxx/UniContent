<?php
function getPublicationsData($filePath)
{
    if (!file_exists($filePath)) {
        die("Файл не найден: $filePath");
    }

    $html = file_get_contents($filePath);
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $yearsSections = [];

    $yearNodes = $xpath->query("//h1[@class='style15']/a");

    foreach ($yearNodes as $yearNode) {
        $year = $yearNode->getAttribute('name');
        $sections = [];
        $current = $yearNode->parentNode->nextSibling;

        while ($current) {
            if ($current->nodeName === 'h1') {
                break;
            }

            if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
                $emNode = $xpath->query(".//em", $current)->item(0);
                if ($emNode && trim($emNode->textContent) !== '') {
                    $sectionText = trim($emNode->textContent);
                    if (!in_array($sectionText, $sections)) {
                        $sections[] = $sectionText;
                    }
                }
            }

            $current = $current->nextSibling;
        }

        $yearsSections[$year] = $sections;
    }

    return $yearsSections;
}

function handleAddYear($filePath, $newYear) {
    if (empty($newYear) || !is_numeric($newYear)) {
        return ['success' => false, 'message' => 'Введите корректный год'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);

    // Проверка на существование года
    if ($xpath->query("//h1[@class='style15']/a[@name='$newYear']")->length > 0) {
        return ['success' => false, 'message' => 'Этот год уже существует'];
    }

    // Создаем элементы для года
    $comment = $dom->createComment($newYear);
    $hr = $dom->createElement('hr');
    $hr->setAttribute('align', 'left');
    $h1 = $dom->createElement('h1');
    $h1->setAttribute('class', 'style15');
    $a = $dom->createElement('a', "Публикации $newYear г.");
    $a->setAttribute('name', $newYear);
    $h1->appendChild($a);

    // Находим body
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        $body = $dom->createElement('body');
        $dom->appendChild($body);
    }

    // Находим все h1 и определяем позицию вставки
    $yearNodes = $xpath->query("//h1[@class='style15']/a");
    $inserted = false;

    if ($yearNodes->length === 0) {
        // Если нет годов, вставляем после таблицы или в конец body
        $table = $xpath->query("//table[@class='style9']")->item(0);
        if ($table) {
            $body->insertBefore($h1, $table->nextSibling);
            $body->insertBefore($comment, $h1->nextSibling);
            $body->insertBefore($hr, $comment->nextSibling);
        } else {
            $body->appendChild($h1);
            $body->appendChild($comment);
            $body->appendChild($hr);
        }
    } else {
        foreach ($yearNodes as $yearNode) {
            $existingYear = (int)preg_replace('/[^0-9]/', '', $yearNode->nodeValue);
            if ($newYear > $existingYear) {
                $targetH1 = $yearNode->parentNode;
                $parent = $targetH1->parentNode;
                if ($parent && $parent->contains($targetH1)) {
                    $parent->insertBefore($h1, $targetH1);
                    $parent->insertBefore($comment, $h1->nextSibling);
                    $parent->insertBefore($hr, $comment->nextSibling);
                    $inserted = true;
                    break;
                } else {
                    $body->insertBefore($h1, $targetH1);
                    $body->insertBefore($comment, $h1->nextSibling);
                    $body->insertBefore($hr, $comment->nextSibling);
                    $inserted = true;
                    break;
                }
            }
        }
        if (!$inserted) {
            $body->appendChild($h1);
            $body->appendChild($comment);
            $body->appendChild($hr);
        }
    }

    // Обновляем меню
    $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    if ($columns->length === 0) {
        $table = $dom->createElement('table');
        $table->setAttribute('class', 'style9');
        $tr = $dom->createElement('tr');
        $td = $dom->createElement('td');
        $td->setAttribute('class', 'style9');
        $td->setAttribute('valign', 'top');
        $ul = $dom->createElement('ul');
        $td->appendChild($ul);
        $tr->appendChild($td);
        $table->appendChild($tr);
        $body->appendChild($table);
        $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    }

    $allYears = [];
    foreach ($columns as $column) {
        $items = $xpath->query(".//ul/li", $column);
        foreach ($items as $item) {
            $link = $xpath->query(".//a", $item)->item(0);
            $year = (int)preg_replace('/[^0-9]/', '', $link->nodeValue);
            $text = $item->ownerDocument->saveHTML($item);
            $allYears[$year] = $text;
        }
    }
    $allYears[$newYear] = "<li><a href=\"#$newYear\">в $newYear г.</a> - 0 работ</li>";

    krsort($allYears);

    foreach ($columns as $column) {
        $ul = $xpath->query(".//ul", $column)->item(0);
        while ($ul->hasChildNodes()) {
            $ul->removeChild($ul->firstChild);
        }
    }

    $columnIndex = 0;
    $itemCount = 0;
    foreach ($allYears as $year => $text) {
        if ($columnIndex >= $columns->length) {
            $td = $dom->createElement('td');
            $td->setAttribute('class', 'style9');
            $td->setAttribute('valign', 'top');
            $ul = $dom->createElement('ul');
            $td->appendChild($ul);
            $xpath->query("//table[@class='style9']/tr")->item(0)->appendChild($td);
            $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
        }
        $ul = $xpath->query(".//ul", $columns->item($columnIndex))->item(0);

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($text);
        $ul->appendChild($fragment);

        $itemCount++;
        if ($itemCount == 4 && $columnIndex < $columns->length - 1) {
            $columnIndex++;
            $itemCount = 0;
        }
    }

    saveDom($dom, $filePath);
    return ['success' => true, 'message' => "Год $newYear успешно добавлен"];
}

function handleAddSection($filePath, $year, $newSection) {
    if (empty($year) || empty($newSection)) {
        return ['success' => false, 'message' => 'Заполните все поля'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);
    
    $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
    if (!$yearNode) {
        return ['success' => false, 'message' => "Год $year не найден"];
    }

    $current = $yearNode->parentNode->nextSibling;
    while ($current && $current->nodeName !== 'h1') {
        if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
            $emNode = $xpath->query(".//em", $current)->item(0);
            if ($emNode && trim($emNode->textContent) === $newSection) {
                return ['success' => false, 'message' => 'Этот раздел уже существует'];
            }
        }
        $current = $current->nextSibling;
    }

    $p = $dom->createElement('p');
    $p->setAttribute('class', 'style15');
    $em = $dom->createElement('em', $newSection);
    $p->appendChild($em);
    $ol = $dom->createElement('ol');
    
    $nextYear = $yearNode->parentNode->nextSibling;
    while ($nextYear && $nextYear->nodeName !== 'h1' && $nextYear->nodeName !== 'hr') {
        $nextYear = $nextYear->nextSibling;
    }
    
    $yearNode->parentNode->parentNode->insertBefore($ol, $nextYear);
    $yearNode->parentNode->parentNode->insertBefore($p, $ol);
    
    saveDom($dom, $filePath);
    return ['success' => true, 'message' => "Раздел '$newSection' добавлен в $year"];
}

function handleDeleteYear($filePath, $year) {
    if (empty($year)) {
        return ['success' => false, 'message' => 'Выберите год'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);

    $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
    if (!$yearNode) {
        return ['success' => false, 'message' => "Год $year не найден"];
    }

    $parent = $yearNode->parentNode->parentNode;
    $current = $yearNode->parentNode->previousSibling;
    $startNode = null;

    while ($current && ($current->nodeType === XML_COMMENT_NODE || $current->nodeName === 'hr')) {
        $startNode = $current;
        $current = $current->previousSibling;
    }

    $current = $startNode ?: $yearNode->parentNode;
    while ($current) {
        $next = $current->nextSibling;
        if ($current->nodeName === 'h1' && $current !== $yearNode->parentNode) {
            break;
        }
        $parent->removeChild($current);
        $current = $next;
    }

    $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    $allYears = [];

    foreach ($columns as $column) {
        $items = $xpath->query(".//ul/li", $column);
        foreach ($items as $item) {
            $link = $xpath->query(".//a", $item)->item(0);
            $menuYear = (int)preg_replace('/[^0-9]/', '', $link->nodeValue);
            if ($menuYear != $year) {
                $text = $item->ownerDocument->saveHTML($item);
                $allYears[$menuYear] = $text;
            }
        }
    }

    krsort($allYears);

    foreach ($columns as $column) {
        $ul = $xpath->query(".//ul", $column)->item(0);
        while ($ul->hasChildNodes()) {
            $ul->removeChild($ul->firstChild);
        }
    }

    $columnIndex = 0;
    $itemCount = 0;
    foreach ($allYears as $year1 => $text) {
        if ($columnIndex >= $columns->length) {
            $td = $dom->createElement('td');
            $td->setAttribute('class', 'style9');
            $td->setAttribute('valign', 'top');
            $ul = $dom->createElement('ul');
            $td->appendChild($ul);
            $xpath->query("//table[@class='style9']/tr")->item(0)->appendChild($td);
            $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
        }
        $ul = $xpath->query(".//ul", $columns->item($columnIndex))->item(0);

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($text);
        $ul->appendChild($fragment);

        $itemCount++;
        if ($itemCount == 4 && $columnIndex < $columns->length - 1) {
            $columnIndex++;
            $itemCount = 0;
        }
    }

    saveDom($dom, $filePath);
    return ['success' => true, 'message' => "Год $year успешно удален"];
}
function handleDeleteSection($filePath, $year, $section) {
    if (empty($year) || empty($section)) {
        return ['success' => false, 'message' => 'Выберите год и раздел'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);
    
    $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
    if (!$yearNode) {
        return ['success' => false, 'message' => "Год $year не найден"];
    }

    $current = $yearNode->parentNode->nextSibling;
    $sectionFound = false;
    $nodesToRemove = [];

    while ($current && $current->nodeName !== 'h1') {
        if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
            $emNode = $xpath->query(".//em", $current)->item(0);
            if ($emNode && trim($emNode->textContent) === trim($section)) {
                $sectionFound = true;
                $nodesToRemove[] = $current;
                $next = $current->nextSibling;
                while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h1') {
                    if ($next->nodeName === 'ol') {
                        $nodesToRemove[] = $next;
                    }
                    $next = $next->nextSibling;
                }
                break;
            }
        }
        $current = $current->nextSibling;
    }

    if (!$sectionFound) {
        return ['success' => false, 'message' => "Раздел '$section' не найден в $year"];
    }

    foreach ($nodesToRemove as $node) {
        $node->parentNode->removeChild($node);
    }

    $currentNode = $yearNode->parentNode->nextSibling;
    $globalCounter = 1;
    while ($currentNode && $currentNode->nodeName !== 'h1') {
        if ($currentNode->nodeName === 'ol') {
            foreach ($currentNode->getElementsByTagName('li') as $item) {
                $item->setAttribute('value', $globalCounter);
                $globalCounter++;
            }
        }
        $currentNode = $currentNode->nextSibling;
    }

    $totalWorks = $globalCounter - 1;

    $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    $allYears = [];

    foreach ($columns as $column) {
        $items = $xpath->query(".//ul/li", $column);
        foreach ($items as $item) {
            $link = $xpath->query(".//a", $item)->item(0);
            $menuYear = (int)preg_replace('/[^0-9]/', '', $link->nodeValue);
            if ($menuYear == $year) {
                $allYears[$menuYear] = "<li><a href=\"#$menuYear\">в $menuYear г.</a> - $totalWorks работ" .
                    ($totalWorks % 10 == 1 && $totalWorks % 100 != 11 ? 'а' : ($totalWorks % 10 >= 2 && $totalWorks % 10 <= 4 && ($totalWorks % 100 < 10 || $totalWorks % 100 >= 20) ? 'ы' : '')) . "</li>";
            } else {
                $allYears[$menuYear] = $item->ownerDocument->saveHTML($item);
            }
        }
    }

    foreach ($columns as $column) {
        $ul = $xpath->query(".//ul", $column)->item(0);
        while ($ul->hasChildNodes()) {
            $ul->removeChild($ul->firstChild);
        }
    }

    $columnIndex = 0;
    $itemCount = 0;
    krsort($allYears);
    foreach ($allYears as $year => $text) {
        if ($columnIndex >= $columns->length) {
            $td = $dom->createElement('td');
            $td->setAttribute('class', 'style9');
            $td->setAttribute('valign', 'top');
            $ul = $dom->createElement('ul');
            $td->appendChild($ul);
            $xpath->query("//table[@class='style9']/tr")->item(0)->appendChild($td);
            $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
        }
        $ul = $xpath->query(".//ul", $columns->item($columnIndex))->item(0);

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($text);
        $ul->appendChild($fragment);

        $itemCount++;
        if ($itemCount == 4 && $columnIndex < $columns->length - 1) {
            $columnIndex++;
            $itemCount = 0;
        }
    }

    saveDom($dom, $filePath);
    return ['success' => true, 'message' => "Раздел '$section' и все его публикации удалены из $year"];
}

function handleDeletePublication($filePath, $year, $section, $publication) {
    if (empty($year) || empty($section) || empty($publication)) {
        return ['success' => false, 'message' => 'Выберите год, раздел и статью'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);
    
    $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
    if (!$yearNode) {
        return ['success' => false, 'message' => "Год $year не найден"];
    }

    $current = $yearNode->parentNode->nextSibling;
    $sectionFound = false;
    $olNode = null;

    while ($current && $current->nodeName !== 'h1') {
        if ($current->nodeName === 'p' && $current->getAttribute('class') === 'style15') {
            $emNode = $xpath->query(".//em", $current)->item(0);
            if ($emNode && trim($emNode->textContent) === trim($section)) {
                $sectionFound = true;
                $next = $current->nextSibling;
                while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h1') {
                    if ($next->nodeName === 'ol') {
                        $olNode = $next;
                        break;
                    }
                    $next = $next->nextSibling;
                }
                break;
            }
        }
        $current = $current->nextSibling;
    }

    if (!$sectionFound || !$olNode) {
        return ['success' => false, 'message' => "Раздел '$section' не найден в $year"];
    }

    $publicationFound = false;
    foreach ($olNode->getElementsByTagName('li') as $li) {
        if (trim($li->textContent) === trim($publication)) {
            $publicationFound = true;
            $li->parentNode->removeChild($li);
            break;
        }
    }

    if (!$publicationFound) {
        return ['success' => false, 'message' => "Статья '$publication' не найдена в разделе '$section'"];
    }

    $currentNode = $yearNode->parentNode->nextSibling;
    $globalCounter = 1;
    while ($currentNode && $currentNode->nodeName !== 'h1') {
        if ($currentNode->nodeName === 'ol') {
            foreach ($currentNode->getElementsByTagName('li') as $item) {
                $item->setAttribute('value', $globalCounter);
                $globalCounter++;
            }
        }
        $currentNode = $currentNode->nextSibling;
    }

    $totalWorks = $globalCounter - 1;

    $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    $allYears = [];

    foreach ($columns as $column) {
        $items = $xpath->query(".//ul/li", $column);
        foreach ($items as $item) {
            $link = $xpath->query(".//a", $item)->item(0);
            $menuYear = (int)preg_replace('/[^0-9]/', '', $link->nodeValue);
            if ($menuYear == $year) {
                $allYears[$menuYear] = "<li><a href=\"#$menuYear\">в $menuYear г.</a> - $totalWorks работ" .
                    ($totalWorks % 10 == 1 && $totalWorks % 100 != 11 ? 'а' : ($totalWorks % 10 >= 2 && $totalWorks % 10 <= 4 && ($totalWorks % 100 < 10 || $totalWorks % 100 >= 20) ? 'ы' : '')) . "</li>";
            } else {
                $allYears[$menuYear] = $item->ownerDocument->saveHTML($item);
            }
        }
    }

    foreach ($columns as $column) {
        $ul = $xpath->query(".//ul", $column)->item(0);
        while ($ul->hasChildNodes()) {
            $ul->removeChild($ul->firstChild);
        }
    }

    $columnIndex = 0;
    $itemCount = 0;
    krsort($allYears);
    foreach ($allYears as $year => $text) {
        if ($columnIndex >= $columns->length) {
            $td = $dom->createElement('td');
            $td->setAttribute('class', 'style9');
            $td->setAttribute('valign', 'top');
            $ul = $dom->createElement('ul');
            $td->appendChild($ul);
            $xpath->query("//table[@class='style9']/tr")->item(0)->appendChild($td);
            $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
        }
        $ul = $xpath->query(".//ul", $columns->item($columnIndex))->item(0);

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($text);
        $ul->appendChild($fragment);

        $itemCount++;
        if ($itemCount == 4 && $columnIndex < $columns->length - 1) {
            $columnIndex++;
            $itemCount = 0;
        }
    }

    saveDom($dom, $filePath);
    return ['success' => true, 'message' => "Статья '$publication' удалена из раздела '$section' в $year"];
}

function loadDom($filePath) {
    if (!file_exists($filePath)) {
        $html = '<!DOCTYPE html><html><body></body></html>';
    } else {
        $html = file_get_contents($filePath);
    }
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    return $dom;
}

function saveDom($dom, $filePath) {
    $updatedHtml = $dom->saveHTML();
    $updatedHtml = html_entity_decode($updatedHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    file_put_contents($filePath, $updatedHtml);
}

function handlePublicationForm($filePath, $yearsSections)
{
    $year = $_POST['year'] ?? '';
    $sectionTitle = $_POST['section'] ?? '';
    $finalAuthors = [];
    if (isset($_POST['authors']) && isset($_POST['custom_authors'])) {
        foreach ($_POST['authors'] as $index => $author) {
            if ($author === 'custom' && !empty($_POST['custom_authors'][$index])) {
                $finalAuthors[] = $_POST['custom_authors'][$index];
            } elseif (!empty($author) && $author !== 'custom') {
                $finalAuthors[] = $author;
            }
        }
    }

    $title = $_POST['title'] ?? '';
    $source = $_POST['source'] ?? '';
    $link = $_POST['link'] ?? '';
    $doi = $_POST['doi'] ?? '';
    $ratings = $_POST['ratings'] ?? [];
    $ratingLinks = $_POST['links'] ?? [];

    if (empty($finalAuthors) || empty($title) || empty($source) || empty($year) || empty($sectionTitle)) {
        return ['success' => false, 'message' => 'Все обязательные поля должны быть заполнены.'];
    }

    $dom = loadDom($filePath);
    $xpath = new DOMXPath($dom);

    $language = preg_match('/[А-Яа-яЁё]/u', implode(' ', $finalAuthors) . $title . $source) ? 'ru' : 'en';

    $publicationContent = htmlspecialchars(implode(', ', $finalAuthors)) . ' ' .
        htmlspecialchars($title) . ' // ' .
        htmlspecialchars($source);

    if (!empty($doi)) {
        $doiLink = '<a href="https://doi.org/' . htmlspecialchars($doi) . '" target="_blank">' .
            htmlspecialchars($doi) . '</a>';
        $publicationContent .= ' DOI: ' . $doiLink;
    }

    $ratingParts = [];
    foreach ($ratings as $index => $rating) {
        if (!empty($rating)) {
            $ratingText = htmlspecialchars($rating);
            if (isset($ratingLinks[$index]) && !empty($ratingLinks[$index])) {
                $ratingParts[] = '<a href="' . htmlspecialchars($ratingLinks[$index]) .
                    '" target="_blank">' . $ratingText . '</a>';
            } else {
                $ratingParts[] = $ratingText;
            }
        }
    }

    if (!empty($ratingParts)) {
        $publicationContent .= ' (' . implode(', ', $ratingParts) . ')';
    }

    if (!empty($link)) {
        $pdfLinkText = ($language === 'ru') ? 'Текст в формате PDF' : 'Full Text in PDF';
        $pdfLink = '<a href="' . htmlspecialchars($link) . '" target="_blank">[' . $pdfLinkText . ']</a>';
        $publicationContent .= ' ' . $pdfLink;
    }

    $yearNode = $xpath->query("//h1[@class='style15']/a[@name='$year']")->item(0);
    if (!$yearNode) {
        return ['success' => false, 'message' => "Год $year не найден."];
    }

    $sectionFound = false;
    $currentNode = $yearNode->parentNode->nextSibling;
    $olNode = null;

    while ($currentNode) {
        if ($currentNode->nodeName === 'h1')
            break;

        if ($currentNode->nodeName === 'p' && $currentNode->getAttribute('class') === 'style15') {
            $emNode = $xpath->query(".//em", $currentNode)->item(0);
            if ($emNode && trim($emNode->textContent) === trim($sectionTitle)) {
                $sectionFound = true;
                $nextNode = $currentNode->nextSibling;
                while ($nextNode && $nextNode->nodeName !== 'ol') {
                    $nextNode = $nextNode->nextSibling;
                }
                if ($nextNode) {
                    $olNode = $nextNode;
                    break;
                }
            }
        }
        $currentNode = $currentNode->nextSibling;
    }

    if (!$sectionFound || !$olNode) {
        return ['success' => false, 'message' => "Раздел '$sectionTitle' не найден или не содержит списка."];
    }

    $newLi = $dom->createElement('li');
    $fragment = $dom->createDocumentFragment();
    $fragment->appendXML($publicationContent);
    $newLi->appendChild($fragment);
    $olNode->appendChild($newLi);

    $yearNodeParent = $yearNode->parentNode;
    $globalCounter = 1;

    $currentNode = $yearNodeParent->nextSibling;
    while ($currentNode) {
        if ($currentNode->nodeName === 'h1')
            break;
        if ($currentNode->nodeName === 'ol') {
            foreach ($currentNode->getElementsByTagName('li') as $item) {
                $item->setAttribute('value', $globalCounter);
                $globalCounter++;
            }
        }
        $currentNode = $currentNode->nextSibling;
    }

    $totalWorks = $globalCounter - 1;

    $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
    $allYears = [];

    foreach ($columns as $column) {
        $items = $xpath->query(".//ul/li", $column);
        foreach ($items as $item) {
            $link = $xpath->query(".//a", $item)->item(0);
            $menuYear = (int)preg_replace('/[^0-9]/', '', $link->nodeValue);
            if ($menuYear == $year) {
                $allYears[$menuYear] = "<li><a href=\"#$menuYear\">в $menuYear г.</a> - $totalWorks работ" .
                    ($totalWorks % 10 == 1 && $totalWorks % 100 != 11 ? 'а' : ($totalWorks % 10 >= 2 && $totalWorks % 10 <= 4 && ($totalWorks % 100 < 10 || $totalWorks % 100 >= 20) ? 'ы' : '')) . "</li>";
            } else {
                $allYears[$menuYear] = $item->ownerDocument->saveHTML($item);
            }
        }
    }

    foreach ($columns as $column) {
        $ul = $xpath->query(".//ul", $column)->item(0);
        while ($ul->hasChildNodes()) {
            $ul->removeChild($ul->firstChild);
        }
    }

    $columnIndex = 0;
    $itemCount = 0;
    krsort($allYears);
    foreach ($allYears as $year => $text) {
        if ($columnIndex >= $columns->length) {
            $td = $dom->createElement('td');
            $td->setAttribute('class', 'style9');
            $td->setAttribute('valign', 'top');
            $ul = $dom->createElement('ul');
            $td->appendChild($ul);
            $xpath->query("//table[@class='style9']/tr")->item(0)->appendChild($td);
            $columns = $xpath->query("//table[@class='style9']/tr/td[@class='style9']");
        }
        $ul = $xpath->query(".//ul", $columns->item($columnIndex))->item(0);

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($text);
        $ul->appendChild($fragment);

        $itemCount++;
        if ($itemCount == 4 && $columnIndex < $columns->length - 1) {
            $columnIndex++;
            $itemCount = 0;
        }
    }

    saveDom($dom, $filePath);
    return ['success' => true, 'message' => 'Публикация успешно добавлена!'];
}
?>