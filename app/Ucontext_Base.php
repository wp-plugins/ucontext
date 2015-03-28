<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext_Base
{
	public static $name			= 'ucontext';

	public static $action		= NULL;

	public static $table		= NULL;

	public static $context		= NULL;


	public static function initBase()
	{
		if (!self::$table)
		{
			global $wpdb;

			self::$table = array(
			'cache'			=> $wpdb->base_prefix.'ucontext_cache',
			'click_log'		=> $wpdb->base_prefix.'ucontext_click_log',
			'keyword'		=> $wpdb->base_prefix.'ucontext_keyword',
			'spider_agent'	=> $wpdb->base_prefix.'ucontext_spider_agent'
			);
		}
	}

	public static function processPost($post_id, $force = false)
	{
		if ($force || !(int)get_post_meta($post_id, 'ucontext_last_process', true))
		{
			if ((int)$post_id && !wp_is_post_revision($post_id))
			{
				$post = get_post($post_id);

				require_once UCONTEXT_APP_PATH.'/Ucontext_Keyword.php';

				$keyword_hints = get_post_meta($post_id, 'ucontext_manual_keywords', true);

				$auto_keywords = Ucontext_Keyword::findKeywordsInContent($post->post_title, $post->post_content, $keyword_hints);

				update_post_meta($post_id, 'ucontext_auto_keywords', $auto_keywords);
				update_post_meta($post_id, 'ucontext_last_process', current_time('timestamp'));

				self::saveKeywordsToMainList(array_keys($auto_keywords), 'auto');
			}
		}
	}

	public static function saveKeywordsToMainList($keyword_list, $type = 'auto')
	{
		if (!is_array($keyword_list))
		{
			$keyword_list = explode(',', strtolower($keyword_list));
		}

		if ($type == 'auto' && (int)@get_option('ucontext_no_autokeywords', 0))
		{
			return NULL;
		}

		foreach ($keyword_list as $keyword)
		{
			$keyword = trim($keyword);

			if ($keyword)
			{
				global $wpdb;

				$keyword_id = $wpdb->get_var('SELECT keyword_id FROM '.self::$table['keyword'].' WHERE keyword = "'.addslashes($keyword).'"');

				if (!(int)$keyword_id)
				{
					$record = array(
					'keyword'		=> $keyword,
					'custom_search'	=> $keyword,
					'created'		=> current_time('timestamp'),
					'modified'		=> current_time('timestamp')
					);

					$wpdb->insert(self::$table['keyword'], $record);

					require_once UCONTEXT_APP_PATH.'/Ucontext_Keyword.php';
				}
			}
		}
	}

	public static function array_stripslashes($array)
	{
		if (is_array($array))
		{
			foreach ($array as $field => $value)
			{
				if (is_array($value))
				{
					$array[$field] = self::array_stripslashes($value);
				}
				else
				{
					$array[$field] = stripslashes($value);
				}
			}
		}

		return $array;
	}

	public static function setCache($namespace, $key, $data, $expire_seconds = 86400)
	{
		global $wpdb;

		$wpdb->query('DELETE FROM '.self::$table['cache'].' WHERE expire_datetime <= NOW()');

		if (is_array($data))
		{
			$data = serialize($data);
		}

		$expire_datetime = current_time('timestamp') + (int)$expire_seconds;

		$wpdb->query('REPLACE INTO '.self::$table['cache'].' (`namespace`, `key`, `data`, `expire_datetime`) VALUES ("'.addslashes((string)$namespace).'", "'.addslashes((string)$key).'", "'.addslashes((string)$data).'", FROM_UNIXTIME(UNIX_TIMESTAMP() + '.(int)$expire_seconds.'))');
	}

	public static function getCache($namespace, $key)
	{
		global $wpdb;

		$wpdb->query('DELETE FROM '.self::$table['cache'].' WHERE expire_datetime <= NOW()');

		$data = $wpdb->get_var('SELECT `data` FROM '.self::$table['cache'].' WHERE `namespace` = "'.addslashes($namespace).'" AND `key` = "'.addslashes($key).'"');

		if (isset($data))
		{
			return $data;
		}

		return FALSE;
	}
}