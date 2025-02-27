<?php
require_once cot_langfile('subscriptcatforum', 'plug');
require_once cot_incfile('forums', 'module');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('plug', 'subscriptcatforum');
//cot_block($usr['auth_read']);

require_once cot_incfile('users', 'module');

// Global variables
global $cfg, $usr, $db, $db_x, $db_subscriptcatforum, $db_structure, $db_forum_topics, $R, $L;


// Register tables
cot::$db->registerTable('subscriptcatforum');


// Функция для получения всех подписок
function get_all_subscriptcatforum() {
    global $cfg, $usr, $db, $db_x, $db_subscriptcatforum;
    return $db->query("SELECT user_id, cat_forum_subs FROM $db_subscriptcatforum ORDER BY user_id")->fetchAll();
}

// Функции для добавления и удаления подписок (пример)
function check_existing_subscription($user_id, $forum_id) {
    global $cfg, $usr, $db, $db_x, $db_subscriptcatforum;
    return $db->query("SELECT COUNT(*) FROM $db_subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $forum_id])->fetchColumn() > 0;
}

function subscribe_user($user_id, $forum_id) {
    global $cfg, $usr, $db, $db_x, $db_subscriptcatforum;
    $db->query("INSERT INTO $db_subscriptcatforum (user_id, cat_forum_subs, created_at) VALUES (?, ?, ?)", [$user_id, $forum_id, time()]);
}

function unsubscribe_user($user_id, $forum_id) {
    global $cfg, $usr, $db, $db_x, $db_subscriptcatforum;
    $db->query("DELETE FROM $db_subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $forum_id]);
}

function log_to_file($message) {
	$logfile = __DIR__ . '/cron_subscription_sender.log';
	$date = date('Y-m-d H:i:s');
	file_put_contents($logfile, "[$date] $message\n", FILE_APPEND);
}
/**
 * заготовка под https://exapmle.com/index.php?r=subscriptcatforum&run_cron=1
 * ($_GET['run_cron'] == '1')
 * 
 */
/*  
if ($_GET['run_cron'] == '1') {
	log_to_file("Содержимое \$_GET: " . print_r($_GET, true));

	function subscriptcatforum_run_cron() {
		return [
			'topic_move_done' => subscriptcatforum_run_cron_topic_move_done(),
			'posts_new_done' => subscriptcatforum_run_cron_posts_new_done(),
			'editpost_update_done' => subscriptcatforum_run_cron_editpost_update_done()
		];
	}

    echo json_encode(['status' => 'ok', 'message' => 'Cron executed']);
    exit;
} */