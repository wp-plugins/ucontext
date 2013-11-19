<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext4c_Integration
{

	public static function search($keyword)
	{
		$result = array();

		if (!@get_option('ucontext4c_api_disabled', 0))
		{
			$ucontext4c_clickbank_category_list = @get_option('ucontext4c_clickbank_category_list', array());

			if (!is_array($ucontext4c_clickbank_category_list))
			{
				$ucontext4c_clickbank_category_list = array();
			}

			$request = array(
			'api_key'					=> @get_option('ucontext4c_api_key'),
			'http_host'					=> site_url(),
			'keyword'					=> $keyword['custom_search'],
			'clickbank_nickname'		=> @get_option('ucontext4c_clickbank_nickname'),
			'clickbank_min_gravity'		=> @get_option('ucontext4c_clickbank_min_gravity', '0.01'),
			'clickbank_min_commission'	=> @get_option('ucontext4c_clickbank_min_commission', 0),
			'clickbank_min_sale'		=> @get_option('ucontext4c_clickbank_min_sale', 0),
			'clickbank_min_total_sale'	=> @get_option('ucontext4c_clickbank_min_total_sale', 0),
			'clickbank_min_referred'	=> @get_option('ucontext4c_clickbank_min_referred', 0),
			'clickbank_min_rebill'		=> @get_option('ucontext4c_clickbank_min_rebill', 0),
			'clickbank_recurring_only'	=> @get_option('ucontext4c_clickbank_recurring_only', 0),
			'clickbank_category_list'	=> array_flip($ucontext4c_clickbank_category_list)
			);

			if (is_array($keyword['config']['category']) && $keyword['config']['category'])
			{
				$request['clickbank_category_list'] = array_flip($keyword['config']['category']);
			}

			$key = md5(serialize($request));

			$result = Ucontext4c_Base::getCache('clickbank_search', $key);

			if ($result === FALSE)
			{
				$response = wp_remote_post('http://ucontext.com/api.php?method=searchClickbankCatalog&version='.UCONTEXT4C_VERSION, array('method' => 'POST', 'body' => $request));

				$result = json_decode(@$response['body'], true);

				if (@$result['error_code'] >= 400)
				{
					update_option('ucontext4c_notification', 'uContext for '.UCONTEXT4C_INTEGRATION_TITLE.': '.@$result['error_message']);
					update_option('ucontext4c_api_disabled', 1);
				}

				Ucontext4c_Base::setCache('clickbank_search', $key, $result);
			}
			else
			{
				$result = unserialize($result);
			}

			if (is_array($result['search_results']))
			{
				$row = 0;
				$found = FALSE;

				foreach ($result['search_results'] as $id => $item)
				{
					$row++;
					if ($row == 1)
					{
						$first_product_id = $id;
					}

					if (trim($id) == trim($keyword['product_id']))
					{
						$found = TRUE;
					}
				}

				$result['product_id'] = trim($keyword['product_id']);

				if (!$found)
				{
					$result['product_id'] = $first_product_id;
				}
			}
		}

		return $result;
	}
}