<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.posts.new.done
[END_COT_EXT]
==================== */


defined('COT_CODE') or die('Wrong URL');

// Подключаем базу данных и функции
global $db, $db_x;

// Таблицы базы данных
$db_subscriptcatforum = $db_x . 'subscriptcatforum';  // Подписки на разделы
$db_users = $db_x . 'users';  // Пользователи
$db_forum_posts = $db_x . 'forum_posts';  // Посты
$db_forum_topics = $db_x . 'forum_topics';  // Темы форума

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
 
 
// ID темы и раздела
$topic_id = $q; // ID темы
$section_code = $s; // Код раздела

// Получаем данные темы
$topic_data = Cot::$db->query("SELECT ft_title FROM $db_forum_topics WHERE ft_id = ?", [$topic_id])->fetch();
$topic_title = $topic_data['ft_title'];

// Получаем новый пост
$post_data = Cot::$db->query("
    SELECT fp_text, fp_posterid, fp_creation 
    FROM $db_forum_posts 
    WHERE fp_topicid = ? 
    ORDER BY fp_creation DESC 
    LIMIT 1", [$topic_id])->fetch();

$post_text = strip_tags($post_data['fp_text']); // Убираем HTML-теги
$post_author_id = $post_data['fp_posterid'];
$post_time = date('Y-m-d H:i', strtotime($post_data['fp_creation']));

// Получаем подписчиков раздела (кроме автора поста)
$subscribers = Cot::$db->query("
    SELECT u.user_email, u.user_id 
    FROM $db_subscriptcatforum s
    JOIN $db_users u ON s.user_id = u.user_id
    WHERE s.cat_forum_subs = ? 
    AND u.user_id != ?", [$section_code, $post_author_id])->fetchAll();

// Формируем сообщение
$message = "Здравствуйте!\n\nВ теме \"$topic_title\" появился новый пост.\n\n";
$message .= "Автор поста: " . cot_user($post_author_id) . "\n";
$message .= "Время публикации: $post_time\n";
$message .= "Текст поста:\n$post_text\n\n";
$message .= "Ссылка на тему: " . COT_ABSOLUTE_URL . cot_url('forums', "m=posts&q=$topic_id", '', true) . "\n";

// Отправляем уведомления всем подписчикам
foreach ($subscribers as $subscriber) {
    $user_email = $subscriber['user_email'];

    if ($user_email) {
        cot_mail($user_email, "Новый пост в теме: $topic_title", $message);
    }
}
