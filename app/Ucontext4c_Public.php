<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once UCONTEXT4C_APP_PATH.'/Ucontext4c_Base.php';

class Ucontext4c_Public extends Ucontext4c_Base
{
	public static function init()
	{
		self::initBase();
	}

	public static function filterContent($content)
	{
		global $wpdb, $post;

		$display = (int)get_option('ucontext4c_links_display', 0);

		if (!$display || ($display == 1 && $post->post_type == 'post') || ($display == 2 && $post->post_type == 'page'))
		{
			if (!(int)get_post_meta($post->ID, 'ucontext4c_disable', true))
			{
				$keyword_list = self::getPostKeywordList($post);

				$content = Ucontext4c_Intext::addInTextLinks($content, $keyword_list, @get_option('ucontext4c_max_links', 5));
			}
		}

		return $content;
	}

	public static function getPostKeywordList($post)
	{
		global $wpdb;

		require_once UCONTEXT4C_APP_PATH.'/Ucontext4c_Intext.php';
		require_once UCONTEXT4C_APP_PATH.'/Ucontext4c_Keyword.php';

		Ucontext4c_Intext::$settings = array(
		'intext_class'	=> get_option('ucontext4c_intext_class'),
		'nofollow'		=> get_option('ucontext4c_nofollow'),
		'new_window'	=> get_option('ucontext4c_new_window')
		);

		$keyword_list = array();

		$ucontext4c_manual_keywords = get_post_meta($post->ID, 'ucontext4c_manual_keywords', true);
		$ucontext4c_manual_keywords = explode(',', strtolower($ucontext4c_manual_keywords));

		if (is_array($ucontext4c_manual_keywords))
		{
			foreach ($ucontext4c_manual_keywords as $keyword)
			{
				$keyword = trim($keyword);

				if ($keyword)
				{
					$keyword_list[$keyword] = array();
				}
			}
		}

		$ucontext4c_site_keywords = get_option('ucontext4c_site_keywords');
		$ucontext4c_site_keywords = explode(',', strtolower($ucontext4c_site_keywords));

		if (is_array($ucontext4c_site_keywords))
		{
			foreach ($ucontext4c_site_keywords as $keyword)
			{
				$keyword = trim($keyword);

				if ($keyword)
				{
					$keyword_list[$keyword] = array();
				}
			}
		}

		if (!(int)@get_post_meta($post->ID, 'ucontext4c_processed', TRUE))
		{
			self::saveKeywordsToMainList(array_keys($keyword_list));
		}

		$ucontext4c_auto_keywords = unserialize(get_post_meta($post->ID, 'ucontext4c_auto_keywords', true));

		if (!is_array($ucontext4c_auto_keywords) || !count($ucontext4c_auto_keywords))
		{
			$ucontext4c_auto_keywords = Ucontext4c_Keyword::findKeywordsInContent($post->post_title, $post->post_content, array_keys($keyword_list));

			update_post_meta($post->ID, 'ucontext4c_auto_keywords', serialize($ucontext4c_auto_keywords));

			Ucontext4c_Base::saveKeywordsToMainList(array_keys($ucontext4c_auto_keywords), 'auto');
		}

		if (is_array($ucontext4c_auto_keywords))
		{
			foreach ($ucontext4c_auto_keywords as $keyword => $count)
			{
				$keyword = trim($keyword);

				if ($keyword)
				{
					$keyword_list[$keyword] = array();
				}
			}
		}

		if (!(int)@get_post_meta($post->ID, 'ucontext4c_processed', TRUE))
		{
			self::saveKeywordsToMainList(array_keys($keyword_list), 'auto');

			update_post_meta($post->ID, 'ucontext4c_processed', 1);
		}

		if (is_array($keyword_list))
		{
			$in_list = array();
			foreach ($keyword_list as $keyword => $keyword_data)
			{
				$in_list[] = '"'.addslashes($keyword).'"';
			}

			if ($in_list)
			{
				$data_list = $wpdb->get_results('SELECT keyword_id, product_id, keyword, search_results FROM '.self::$table['keyword'].' WHERE keyword IN ('.implode(',', $in_list).') AND disabled = 0', ARRAY_A);

				if ($data_list)
				{
					$aws_check = 0;

					foreach ($data_list as $data)
					{
						$search_results = unserialize($data['search_results']);

						if (is_array($search_results))
						{
							if ($data['product_id'] && isset($search_results[$data['product_id']]))
							{
								$keyword_list[$data['keyword']]['title'] = $search_results[$data['product_id']]['title'];
								$keyword_list[$data['keyword']]['url'] = $search_results[$data['product_id']]['url'];
							}
							else
							{
								$temp = array_shift($search_results);

								$keyword_list[$data['keyword']]['title'] = $temp['title'];
								$keyword_list[$data['keyword']]['url'] = $temp['url'];
							}
						}
					}
				}
			}
		}

		foreach ($keyword_list as $keyword => $keyword_data)
		{
			if (!isset($keyword_data['url']) || !trim($keyword_data['url']))
			{
				unset($keyword_list[$keyword]);
			}
			else
			{
				$keyword_list[$keyword]['url'] = trim(home_url(), '/').'/'.get_option('ucontext4c_redirect_slug', 'recommends').'/'.$post->ID.'/'.urlencode($keyword);
			}
		}

		return $keyword_list;
	}
}