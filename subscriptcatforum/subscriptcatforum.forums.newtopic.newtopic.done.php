<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.newtopic.newtopic.done
[END_COT_EXT]
==================== */


defined('COT_CODE') or die('Wrong URL');

// Функция для записи в лог
function log_to_file($message) {
    $logfile = __DIR__ . '/subscription_debug.log';  // Путь к файлу лога в папке плагина
    $date = date('Y-m-d H:i:s');  // Текущая дата с временем
    file_put_contents($logfile, "[$date] $message\n", FILE_APPEND);  // Записываем в файл
}

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
} */

log_to_file('Запуск обработки новой темы форума.');

// Получаем данные о категории форума
$cat_forum_subs = cot_import('s', 'G', 'TXT');  // Категория форума
$topic_id = cot_import('q', 'G', 'INT'); // Получаем ID темы из GET-параметров
//Cot::$topic_id = $q; // ID новой темы

log_to_file("Получение данных для темы с ID: $topic_id");

// Получаем название новой темы и данные о категории из таблицы forum_topics
$topic_data = Cot::$db->query("SELECT ft_title, ft_cat FROM $db_forum_topics WHERE ft_id = ?", [$topic_id])->fetch();
$topic_title = $topic_data['ft_title'];
$category_title = Cot::$db->query("SELECT structure_title FROM $db_structure WHERE structure_code = ?", [$topic_data['ft_cat']])->fetchColumn(); // Получаем название категории
log_to_file("Название темы: $topic_title, категория: $category_title");

// Получаем данные последнего поста в теме
$post_data = Cot::$db->query("SELECT fp_id, fp_postername, fp_text, fp_creation FROM $db_forum_posts WHERE fp_topicid = ? ORDER BY fp_creation DESC LIMIT 1", [$topic_id])->fetch();
$poster_name = $post_data['fp_postername'];
$post_id = $post_data['fp_id'];
$post_text = strip_tags($post_data['fp_text']);  // Убираем HTML-теги из текста поста

$topic_url = cot_url('forums', "m=posts&q=$topic_id", '', true); // Ссылка на тему

$post_url = cot_url('forums', "m=posts&id=" . $post_data['fp_id'] . "&n=last", "#bottom");  // Ссылка на последний пост в теме

log_to_file("Последний пост. Автор: $poster_name, текст: $post_text");

// Формируем сообщение для отправки уведомления
$message = "Здравствуйте!\n\nВ категории \"$category_title\" была создана новая тема: \"$topic_title\".\n\n";
$message .= "Раздел: " . $category_title . "\n";  // Название категории
$message .= "Тема: " . $topic_title . "\n";  // Название темы
$message .= "Автор поста: " . $poster_name . "\n";  // Имя автора поста
$message .= "Текст поста: \n" . $post_text . "\n\n";  // Текст последнего поста
$message .= "Ссылка на топик: " . COT_ABSOLUTE_URL . $topic_url . "\n";  // Ссылка на тему
$message .= "Ссылка на пост: " . COT_ABSOLUTE_URL . $post_url . "\n";  // Ссылка на последний пост

log_to_file("Сообщение для отправки сформировано. Отправка уведомлений подписчикам.");

// Получаем список подписчиков для данной категории
$subscribers = Cot::$db->query("SELECT user_id FROM $db_subscriptcatforum WHERE cat_forum_subs = ?", [$cat_forum_subs])->fetchAll();

// Логируем количество подписчиков
log_to_file("Найдено подписчиков: " . count($subscribers));

// Если есть подписчики, отправляем уведомления
if (!empty($subscribers)) {
    foreach ($subscribers as $subscriber) {
        $user_id = $subscriber['user_id'];

        // Получаем email пользователя
        $user_email = Cot::$db->query("SELECT user_email FROM $db_users WHERE user_id = ?", [$user_id])->fetchColumn();

        if ($user_email) {
            // Формируем тему письма
            $subject = "Новая тема: $topic_title";

            // Логируем факт отправки письма
            log_to_file("Отправка письма на email: $user_email");

            // Отправляем уведомление
            cot_mail($user_email, $subject, $message);
        }
    }
}

log_to_file('Обработка завершена.');
