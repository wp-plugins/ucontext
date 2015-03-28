<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once UCONTEXT_APP_PATH.'/Ucontext_Base.php';

class Ucontext_Public extends Ucontext_Base
{
	public static function init()
	{
		self::initBase();
	}

	public static function filterContent($content)
	{
		require_once UCONTEXT_INTEGRATION_PATH.'/Ucontext_Integration.php';

		if (Ucontext_Integration::isValidLicense())
		{
			global $wpdb, $post;

			$display = (int)get_option('ucontext_links_display', 0);

			if (!$display || ($display == 1 && $post->post_type == 'post') || ($display == 2 && $post->post_type == 'page'))
			{
				if (!(int)get_post_meta($post->ID, 'ucontext_disable', true))
				{
					$keyword_list = self::getPostKeywordList($post);

					$content = Ucontext_Intext::addInTextLinks($content, $keyword_list, @get_option('ucontext_max_links', 5));
				}
			}
		}

		return $content;
	}

	public static function getPostKeywordList($post)
	{
		global $wpdb;

		require_once UCONTEXT_APP_PATH.'/Ucontext_Intext.php';
		require_once UCONTEXT_APP_PATH.'/Ucontext_Keyword.php';

		Ucontext_Intext::$settings = array(
		'intext_class'	=> get_option('ucontext_intext_class'),
		'nofollow'		=> get_option('ucontext_nofollow'),
		'new_window'	=> get_option('ucontext_new_window')
		);

		$keyword_list = array();

		$ucontext_manual_keywords = get_post_meta($post->ID, 'ucontext_manual_keywords', true);
		$ucontext_manual_keywords = explode(',', strtolower($ucontext_manual_keywords));

		if (is_array($ucontext_manual_keywords))
		{
			foreach ($ucontext_manual_keywords as $keyword)
			{
				$keyword = trim($keyword);

				if ($keyword)
				{
					$keyword_list[$keyword] = array();
				}
			}
		}

		$ucontext_site_keywords = get_option('ucontext_site_keywords');
		$ucontext_site_keywords = explode(',', strtolower($ucontext_site_keywords));

		if (is_array($ucontext_site_keywords))
		{
			foreach ($ucontext_site_keywords as $keyword)
			{
				$keyword = trim($keyword);

				if ($keyword)
				{
					$keyword_list[$keyword] = array();
				}
			}
		}

		if (!(int)@get_post_meta($post->ID, 'ucontext_processed', TRUE))
		{
			self::saveKeywordsToMainList(array_keys($keyword_list));
		}

		if (!(int)@get_option('ucontext_no_autokeywords', 0))
		{
			$ucontext_auto_keywords = get_post_meta($post->ID, 'ucontext_auto_keywords', true);

			if (!is_array($ucontext_auto_keywords) || !count($ucontext_auto_keywords))
			{
				$ucontext_auto_keywords = Ucontext_Keyword::findKeywordsInContent($post->post_title, $post->post_content, array_keys($keyword_list));

				update_post_meta($post->ID, 'ucontext_auto_keywords', $ucontext_auto_keywords);

				Ucontext_Base::saveKeywordsToMainList(array_keys($ucontext_auto_keywords), 'auto');
			}

			if (is_array($ucontext_auto_keywords))
			{
				foreach ($ucontext_auto_keywords as $keyword => $count)
				{
					$keyword = trim($keyword);

					if ($keyword)
					{
						$keyword_list[$keyword] = array();
					}
				}
			}

			if (!(int)@get_post_meta($post->ID, 'ucontext_processed', TRUE))
			{
				self::saveKeywordsToMainList(array_keys($keyword_list), 'auto');

				update_post_meta($post->ID, 'ucontext_processed', 1);
			}
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
				$slug = trim(@get_option('ucontext_redirect_slug', 'recommends'));

				if (!$slug)
				{
					$slug = 'recommends';
				}

				if (trim(get_option('permalink_structure', '')))
				{
					$keyword_list[$keyword]['url'] = trim(site_url(), '/').'/'.$slug.'/'.$post->ID.'/'.urlencode($keyword);
				}
				else
				{
					$keyword_list[$keyword]['url'] = trim(site_url(), '/').'?'.$slug.'='.urlencode($keyword).'&post_id='.$post->ID;
				}
			}
		}

		return $keyword_list;
	}
}