<?php

if (!(int)@get_option('ucontext_log_archive_done', 0))
{
	set_time_limit(900);
	ignore_user_abort(true);

	$tmpfname = tempnam(sys_get_temp_dir(), "temp_ucontext_log_data");

	$fp = fopen($tmpfname, "w+");

	$request = array(
	'api_key'	=> @get_option('ucontext_api_key'),
	'http_host'	=> site_url()
	);

	$response = wp_remote_post('http://ucontext.com/api.php?method=getArchivedLogs', array('method' => 'POST', 'timeout' => 900, 'body' => $request));

	fwrite($fp, trim($response['body']));

	fseek($fp, 0);

	global $wpdb;

	$count = 0;

	while (!feof($fp))
	{
		$raw = fgetcsv($fp);

		if ($raw[0])
		{
			$count++;

			$record = array(
				'keyword'	=> $raw[0],
				'agent'		=> $raw[1],
				'spider'	=> $raw[2],
				'date_time'	=> $raw[3],
				'year'		=> (int)date('Y', strtotime($raw[3])),
				'month'		=> (int)date('n', strtotime($raw[3])),
				'day'		=> (int)date('j', strtotime($raw[3])),
				'weekday'	=> (int)date('w', strtotime($raw[3])),
				'hour'		=> (int)date('G', strtotime($raw[3]))
			);

			$wpdb->insert(self::$table['click_log'], $record);

			echo '.';
			flush(); flush();

			if (!($count % 200))
			{
				echo '<br />';
			}
		}
	}

	fclose($fp);

	unlink($tmpfname);

	echo '<br />DONE !!!<br />';

	echo 'Return to <a href="admin.php?page='.self::$name.'&action=settings">settings</a>';
	

	update_option('ucontext_log_archive_done', 1);
}