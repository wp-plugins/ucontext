<?php

if (isset($_REQUEST['keyword_id']) && (int)$_REQUEST['keyword_id'])
{
	$_REQUEST['keyword_list'][(int)$_REQUEST['keyword_id']] = 1;
}

$response = array('success' => true);

if (isset($_REQUEST['keyword_list']) && is_array($_REQUEST['keyword_list']))
{
	$keyword_id_list = implode(',', array_keys($_REQUEST['keyword_list']));

	if ($keyword_id_list)
	{
		$wpdb->query('DELETE FROM '.self::$table['keyword'].' WHERE keyword_id IN ('.$keyword_id_list.')');
	}
}
else
{
	$response = array('success' => false, 'error' => 'Keyword is required');
}

exit(json_encode($response));