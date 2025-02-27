<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=ajax
[END_COT_EXT]
==================== */

defined('COT_CODE') or die('Wrong URL');
//$ns = cot_import('ns', 'G', 'TXT'); // Позволит любые символы
//$ns = cot_import('ns', 'G', 'ALP') // Позволит латинские символы и всё
require_once cot_incfile('subscriptcatforum', 'plug');
//require_once cot_incfile('subscriptcatforum', 'plug', 'cron_logic'); // Подключаем логику крон
list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('plug', 'subscriptcatforum');



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