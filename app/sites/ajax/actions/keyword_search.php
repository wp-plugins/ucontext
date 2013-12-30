<?php

require_once UCONTEXT_INTEGRATION_PATH.'/Ucontext_Integration.php';

if (Ucontext_Integration::isValidLicense())
{
	if (isset($_REQUEST['keyword_id']) && $_REQUEST['keyword_id'])
	{
		$keyword_id_list = explode(',', $_REQUEST['keyword_id']);

		$_REQUEST['keyword_id'] = array_shift($keyword_id_list);

		require UCONTEXT_APP_PATH.'/Ucontext_Keyword.php';

		$search_results = Ucontext_Keyword::getResults($_REQUEST['keyword_id'], TRUE);

		if ($search_results)
		{
			$keyword = $wpdb->get_row('SELECT * FROM '.self::$table['keyword'].' WHERE keyword_id = '.(int)$_REQUEST['keyword_id'], ARRAY_A);

			$found = false;
			foreach ($search_results as $product_id => $item)
			{
				if ($keyword['product_id'] == $product_id)
				{
					$found = true;
				}
			}

			$row = 0;

			foreach ($search_results as $product_id => $item)
			{
				$row++;
				if ($row == 1 && !$found)
				{
					$keyword['product_id'] = $product_id;
				}

				$checked = '';
				if ($keyword['product_id'] == $product_id)
				{
					$checked = ' checked';
				}

				echo '
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td width="1%"><input type="radio" name="product_id" value="'.$product_id.'"'.$checked.' /></td>
	<td><a href="'.$item['url'].'" target="_blank">'.$item['title'].'</a></td>
</tr>
</table>
';
			}
		}
		else
		{
			echo '
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td colspan="2"><br /><center><strong>No matching products found</strong></center><br /></td>
</tr>
</table>
';
		}
	}
}
else
{
	echo '
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td colspan="2"><br /><center><strong>Empty or Invalid uContext License<br />Please check your Settings</strong></center><br /></td>
</tr>
</table>
';
}

exit();