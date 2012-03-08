<?php

/**

Copyright 2010  Summit Media Concepts LLC (email : info@SummitMediaConcepts.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

class Ucontext_Library
{
	var $version = '2.1';

	var $payload_url = 'http://www.ucontext.com/payload.php';
	//var $payload_url = 'http://gozer.dynalias.com/ucontext/www/payload.php';

	var $content = '';

	var $keywords = array();

	var $max_links = 5;

	var $kw_totals = array();

	var $kw_indexes = array();

	var $current_keyword = '';

	var $current_index = 0;

	var $mask_links_list = array();

	var $mask_html_list = array();

	var $keyword_data = '';

	var $post = array();

	var $settings = array();

	var $data = array();


	function getInText()
	{
		$this->post['format'] = 'INLINE';

		$link_list = Ucontext_Library::postToServer($this->post);

		if (is_array($link_list))
		{
			$max_links = count($link_list);

			if ($max_links)
			{
				$result = $this->createInText($this->post['body'], $link_list, count($link_list));
			}
		}

		if (!$result)
		{
			$result = $this->post['body'];
		}

		return $result;
	}

	function setApiKey($api_key)
	{
		$this->post['api_key'] = $api_key;
	}

	function setKeywords($keywords)
	{
		$this->post['keywords'] = trim($keywords);
	}

	function setTitle($title)
	{
		$this->post['title'] = trim($title);
	}

	function setBody($body)
	{
		$this->post['body'] = trim($body);
	}

	function setPermalink($permalink)
	{
		$this->post['permalink'] = trim($permalink);
	}

	function setUrl($url)
	{
		$this->post['url'] = trim($url);
	}

	function setInTextClass($class)
	{
		$this->settings['intext_class'] = trim($class);
	}

	function setNoFollow($nofollow)
	{
		$this->settings['nofollow'] = intval($nofollow);
	}

	function setNewWindow($new_window)
	{
		$this->settings['new_window'] = intval($new_window);
	}

	function setCacheData($data)
	{
		$this->settings['cache'] = $data;
	}

	function postToServer($post)
	{
		$data = NULL;

		if (is_array($this->settings['cache']) && is_array($this->settings['cache']['link_list']))
		{
			$link_list = $this->settings['cache']['link_list'];
		}
		else
		{
			if ($post['url'])
			{
				if (substr($post['url'], 0, 5) == 'https')
				{
					$post['https'] = 'on';
				}

				$temp = str_replace('http://', '', $post['url']);
				$temp = str_replace('https://', '', $temp);

				$post['http_host'] = str_replace(stristr($temp, '/'), '', $temp);

				if (!$post['http_host'])
				{
					$post['http_host'] = $temp;
				}

				$post['request_uri'] = stristr($temp, '/');

				unset($post['url']);
			}
			else
			{
				$post['https']			= $_SERVER['HTTPS'];
				$post['http_host']		= $_SERVER['HTTP_HOST'];
				$post['request_uri']	= $_SERVER['REQUEST_URI'];
			}

			$post['version'] = $this->version;

			if (intval(extension_loaded('curl')))
			{
				$data = unserialize($this->curlPost($post));
			}
			else
			{
				$data = unserialize($this->socketPost($post));
			}

			$link_list = $data['link_list'];
		}

		$this->data = $data;

		return $link_list;
	}

	function curlPost($post)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->payload_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	function socketPost($post)
	{
		$url_info = parse_url($this->payload_url);

		if (is_array($post))
		{
			$fp = fsockopen($url_info['host'], 80, $errno, $errstr, 3);

			if ($fp)
			{
				if (is_array($post))
				{
					foreach ($post as $name => $value)
					{
						$coded_post[] = urlencode($name).'='.urlencode($value);
					}
				}

				$senddata = implode('&', $coded_post);

				$out = 'POST '.(isset($url_info['path'])?$url_info['path']:'/').(isset($url_info['query'])?'?'.$url_info['query']:'').' HTTP/1.0'."\r\n";
				$out .= 'Host: '.$url_info['host']."\r\n";
				$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$out .= 'Content-Length: '.strlen( $senddata )."\r\n";
				$out .= 'Connection: Close'."\r\n\r\n";
				$out .= $senddata;

				fwrite($fp, $out);

				while (!feof($fp))
				{
					$contents .= fgets($fp, 1024);
				}

				list($headers, $result) = explode("\r\n\r\n", $contents, 2);
			}
		}

		return $result;
	}

	function createInText($content, $keywords, $max_links = 5)
	{
		$this->content = trim($content);
		$this->keywords = $keywords;
		$this->max_links = intval($max_links);

		if (is_array($this->keywords) && count($this->keywords))
		{
			$this->kw_totals = array();
			$this->kw_max = array();
			$this->kw_indexes = array();

			$this->maskHtml();

			$this->maskLinks();

			$this->loadTotals();

			$this->calcMaxToDisplay();

			$this->addInlineLinks();

			$this->unmaskLinks();

			$this->unmaskHtml();
		}

		return $this->content;
	}

	function maskLinks()
	{
		if (preg_match_all('/\<a\ .*?\<\/a\>/is', $this->content, $matches))
		{
			if (is_array($matches[0]))
			{
				foreach ($matches[0] as $match)
				{
					$hash = '|'.md5($match).'|';

					$this->mask_links_list[$hash] = $match;

					$this->content = str_replace($match, $hash, $this->content);
				}
			}
		}
	}

	function maskHtml()
	{
		$mask_search = array('h1','h2','h3','h4','h5','h6','strong','b');

		foreach ($mask_search as $tag)
		{
			if (preg_match_all('/\<'.$tag.'.*?\<\/'.$tag.'\>/is', $this->content, $matches))
			{
				if (is_array($matches[0]))
				{
					foreach ($matches[0] as $match)
					{
						$hash = '|'.md5($match).'|';

						$this->mask_html_list[$hash] = $match;

						$this->content = str_replace($match, $hash, $this->content);
					}
				}
			}
		}

		if (preg_match_all('/\<.*?\>/is', $this->content, $matches))
		{
			if (is_array($matches[0]))
			{
				foreach ($matches[0] as $match)
				{
					$hash = '|'.md5($match).'|';

					$this->mask_html_list[$hash] = $match;

					$this->content = str_replace($match, $hash, $this->content);
				}
			}
		}
	}

	function unmaskHtml()
	{
		if (is_array($this->mask_html_list))
		{
			foreach ($this->mask_html_list as $hash => $match)
			{
				$this->content = str_replace($hash, $match, $this->content);
			}
		}
	}

	function unmaskLinks()
	{
		if (is_array($this->mask_html_list))
		{
			foreach ($this->mask_links_list as $hash => $match)
			{
				$this->content = str_replace($hash, $match, $this->content);
			}
		}
	}

	function loadTotals()
	{
		$n_max_links = 0;

		if (is_array($this->keywords))
		{
			foreach ($this->keywords as $keyword => $keyword_data)
			{
				$this->keywords[$keyword]['count'] = preg_match_all('/(^|[^a-z])(' . preg_quote($keyword) . ')([^a-z]|$)/is', $this->content, $matches);

				$this->kw_totals[$keyword] = $this->keywords[$keyword]['count'];

				$n_max_links += $this->keywords[$keyword]['count'];
			}
		}

		if ($n_max_links < $this->max_links)
		{
			$this->max_links = $n_max_links;
		}
	}

	function calcMaxToDisplay()
	{
		$total = 0;

		while ($total < $this->max_links)
		{
			if (is_array($this->kw_totals))
			{
				foreach ($this->kw_totals as $keyword => $count)
				{
					if (intval($this->keywords[$keyword]['max']) < $count)
					{
						$this->keywords[$keyword]['max'] = intval($this->keywords[$keyword]['max']) + 1;
						$total++;

						if ($total == $max_links)
						{
							break 2;
						}
					}
				}
			}
		}
	}

	function addInlineLinks()
	{
		if (is_array($this->keywords))
		{
			foreach ($this->keywords as $keyword => $keyword_data)
			{
				if ($keyword_data['count'])
				{
					$this->keyword_data = $keyword_data;

					$this->current_index = 0;
					$this->current_keyword = $keyword;

					if ($this->keywords[$keyword]['count'] > $this->keywords[$keyword]['max'])
					{
						$inc = round($this->keywords[$keyword]['count'] / ($this->keywords[$keyword]['max'] + 1));

						$count = 0;
						$running = 0;

						while ($running <= $this->keywords[$keyword]['count'] && count($this->kw_indexes[$keyword]) < $this->keywords[$keyword]['max'])
						{
							$running += $inc;
							$this->kw_indexes[$keyword][$running] = $running;
						}
					}
					else
					{
						for ($i = 1; $i <= $this->keywords[$keyword]['max']; $i++)
						{
							$this->kw_indexes[$keyword][$i] = $i;
						}
					}

					$this->content = preg_replace_callback('/(^|[^a-z])(' . preg_quote($keyword) . ')([^a-z]|$)/is', array($this, 'makeLink'), $this->content);
				}
			}
		}
	}

	function makeLink($matches)
	{
		$this->current_index++;

		if ($this->kw_indexes[$this->current_keyword][$this->current_index])
		{
			$attribs = '';

			$ucontext_intext_class = $this->settings['intext_class'];
			if ($ucontext_intext_class)
			{
				$attribs .= ' class="' . $ucontext_intext_class . '"';
			}

			if (intval($this->settings['nofollow']))
			{
				$attribs .= ' rel="nofollow"';
			}

			if (intval($this->settings['new_window']))
			{
				$attribs .= ' target="_blank"';
			}

			$link = $matches[1].'<a href="' . $this->keyword_data['url'] . '"'.$attribs.'>' . $matches[2] . '</a>'.$matches[3];

			$hash = '|'.md5(rand() . $link . serialize($matches)).'|';

			$this->mask_links_list[$hash] = $link;

			return $hash;
		}
		else
		{
			return $matches[1].$matches[2].$matches[3];
		}
	}
}