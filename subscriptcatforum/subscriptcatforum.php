<?php

/**
 * [BEGIN_COT_EXT]
 * Hooks=standalone
 * [END_COT_EXT]
 */
 


// Подключаем необходимые файлы и данные
defined('COT_CODE') or die('Wrong URL');

// Получаем все категории форума
$categories = $db->query("SELECT structure_code, structure_id, structure_title, structure_path FROM $db_structure WHERE structure_area = 'forums' ORDER BY structure_path ASC")->fetchAll();

// Формируем массив иерархии
$category_tree = [];
foreach ($categories as $category) {
    $levels = explode('.', trim($category['structure_path'], '.'));
    $depth = count($levels); // Определяем уровень вложенности
    $category['depth'] = $depth;
    $category_tree[] = $category;
}

// Инициализация переменной для выбранных категорий
$selected_categories = [];

// Проверяем, есть ли подписки у пользователя в таблице подписок
if (isset($usr['id'])) {
    // Получаем подписки из таблицы $db_subscriptcatforum для текущего пользователя
    $subscriptcatforum = $db->query("SELECT cat_forum_subs FROM $db_subscriptcatforum WHERE user_id = ?", [$usr['id']])->fetchAll();
    
    // Преобразуем результат в массив кодов категорий
    $selected_categories = array_map(function($row) {
        return $row['cat_forum_subs'];
    }, $subscriptcatforum);
}

// Если форма отправлена и есть выбранные категории
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_categories'])) {
    // Получаем выбранные категории из POST запроса
    $selected_categories = $_POST['selected_categories'];

    // Сохраняем подписки в таблицу подписок
    if (!empty($selected_categories)) {
        // Удаляем старые подписки пользователя перед добавлением новых
        $db->query("DELETE FROM $db_subscriptcatforum WHERE user_id = ?", [$usr['id']]);

        // Добавляем новые подписки
        foreach ($selected_categories as $category_id) {
            // Пишем новый запрос на добавление подписки
            $db->query("INSERT INTO {$db_subscriptcatforum} (user_id, cat_forum_subs, created_at) VALUES (?, ?, ?)", 
                [$usr['id'], $category_id, time()]);
        }
    }
}

// Загрузка шаблона
$t = new XTemplate(cot_tplfile('subscriptcatforum', 'plug'));

// Отображаем категории в виде дерева
foreach ($category_tree as $category) {
    $t->assign([
        'CATEGORY_CODE' => $category['structure_code'], // Код категории
        'CATEGORY_ID' => $category['structure_id'], // ID категории
        'CATEGORY_TITLE' => str_repeat('&nbsp;—&nbsp;', $category['depth'] - 1) . $category['structure_title'], // Отступ для вложенности
        'IS_SELECTED' => in_array($category['structure_code'], $selected_categories) ? 'checked' : '' // Проверяем, выбрана ли категория
    ]);
    $t->parse('MAIN.CATEGORY');
}


    // Генерация и вывод шаблона
    $t->text('MAIN');
