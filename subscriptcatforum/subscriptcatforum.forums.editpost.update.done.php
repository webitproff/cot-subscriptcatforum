<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.editpost.update.done
[END_COT_EXT]
==================== */


// Проверка, чтобы код выполнялся только в рамках правильного контекста
defined('COT_CODE') or die('Wrong URL');

// Подключаем необходимые файлы и библиотеки для работы с подписками и форумом
require_once cot_incfile('subscriptcatforum', 'plug');  // Подключение плагина подписок
require_once cot_incfile('forums', 'module');  // Подключение модуля форумов
global $db, $db_x, $db_subscriptcatforum, $db_users, $db_forum_topics, $db_forum_posts, $db_forum_stats, $usr;

// Инициализация переменных для работы с таблицами базы данных с учетом префикса
$db_subscriptcatforum = $db_x . 'subscriptcatforum';  // Таблица подписок
$db_users = $db_x . 'users';  // Таблица пользователей
$db_forum_topics = $db_x . 'forum_topics';  // Таблица тем форума
$db_forum_posts = $db_x . 'forum_posts';  // Таблица постов форума

/**
 * заготовка под https://exapmle.com/index.php?r=subscriptcatforum&run_cron=1
 * ($_GET['run_cron'] == '1')
 * 
 */
/* 
// Получаем настройки плагина
$enable_cron_mail = Cot::$cfg['plugin']['subscriptcatforum']['enable_cron_mail']; // Получаем настройку из конфигурации плагина

// Проверка, если cron включен
if ($enable_cron_mail == 1) {
    // Если cron активен, то не отправляем уведомления через хук
    return;
}
 */

// Функция для получения текущей даты и времени
function get_current_datetime() {
    return date('Y-m-d H:i:s');  // Возвращаем текущую дату и время в формате Y-m-d H:i:s
}

// Логируем все входные параметры для отладки
file_put_contents(
    __DIR__ . '/subscription_debug.log',
    "[" . get_current_datetime() . "] Все входные параметры: " . var_export($_GET, true) . "\n",  // Логируем параметры запроса
    FILE_APPEND  // Добавляем в файл без перезаписи
);

// Получаем код категории из параметра 's' запроса (например, из URL)
$cat_forum_subs = cot_import('s', 'G', 'TXT');  // Параметр 's' - это код категории форума

// Логируем полученный код категории для отладки
file_put_contents(
    __DIR__ . '/subscription_debug.log',
    "[" . get_current_datetime() . "] Получена категория: " . var_export($cat_forum_subs, true) . "\n",  // Логируем код категории
    FILE_APPEND
);

// Проверка, если категория не передана — прерываем выполнение скрипта
if (empty($cat_forum_subs)) {
    error_log("[" . get_current_datetime() . "] Ошибка: категория не передана.");  // Логируем ошибку в системный лог
    file_put_contents(__DIR__ . '/subscription_debug.log', "[" . get_current_datetime() . "] Ошибка: категория не передана.\n", FILE_APPEND);  // Логируем в файл
    return;  // Прерываем выполнение скрипта
}

// Получаем подписчиков для указанной категории
$subscribers = $db->query("
    SELECT u.user_email 
    FROM $db_subscriptcatforum s
    JOIN $db_users u ON s.user_id = u.user_id
    WHERE s.cat_forum_subs = ?", [$cat_forum_subs])->fetchAll();  // SQL запрос для получения подписчиков на категорию

// Логируем информацию о подписчиках для отладки
file_put_contents(
    __DIR__ . '/subscription_debug.log',
    "[" . get_current_datetime() . "] Подписчики для категории: " . var_export($subscribers, true) . "\n",  // Логируем подписчиков
    FILE_APPEND
);

// Получаем ID темы из параметра запроса 'q'
$topic_id = cot_import('q', 'G', 'INT');  // Параметр 'q' — это ID темы форума

// Логируем ID темы для отладки
file_put_contents(
    __DIR__ . '/subscription_debug.log',
    "[" . get_current_datetime() . "] Получен ID темы: " . var_export($topic_id, true) . "\n",  // Логируем ID темы
    FILE_APPEND
);

// Получаем информацию о последнем посте в теме (по категории и ID темы)
$post_data = $db->query("
    SELECT fp_id, fp_topicid, fp_cat, fp_text, fp_postername
    FROM $db_forum_posts
    WHERE fp_cat = ? AND fp_topicid = ? ORDER BY fp_creation DESC LIMIT 1", [$cat_forum_subs, $topic_id])->fetch();

// Если пост найден, извлекаем данные
if ($post_data) {
    $post_id = $post_data['fp_id'];  // ID поста
    $topic_id = $post_data['fp_topicid'];  // ID темы
    $post_text = strip_tags($post_data['fp_text']);  // Убираем HTML-теги из текста поста
    $poster_name = $post_data['fp_postername'];  // Имя автора поста
} else {
    // Если пост не найден, логируем это и прекращаем выполнение
    file_put_contents(__DIR__ . '/subscription_debug.log', "[" . get_current_datetime() . "] Пост не найден для темы ID: " . var_export($topic_id, true) . "\n", FILE_APPEND);
    return;  // Прерываем выполнение
}

// Получаем информацию о теме по её ID (название темы и код категории)
$topic_data = $db->query("
    SELECT ft_title, ft_cat
    FROM $db_forum_topics
    WHERE ft_id = ?", [$topic_id])->fetch();

// Если данные о теме найдены, сохраняем название темы
if ($topic_data) {
    $topic_title = $topic_data['ft_title'];  // Название темы
} else {
    $topic_title = "Без названия";  // Если тема не найдена, устанавливаем значение по умолчанию
}

// Получаем данные о категории форума, если это код категории
$category_data = $db->query("
    SELECT structure_title 
    FROM $db_structure 
    WHERE structure_code = ?", [$topic_data['ft_cat']])->fetch();

// Если данные о категории найдены, сохраняем название категории
if ($category_data) {
    $category_title = $category_data['structure_title'];  // Название категории
} else {
    $category_title = "Без категории";  // Если категория не найдена, устанавливаем значение по умолчанию
}

// Формируем ссылку на тему
$topic_url = cot_url('forums', 'm=posts&q=' . $topic_id);  // Ссылка на тему
$post_url = cot_url('forums', "m=posts&id=" . $post_id ."&n=last", "#bottom");  // Ссылка на последний пост в теме

// Если подписчики найдены, отправляем уведомления
if (!empty($subscribers)) {
    // Формируем сообщение для отправки
    $subject = "Обновления на форуме!";  // Тема письма
    $message = "Привет!\n\n";
    $message .= "В разделе форума, на который вы подписаны, обновления.\n\n";
    $message .= "Раздел: " . $category_title . "\n";  // Название категории
    $message .= "Тема: " . $topic_title . "\n";  // Название темы
    $message .= "Автор поста: " . $poster_name . "\n";  // Имя автора поста
    $message .= "Текст поста: \n" . $post_text . "\n\n";  // Текст последнего поста
    $message .= "Ссылка на топик: " . COT_ABSOLUTE_URL .  $topic_url . "\n";  // Ссылка на тему
    $message .= "Ссылка на пост: " . COT_ABSOLUTE_URL .  $post_url . "\n";  // Ссылка на последний пост
    $message .= "Чтобы отписаться, измените свои подписки в профиле.";  // Инструкция по отписке

    // Заголовки для отправки письма
    $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Отправляем письма всем подписчикам
    foreach ($subscribers as $subscriber) {
        cot_mail($subscriber['user_email'], $subject, $message, $headers);  // Отправка письма

        // Логируем факт отправки письма
        file_put_contents(
            __DIR__ . '/subscription_debug.log',
            "[" . get_current_datetime() . "] Отправлено уведомление: " . var_export($subscriber['user_email'], true) . "\n",
            FILE_APPEND
        );
        error_log("[" . get_current_datetime() . "] Отправлено уведомление: " . var_export($subscriber['user_email'], true));  // Логируем отправку в системный лог
    }
} else {
    // Если подписчиков нет, логируем это
    file_put_contents(__DIR__ . '/subscription_debug.log', "[" . get_current_datetime() . "] Подписчиков не найдено.\n", FILE_APPEND);
    error_log("[" . get_current_datetime() . "] Подписчиков не найдено для категории: " . var_export($cat_forum_subs, true));  // Логируем отсутствие подписчиков
}
