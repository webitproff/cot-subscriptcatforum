<?php

/**
 * [BEGIN_COT_EXT]
 * Hooks=tools
 * [END_COT_EXT]
 */

defined('COT_CODE') or die('Wrong URL');

// Подключаем необходимые файлы и библиотеки
require_once cot_incfile('subscriptcatforum', 'plug');

// Обрабатываем действия добавления и удаления подписчиков
$a = cot_import('a', 'G', 'ALP');

if ($a === 'add_user') {
    $category_id = cot_import('category_id', 'G', 'TXT'); // Получаем код категории
    $user_id = cot_import('user_id', 'P', 'INT'); // ID пользователя из POST-запроса

    if ($category_id && $user_id) {
        $exists = $db->query("SELECT COUNT(*) FROM {$db_x}subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $category_id])->fetchColumn();
        
        if (!$exists) {
            $db->insert($db_x . "subscriptcatforum", [
                'user_id' => $user_id,
                'cat_forum_subs' => $category_id,
                'created_at' => time()
            ]);
            cot_message('Пользователь добавлен в подписку');
        } else {
            cot_message('Этот пользователь уже подписан на данную категорию', 'warning');
        }
    }
    cot_redirect(cot_url('admin', 'm=other&p=subscriptcatforum', '', true));
    exit;
}

if ($a === 'remove_user') {
    $category_id = cot_import('category_id', 'G', 'TXT');
    $user_id = cot_import('user_id', 'G', 'INT');

    if ($category_id && $user_id) {
        $db->delete($db_x . "subscriptcatforum", "user_id = ? AND cat_forum_subs = ?", [$user_id, $category_id]);
        cot_message('Пользователь удалён из подписки');
    }
    cot_redirect(cot_url('admin', 'm=other&p=subscriptcatforum', '', true));
    exit;
}

// Получаем все категории форума
$categories = $db->query("SELECT structure_code, structure_id, structure_title, structure_path FROM $db_structure WHERE structure_area = 'forums' ORDER BY structure_path ASC")->fetchAll();

// Формируем массив иерархии категорий
$category_tree = [];
foreach ($categories as $category) {
    $levels = explode('.', trim($category['structure_path'], '.'));
    $depth = count($levels);
    $category['depth'] = $depth;
    $category_tree[] = $category;
}

// Получаем подписчиков для каждой категории
$subscriptcatforum_by_category = [];
foreach ($category_tree as $category) {
    $subscriptcatforum = $db->query(
        "SELECT u.user_id, u.user_name 
         FROM {$db_x}subscriptcatforum AS s
         LEFT JOIN {$db_x}users AS u ON s.user_id = u.user_id 
         WHERE s.cat_forum_subs = ?",
        [$category['structure_code']]
    )->fetchAll();
    
    $subscriptcatforum_by_category[$category['structure_code']] = $subscriptcatforum ?: [];
}

// Отображаем категории и их подписчиков
$t = new XTemplate(cot_tplfile('subscriptcatforum.admin', 'plug'));
cot_display_messages($t);

foreach ($category_tree as $category) {
    $t->assign([
        'CATEGORY_CODE' => $category['structure_code'],
        'CATEGORY_ID' => $category['structure_id'],
        'CATEGORY_TITLE' => str_repeat('&nbsp;—&nbsp;', $category['depth'] - 1) . $category['structure_title'],
    ]);

    $t->reset('MAIN.CATEGORY.SUBSCRIBERS');

    if (!empty($subscriptcatforum_by_category[$category['structure_code']])) {
        foreach ($subscriptcatforum_by_category[$category['structure_code']] as $subscriber) {
            $t->assign([
                'USER_ID' => $subscriber['user_id'],
                'USER_NAME' => htmlspecialchars($subscriber['user_name']),
                'REMOVE_USER_FORM' => cot_url('admin', 'm=other&p=subscriptcatforum&a=remove_user&user_id=' . $subscriber['user_id'] . '&category_id=' . $category['structure_code']),
            ]);
            $t->parse('MAIN.CATEGORY.SUBSCRIBERS');
        }
    }

    $t->assign('ADD_USER_FORM_ACTION', cot_url('admin', 'm=other&p=subscriptcatforum&a=add_user&category_id=' . $category['structure_code']));
    $t->parse('MAIN.CATEGORY');
}

$t->parse();
$pluginBody = $t->text('MAIN');
