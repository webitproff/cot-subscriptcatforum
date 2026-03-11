<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.posts.newpost.done
[END_COT_EXT]
==================== */
// Файл subscriptcatforum.forums.posts.newpost.done.php.
defined('COT_CODE') or die('Wrong URL');

// Подключаем необходимые глобальные переменные
global $db, $db_x, $q, $s;

// Определяем имена таблиц
$db_subscriptcatforum = $db_x . 'subscriptcatforum';
$db_users = $db_x . 'users';
$db_forum_posts = $db_x . 'forum_posts';
$db_forum_topics = $db_x . 'forum_topics';

// Функция логирования
function log_to_file_newpostdone($message) {
    $logfile = __DIR__ . '/subscription_debug.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logfile, "[$date] $message\n", FILE_APPEND);
}

log_to_file_newpostdone('=== forums.posts.newpost.done started ===');
log_to_file_newpostdone('$q = ' . var_export($q, true) . ', $s = ' . var_export($s, true));

// ID темы и раздела
$topic_id = (int)$q;
$section_code = $s;

if (empty($topic_id) || empty($section_code)) {
    log_to_file_newpostdone('Ошибка: не переданы topic_id или section_code');
    return;
}

// Получаем данные темы
$topic_data = $db->query("SELECT ft_title FROM $db_forum_topics WHERE ft_id = ?", [$topic_id])->fetch();
if (!$topic_data) {
    log_to_file_newpostdone("Тема с ID $topic_id не найдена");
    return;
}
$topic_title = $topic_data['ft_title'];
log_to_file_newpostdone("Тема: $topic_title");

// Получаем последний пост в теме (новый только что добавленный)
$post_data = $db->query("
    SELECT fp_id, fp_text, fp_posterid, fp_postername, fp_creation 
    FROM $db_forum_posts 
    WHERE fp_topicid = ? 
    ORDER BY fp_creation DESC 
    LIMIT 1", [$topic_id])->fetch();

if (!$post_data) {
    log_to_file_newpostdone("Пост для темы $topic_id не найден");
    return;
}

$post_id = (int)$post_data['fp_id'];
$post_text = strip_tags($post_data['fp_text']);
$post_author_id = (int)$post_data['fp_posterid'];
$poster_name = $post_data['fp_postername'];
$post_timestamp = $post_data['fp_creation']; // Это Unix timestamp
$post_time = date('Y-m-d H:i', $post_timestamp);

log_to_file_newpostdone("Автор поста: $poster_name (ID $post_author_id), время: $post_time, timestamp: $post_timestamp");

// Получаем подписчиков раздела (исключая автора поста)
$subscribers = $db->query("
    SELECT u.user_email, u.user_id 
    FROM $db_subscriptcatforum s
    JOIN $db_users u ON s.user_id = u.user_id
    WHERE s.cat_forum_subs = ? 
    AND u.user_id != ?", [$section_code, $post_author_id])->fetchAll();

log_to_file_newpostdone("Найдено подписчиков: " . count($subscribers));

if (empty($subscribers)) {
    log_to_file_newpostdone("Нет подписчиков для уведомления");
    return;
}

// Формируем ссылки (аналогично файлу для редактирования)
$topic_url = cot_url('forums', 'm=posts&q=' . $topic_id);
$post_url = cot_url('forums', "m=posts&id=" . $post_id . "&n=last", "#bottom");

// Формируем сообщение
$message = "Здравствуйте!\n\nВ теме \"$topic_title\" появился новый пост.\n\n";
$message .= "Раздел: " . $section_code . "\n";
$message .= "Тема: " . $topic_title . "\n";
$message .= "Автор поста: " . $poster_name . "\n";
$message .= "Время публикации: $post_time\n";
$message .= "Текст поста:\n$post_text\n\n";
$message .= "Ссылка на тему: " . COT_ABSOLUTE_URL . $topic_url . "\n";
$message .= "Ссылка на пост: " . COT_ABSOLUTE_URL . $post_url . "\n";

// Отправляем уведомления
foreach ($subscribers as $subscriber) {
    $user_email = $subscriber['user_email'];
    if ($user_email) {
        cot_mail($user_email, "Новый пост в теме: $topic_title", $message);
        log_to_file_newpostdone("Уведомление отправлено на $user_email");
    }
}

log_to_file_newpostdone('=== forums.posts.newpost.done completed ===');