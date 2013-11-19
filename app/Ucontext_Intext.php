<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext_Intext
{
	public static $content = '';

	public static $keywords = array();

	public static $max_links = 5;

	public static $kw_max = array();

	public static $kw_totals = array();

	public static $kw_indexes = array();

	public static $current_keyword = '';

	public static $current_index = 0;

	public static $mask_links_list = array();

	public static $mask_html_list = array();

	public static $keyword_data = '';

	public static $settings = array();


	public static function addInTextLinks($content, $keywords, $max_links = 5)
	{
		self::$content = trim($content);
		self::$keywords = $keywords;
		self::$max_links = intval($max_links);

		if (is_array(self::$keywords) && count(self::$keywords))
		{
			self::$kw_totals = array();
			self::$kw_max = array();
			self::$kw_indexes = array();

			self::maskLinks();

			self::maskHtml();

			self::loadTotals();

			self::calcMaxToDisplay();

			self::addInlineLinks();

			self::unmaskHtml();
				
			self::unmaskLinks();
		}

		return self::$content;
	}

	public static function maskLinks()
	{
		if (preg_match_all('/\<a\ .*?\<\/a\>/is', self::$content, $matches))
		{
			if (is_array($matches[0]))
			{
				foreach ($matches[0] as $match)
				{
					$hash = '|'.md5($match).'|';

					self::$mask_links_list[$hash] = $match;

					self::$content = str_replace($match, $hash, self::$content);
				}
			}
		}
	}

	public static function maskHtml()
	{
		$mask_search = array('h1','h2','h3','h4','h5','h6','strong','b');

		foreach ($mask_search as $tag)
		{
			if (preg_match_all('/\<'.$tag.'.*?\<\/'.$tag.'\>/is', self::$content, $matches))
			{
				if (is_array($matches[0]))
				{
					foreach ($matches[0] as $match)
					{
						$hash = '|'.md5($match).'|';

						self::$mask_html_list[$hash] = $match;

						self::$content = str_replace($match, $hash, self::$content);
					}
				}
			}
		}

		if (preg_match_all('/\<.*?\>/is', self::$content, $matches))
		{
			if (is_array($matches[0]))
			{
				foreach ($matches[0] as $match)
				{
					$hash = '|'.md5($match).'|';

					self::$mask_html_list[$hash] = $match;

					self::$content = str_replace($match, $hash, self::$content);
				}
			}
		}
	}

	public static function unmaskHtml()
	{
		if (is_array(self::$mask_html_list))
		{
			foreach (self::$mask_html_list as $hash => $match)
			{
				self::$content = str_replace($hash, $match, self::$content);
			}
		}
	}

	public static function unmaskLinks()
	{
		if (is_array(self::$mask_links_list))
		{
			foreach (self::$mask_links_list as $hash => $match)
			{
				self::$content = str_replace($hash, $match, self::$content);
			}
		}
	}

	public static function loadTotals()
	{
		$n_max_links = 0;

		if (is_array(self::$keywords))
		{
			foreach (self::$keywords as $keyword => $keyword_data)
			{
				self::$keywords[$keyword]['count'] = preg_match_all('/(^|[^a-z])(' . preg_quote($keyword) . ')([^a-z]|$)/is', self::$content, $matches);

				self::$kw_totals[$keyword] = self::$keywords[$keyword]['count'];

				$n_max_links += self::$keywords[$keyword]['count'];
			}
		}

		if ($n_max_links < self::$max_links)
		{
			self::$max_links = $n_max_links;
		}
	}

	public static function calcMaxToDisplay()
	{
		$total = 0;

		while ($total < self::$max_links)
		{
			if (is_array(self::$kw_totals))
			{
				foreach (self::$kw_totals as $keyword => $count)
				{
					if (!isset(self::$keywords[$keyword]['max']))
					{
						self::$keywords[$keyword]['max'] = 0;
					}

					if (intval(self::$keywords[$keyword]['max']) < $count)
					{
						self::$keywords[$keyword]['max'] = intval(self::$keywords[$keyword]['max']) + 1;
						$total++;

						if ($total == self::$max_links)
						{
							break 2;
						}
					}
				}
			}
		}
	}

	public static function addInlineLinks()
	{
		if (is_array(self::$keywords))
		{
			foreach (self::$keywords as $keyword => $keyword_data)
			{
				if ($keyword_data['count'])
				{
					self::$keyword_data = $keyword_data;

					self::$current_index = 0;
					self::$current_keyword = $keyword;

					if ((int)@self::$keywords[$keyword]['count'] > (int)@self::$keywords[$keyword]['max'])
					{
						$inc = round((int)@self::$keywords[$keyword]['count'] / ((int)@self::$keywords[$keyword]['max'] + 1));

						$count = 0;
						$running = 0;

						if (!isset(self::$kw_indexes[$keyword]))
						{
							self::$kw_indexes[$keyword] = array();
						}

						while ($running <= (int)@self::$keywords[$keyword]['count'] && count(self::$kw_indexes[$keyword]) < (int)@self::$keywords[$keyword]['max'])
						{
							$running += $inc;
							self::$kw_indexes[$keyword][$running] = $running;
						}
					}
					else
					{
						for ($i = 1; $i <= (int)@self::$keywords[$keyword]['max']; $i++)
						{
							self::$kw_indexes[$keyword][$i] = $i;
						}
					}

					self::$content = preg_replace_callback('/(^|[^a-z])(' . preg_quote(trim($keyword)) . ')([^a-z]|$)/is', array('self', 'makeLink'), self::$content);
				}
			}
		}
	}

	public static function makeLink($matches)
	{
		self::$current_index++;

		if (!isset(self::$kw_indexes[self::$current_keyword][self::$current_index]))
		{
			self::$kw_indexes[self::$current_keyword][self::$current_index] = NULL;
		}

		if (self::$kw_indexes[self::$current_keyword][self::$current_index])
		{
			$attribs = '';

			$ucontext_intext_class = self::$settings['intext_class'];
			if ($ucontext_intext_class)
			{
				$attribs .= ' class="' . $ucontext_intext_class . '"';
			}

			if (intval(self::$settings['nofollow']))
			{
				$attribs .= ' rel="nofollow"';
			}

			if (intval(self::$settings['new_window']))
			{
				$attribs .= ' target="_blank"';
			}

			$link = '<a href="' . self::$keyword_data['url'] . '"'.$attribs.'>' . $matches[2] . '</a>';

			$hash = '|'.md5(rand() . $link . serialize($matches)).'|';

			self::$mask_links_list[$hash] = $link;

			return $matches[1].$hash.$matches[3];
		}
		else
		{
			return $matches[1].$matches[2].$matches[3];
		}
	}
}