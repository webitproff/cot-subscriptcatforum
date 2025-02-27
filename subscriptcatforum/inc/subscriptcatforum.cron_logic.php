<?php
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('subscriptcatforum', 'plug');

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