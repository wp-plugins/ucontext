<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext_Keyword
{
	protected static $stop_words;


	public static function getResults($keyword_id, $force = FALSE)
	{
		global $wpdb;

		$search_results = array();

		$keyword = $wpdb->get_row('SELECT * FROM '.Ucontext_Base::$table['keyword'].' WHERE keyword_id = '.(int)$keyword_id, ARRAY_A);

		if ($keyword)
		{
			$keyword['config'] = unserialize($keyword['config']);

			if (!$force && $keyword['last_updated'] > (current_time('timestamp') - (60 * 60 * 48)) && $keyword['search_results'])
			{
				$search_results = unserialize($keyword['search_results']);
			}

			if (!$search_results)
			{
				require_once UCONTEXT_INTEGRATION_PATH.'/Ucontext_Integration.php';

				if (Ucontext_Integration::isValidLicense())
				{
					if (!@$keyword['custom_search'] && @$keyword['keyword'])
					{
						$keyword['custom_search'] = $keyword['keyword'];
					}

					$result = Ucontext_Integration::search($keyword, $force);

					if ($result)
					{
						$search_results = $result['search_results'];

						$wpdb->query('UPDATE '.Ucontext_Base::$table['keyword'].' SET product_id = "'.esc_sql($result['product_id']).'", search_results = "'.esc_sql(serialize($result['search_results'])).'", num_results = '.(int)count($result['search_results']).', last_updated = '.current_time('timestamp').' WHERE keyword_id = '.(int)$keyword['keyword_id']);
					}
					else
					{
						$wpdb->query('UPDATE '.Ucontext_Base::$table['keyword'].' SET last_updated = '.current_time('timestamp').' WHERE keyword_id = '.(int)$keyword['keyword_id']);
					}
				}
			}
		}

		return $search_results;
	}

	public static function findKeywordsInContent($title, $body, $keyword_hints = NULL, $params = NULL)
	{
		$title = html_entity_decode($title, ENT_QUOTES);
		$body = html_entity_decode($body, ENT_QUOTES);

		self::loadStopWords();

		if (!@$params['max_results'])
		{
			$params['max_results'] = 15;
		}

		if (!@$params['max_phrase_words'])
		{
			$params['max_phrase_words'] = 5;
		}

		if (!@$params['min_word_length'])
		{
			$params['min_word_length'] = 3;
		}

		$result_count = 0;

		$keyword_list = array();

		$hint_word_list = array();

		// organize keyword hints
		if ($keyword_hints)
		{
			if (!is_array($keyword_hints))
			{
				$keyword_hints = explode(',', strtolower($keyword_hints));
			}

			foreach ($keyword_hints as $word)
			{
				$word = trim($word);

				if ($word)
				{
					$i = count(explode(' ', $word));
					$hint_word_list[$i][$word] = 1;
				}
			}
		}

		// break out sentences
		$body_word_list = self::extractPhrases($body, $params, TRUE);
			
		// get phrases matching title or keyword hints

		$max_hint_words = count($hint_word_list);

		if ($max_hint_words)
		{
			foreach ($hint_word_list as $a => $word_list)
			{
				foreach ($word_list as $word => $count)
				{
					if (stristr($body, $word) !== FALSE)
					{
						$keyword_list[$word] = preg_match_all('/'.preg_quote($word).'/is', $body, $matches);

						$result_count++;

						if ($result_count >= $params['max_results'])
						{
							break(2);
						}
					}
				}
			}
		}

		if ($result_count < $params['max_results'])
		{
			// find all non-stop words in title
			$title_word_list = self::extractPhrases($title, $params);

			$max_title_words = count($title_word_list);

			if ($max_title_words)
			{
				for ($a = $max_title_words; $a > 1; $a--)
				{
					if (is_array($title_word_list[$a]))
					{
						foreach ($title_word_list[$a] as $word => $count)
						{
							if (isset($body_word_list[$a][$word]))
							{
								$keyword_list[$word] = $body_word_list[$a][$word];

								$result_count++;

								if ($result_count >= $params['max_results'])
								{
									break(2);
								}
							}
						}
					}
				}
			}
		}

		arsort($keyword_list);

		if ($result_count < $params['max_results'])
		{
			$max_body_words = count($body_word_list);

			if ($max_body_words)
			{
				$b_keyword_list = array();

				for ($a = $max_body_words; $a > 1; $a--)
				{
					foreach ($body_word_list[$a] as $word => $count)
					{
						if (!isset($keyword_list[$word]))
						{
							$b_keyword_list[$word] = $body_word_list[$a][$word];

							$result_count++;

							if ($result_count >= $params['max_results'])
							{
								break(2);
							}
						}
					}
				}

				arsort($b_keyword_list);

				foreach ($b_keyword_list as $word => $count)
				{
					$keyword_list[$word] = $count;
				}
			}
		}

		//		echo '<pre>';
		//		print_r($keyword_list);
		//		echo '<pre>';

		// get most popular keywords

		return $keyword_list;
	}

	public static function extractPhrases($text, $params, $unset_singles = FALSE)
	{
		$text = strtolower(self::cleanContent($text));

		$phrase_list = array();

		$raw = explode(' ', $text);

		if (is_array($raw))
		{
			foreach ($raw as $i => $word)
			{
				if (trim($word))
				{
					$temp[] = $word;
				}
			}
		}

		unset($raw);

		if (is_array($temp))
		{
			foreach ($temp as $i => $word)
			{
				$phrase = '';

				for ($a = 0; $a < $params['max_phrase_words']; $a++)
				{
					$index = $i + $a;

					$t_word = @$temp[$index];

					$last_char = substr($t_word, -1);

					if ($last_char == '.' || $last_char == '?')
					{
						break;
					}

					if (!$phrase && (in_array($t_word, self::$stop_words) || strlen($t_word) < $params['min_word_length']))
					{
						break;
					}

					$phrase = trim($phrase.' '.$t_word);

					if (!in_array($t_word, self::$stop_words) && strlen($t_word) > $params['min_word_length'])
					{
						if (!isset($phrase_list[($a + 1)][$phrase]))
						{
							$phrase_list[($a + 1)][$phrase] = 0;
						}

						$phrase_list[($a + 1)][$phrase] = (int)$phrase_list[($a + 1)][$phrase] + 1;
					}
				}
			}
		}

		for ($a = 1; $a <= $params['max_phrase_words']; $a++)
		{
			if ($unset_singles && is_array(@$phrase_list[$a]))
			{
				foreach ($phrase_list[$a] as $word => $count)
				{
					if ($count == 1)
					{
						unset($phrase_list[$a][$word]);
					}
				}
			}

			if (isset($phrase_list[$a]) && is_array($phrase_list[$a]))
			{
				arsort($phrase_list[$a]);
			}
		}

		return $phrase_list;
	}

	protected function cleanHTag($text)
	{
		$text = strip_tags($text);
		$text = preg_replace('/\r\n/is', ' ', $text);
		$text = preg_replace('/\n/is', ' ', $text);
		$text = preg_replace('/\t/is', ' ', $text);
		$text = preg_replace('/[\ ]+/is', ' ', $text);
		$text = preg_replace('/[\ ]+/is', ' ', $text);
		$text = preg_replace('/[\-]+/is', '-', $text);
		$text = preg_replace('/[\%]+/is', '%', $text);
		$text = preg_replace('/[\@]+/is', '@', $text);
		$text = preg_replace('/[\.]+/is', '.', $text);
		$text = trim($text);
		$text = trim($text, '"');

		return $text;
	}

	protected function cleanContent($text)
	{
		$text = preg_replace('/\<script.*?\<\/script\>/is', ' ', $text);
		$text = strip_tags($text);
		$text = preg_replace('/<.*?>/is', '', $text);
		//		$text = strtolower($text);
		$text = preg_replace('/[^0-9a-zA-Z\'\-\%\@\.\?\’]+/is', ' ', $text);
		//		$text = preg_replace('/\.\ /is', ' ', $text);
		$text = preg_replace('/\r\n/is', ' ', $text);
		$text = preg_replace('/\n/is', ' ', $text);
		$text = preg_replace('/\t/is', ' ', $text);
		$text = preg_replace('/[\ ]+/is', ' ', $text);
		$text = preg_replace('/[\-]+/is', '-', $text);
		$text = preg_replace('/[\%]+/is', '%', $text);
		$text = preg_replace('/[\@]+/is', '@', $text);
		$text = preg_replace('/[\$]+/is', '$', $text);
		$text = preg_replace('/[\.]+/is', '.', $text);
		$text = trim($text);

		return $text;
	}

	protected function loadStopWords()
	{
		self::$stop_words = array(
			'a\'s',
			'able',
			'about',
			'above',
			'according',
			'accordingly',
			'across',
			'actually',
			'after',
			'afterwards',
			'again',
			'against',
			'ain\'t',
			'all',
			'allow',
			'allows',
			'almost',
			'alone',
			'along',
			'already',
			'also',
			'although',
			'always',
			'am',
			'among',
			'amongst',
			'an',
			'and',
			'another',
			'any',
			'anybody',
			'anyhow',
			'anyone',
			'anything',
			'anyway',
			'anyways',
			'anywhere',
			'apart',
			'appear',
			'appreciate',
			'appropriate',
			'are',
			'aren\'t',
			'around',
			'as',
			'aside',
			'ask',
			'asking',
			'associated',
			'at',
			'available',
			'away',
			'awfully',
			'be',
			'became',
			'because',
			'become',
			'becomes',
			'becoming',
			'been',
			'before',
			'beforehand',
			'behind',
			'being',
			'believe',
			'below',
			'beside',
			'besides',
			'best',
			'better',
			'between',
			'beyond',
			'both',
			'brief',
			'but',
			'by',
			'c\'mon',
			'c\'s',
			'came',
			'can',
			'can\'t',
			'cannot',
			'cant',
			'cause',
			'causes',
			'certain',
			'certainly',
			'changes',
			'clearly',
			'co',
			'com',
			'come',
			'comes',
			'concerning',
			'consequently',
			'consider',
			'considering',
			'contain',
			'containing',
			'contains',
			'corresponding',
			'could',
			'couldn\'t',
			'course',
			'currently',
			'definitely',
			'described',
			'despite',
			'did',
			'didn\'t',
			'different',
			'do',
			'does',
			'doesn\'t',
			'doing',
			'don\'t',
			'done',
			'down',
			'downwards',
			'during',
			'each',
			'edu',
			'eg',
			'eight',
			'either',
			'else',
			'elsewhere',
			'enough',
			'entirely',
			'especially',
			'et',
			'etc',
			'even',
			'ever',
			'every',
			'everybody',
			'everyone',
			'everything',
			'everywhere',
			'ex',
			'exactly',
			'example',
			'except',
			'far',
			'few',
			'fifth',
			'first',
			'five',
			'followed',
			'following',
			'follows',
			'for',
			'former',
			'formerly',
			'forth',
			'four',
			'from',
			'further',
			'furthermore',
			'get',
			'gets',
			'getting',
			'given',
			'gives',
			'go',
			'goes',
			'going',
			'gone',
			'got',
			'gotten',
			'greetings',
			'had',
			'hadn\'t',
			'happens',
			'hardly',
			'has',
			'hasn\'t',
			'have',
			'haven\'t',
			'having',
			'he',
			'he\'s',
			'hello',
			'help',
			'hence',
			'her',
			'here',
			'here\'s',
			'hereafter',
			'hereby',
			'herein',
			'hereupon',
			'hers',
			'herself',
			'hi',
			'him',
			'himself',
			'his',
			'hither',
			'hopefully',
			'how',
			'howbeit',
			'however',
			'i\'d',
			'i\'ll',
			'i\'m',
			'i\'ve',
			'ie',
			'if',
			'ignored',
			'immediate',
			'in',
			'inasmuch',
			'inc',
			'indeed',
			'indicate',
			'indicated',
			'indicates',
			'inner',
			'insofar',
			'instead',
			'into',
			'inward',
			'is',
			'isn\'t',
			'it',
			'it\'d',
			'it\'ll',
			'it\'s',
			'its',
			'itself',
			'just',
			'keep',
			'keeps',
			'kept',
			'know',
			'knows',
			'known',
			'last',
			'lately',
			'later',
			'latter',
			'latterly',
			'least',
			'less',
			'lest',
			'let',
			'let\'s',
			'like',
			'liked',
			'likely',
			'little',
			'look',
			'looking',
			'looks',
			'ltd',
			'mainly',
			'many',
			'may',
			'maybe',
			'me',
			'mean',
			'meanwhile',
			'merely',
			'might',
			'more',
			'moreover',
			'most',
			'mostly',
			'much',
			'must',
			'my',
			'myself',
			'name',
			'namely',
			'nd',
			'near',
			'nearly',
			'necessary',
			'need',
			'needs',
			'neither',
			'never',
			'nevertheless',
			'new',
			'next',
			'nine',
			'no',
			'nobody',
			'non',
			'none',
			'noone',
			'nor',
			'normally',
			'not',
			'nothing',
			'novel',
			'now',
			'nowhere',
			'obviously',
			'of',
			'off',
			'often',
			'oh',
			'ok',
			'okay',
			'old',
			'on',
			'once',
			'one',
			'ones',
			'only',
			'onto',
			'or',
			'other',
			'others',
			'otherwise',
			'ought',
			'our',
			'ours',
			'ourselves',
			'out',
			'outside',
			'over',
			'overall',
			'own',
			'particular',
			'particularly',
			'per',
			'perhaps',
			'placed',
			'please',
			'plus',
			'possible',
			'presumably',
			'probably',
			'provides',
			'que',
			'quite',
			'qv',
			'rather',
			'rd',
			're',
			'really',
			'reasonably',
			'regarding',
			'regardless',
			'regards',
			'relatively',
			'respectively',
			'right',
			'said',
			'same',
			'saw',
			'say',
			'saying',
			'says',
			'second',
			'secondly',
			'see',
			'seeing',
			'seem',
			'seemed',
			'seeming',
			'seems',
			'seen',
			'self',
			'selves',
			'sensible',
			'sent',
			'serious',
			'seriously',
			'seven',
			'several',
			'shall',
			'she',
			'should',
			'shouldn\'t',
			'since',
			'six',
			'so',
			'some',
			'somebody',
			'somehow',
			'someone',
			'something',
			'sometime',
			'sometimes',
			'somewhat',
			'somewhere',
			'soon',
			'sorry',
			'specified',
			'specify',
			'specifying',
			'still',
			'sub',
			'such',
			'sup',
			'sure',
			't\'s',
			'take',
			'taken',
			'tell',
			'tends',
			'th',
			'than',
			'thank',
			'thanks',
			'thanx',
			'that',
			'that\'s',
			'thats',
			'the',
			'their',
			'theirs',
			'them',
			'themselves',
			'then',
			'thence',
			'there',
			'there\'s',
			'thereafter',
			'thereby',
			'therefore',
			'therein',
			'theres',
			'thereupon',
			'these',
			'they',
			'they\'d',
			'they\'ll',
			'they\'re',
			'they\'ve',
			'think',
			'third',
			'this',
			'thorough',
			'thoroughly',
			'those',
			'though',
			'three',
			'through',
			'throughout',
			'thru',
			'thus',
			'to',
			'together',
			'too',
			'took',
			'toward',
			'towards',
			'tried',
			'tries',
			'truly',
			'try',
			'trying',
			'twice',
			'two',
			'un',
			'under',
			'unfortunately',
			'unless',
			'unlikely',
			'until',
			'unto',
			'up',
			'upon',
			'us',
			'use',
			'used',
			'useful',
			'uses',
			'using',
			'usually',
			'value',
			'various',
			'very',
			'via',
			'viz',
			'vs',
			'want',
			'wants',
			'was',
			'wasn\'t',
			'way',
			'we',
			'we\'d',
			'we\'ll',
			'we\'re',
			'we\'ve',
			'welcome',
			'well',
			'went',
			'were',
			'weren\'t',
			'what',
			'what\'s',
			'whatever',
			'when',
			'whence',
			'whenever',
			'where',
			'where\'s',
			'whereafter',
			'whereas',
			'whereby',
			'wherein',
			'whereupon',
			'wherever',
			'whether',
			'which',
			'while',
			'whither',
			'who',
			'who\'s',
			'whoever',
			'whole',
			'whom',
			'whose',
			'why',
			'will',
			'willing',
			'wish',
			'with',
			'within',
			'without',
			'won\'t',
			'wonder',
			'would',
			'would',
			'wouldn\'t',
			'yes',
			'yet',
			'you',
			'you\'d',
			'you\'ll',
			'you\'re',
			'you\'ve',
			'your',
			'yours',
			'yourself',
			'yourselves',
			'zero'
			);
	}
}