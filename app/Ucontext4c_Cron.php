<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once UCONTEXT4C_APP_PATH.'/Ucontext4c_Base.php';

class Ucontext4c_Cron extends Ucontext4c_Base
{
	public static function init()
	{
		self::initBase();
	}

	public static function updateKeywordSearchResults()
	{
		global $wpdb;

		$keyword_list = $wpdb->get_results('SELECT keyword_id FROM '.self::$table['keyword'].' WHERE last_updated < '.(current_time('timestamp') - 172800).' LIMIT 150', ARRAY_A);

		if (is_array($keyword_list) && count($keyword_list))
		{
			require_once UCONTEXT4C_APP_PATH.'/Ucontext4c_Keyword.php';

			foreach ($keyword_list as $keyword)
			{
				Ucontext4c_Keyword::getResults($keyword['keyword_id']);
			}
		}
	}

	public static function updateAgents()
	{
		$response = wp_remote_get('http://www.ucontext.com/agents.csv');

		if (!is_wp_error($response))
		{
			global $wpdb;

			$temp = tmpfile();
			fwrite($temp, $response['body']);
			fseek($temp, 0);

			while (!feof($temp))
			{
				if (!@$done)
				{
					$wpdb->query('TRUNCATE '.self::$table['spider_agent']);
					$done = true;
				}

				$data = fgetcsv($temp, 4096);

				if (trim($data[0]))
				{
					$wpdb->query('INSERT INTO '.self::$table['spider_agent'].' (sig) VALUES ("'.esc_sql($data[0]).'")');
				}
			}

			fclose($temp);
		}
	}
}