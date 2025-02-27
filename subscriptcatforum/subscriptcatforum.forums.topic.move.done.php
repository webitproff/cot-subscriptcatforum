<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.topic.move.done
[END_COT_EXT]
==================== */


defined('COT_CODE') or die('Wrong URL');

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
// Инициализация переменных для работы с таблицами базы данных с учетом префикса
$db_subscriptcatforum = $db_x . 'subscriptcatforum';  // Таблица подписок
$db_users = $db_x . 'users';  // Таблица пользователей
$db_forum_topics = $db_x . 'forum_topics';  // Таблица тем форума
$db_forum_posts = $db_x . 'forum_posts';  // Таблица постов форума

log_to_file('Запуск обработки перемещения темы форума.');

// Получаем данные о перемещенной теме
$topic_id = $q;  // ID перемещенной темы
$new_cat_code = $ns;  // Новый код категории
$old_cat_code = $s;  // Старый код категории
log_to_file("Перемещение темы с ID: $topic_id в новый раздел с кодом: $new_cat_code");

// Получаем название новой и старой категории
$new_category_title = Cot::$db->query("SELECT structure_title FROM $db_structure WHERE structure_code = ?", [$new_cat_code])->fetchColumn();
$old_category_title = Cot::$db->query("SELECT structure_title FROM $db_structure WHERE structure_code = ?", [$old_cat_code])->fetchColumn();

// Получаем название темы из таблицы forum_topics
$topic_title = Cot::$db->query("SELECT ft_title FROM $db_forum_topics WHERE ft_id = ?", [$topic_id])->fetchColumn();

// Получаем данные первого поста в теме
$post_data = Cot::$db->query("SELECT fp_text FROM $db_forum_posts WHERE fp_topicid = ? ORDER BY fp_creation ASC LIMIT 1", [$topic_id])->fetch();
$post_text = strip_tags($post_data['fp_text']);  // Убираем HTML-теги из текста первого поста

// Формируем сообщение для отправки уведомлений
$message = "Здравствуйте!\n\nТема \"$topic_title\" была перемещена.\n\n";
$message .= "Новый раздел: " . $new_category_title . "\n";  // Новый раздел
$message .= "Старый раздел: " . $old_category_title . "\n";  // Старый раздел
$message .= "Тема: " . $topic_title . "\n";  // Название темы
$message .= "Текст первого поста в теме: \n" . $post_text . "\n\n";  // Текст первого поста в теме
$message .= "Ссылка на тему: " . COT_ABSOLUTE_URL . cot_url('forums', "m=posts&q=$topic_id", '', true) . "\n";  // Ссылка на тему

log_to_file("Сообщение для отправки сформировано. Отправка уведомлений подписчикам.");

// Получаем список подписчиков для новой категории
$subscribers = Cot::$db->query("
    SELECT u.user_email 
    FROM $db_subscriptcatforum s
    JOIN $db_users u ON s.user_id = u.user_id
    WHERE s.cat_forum_subs = ?", [$new_cat_code])->fetchAll();

// Логируем количество подписчиков
log_to_file("Найдено подписчиков: " . count($subscribers));

// Если есть подписчики, отправляем уведомления
if (!empty($subscribers)) {
    foreach ($subscribers as $subscriber) {
        $user_email = $subscriber['user_email'];

        // Логируем факт получения email-адреса
        log_to_file("Получен email для отправки: $user_email");

        if ($user_email) {
            // Формируем тему письма
            $subject = "Перемещение темы: $topic_title";

            // Логируем факт отправки письма
            log_to_file("Отправка письма на email: $user_email");

            // Логируем тему и тело письма для отладки
            log_to_file("Тема письма: $subject");
            log_to_file("Сообщение: $message");

            // Отправляем уведомление
            cot_mail($user_email, $subject, $message);
        }
    }
} else {
    log_to_file("Нет подписчиков для уведомления.");
}

log_to_file('Обработка перемещения завершена.');
