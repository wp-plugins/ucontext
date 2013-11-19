<?php

$keyword = NULL;

$response = array('success' => true);

@include UCONTEXT_INTEGRATION_PATH.'/ajax/snippets/keyword_save.php';

$form_vars['keyword_id']	= (int)@$_REQUEST['keyword_id'];
$form_vars['keyword']		= stripslashes(trim(@$_POST['keyword']));
$form_vars['disabled']		= (int)@$_POST['disabled'];
$form_vars['custom_search']	= stripslashes(trim(@$_POST['custom_search']));
$form_vars['product_id']	= trim(@$_POST['product_id']);
$form_vars['config']		= serialize(@$_POST['config']);

if (!$form_vars['keyword_id'])
{
	$keyword_id = (int)$wpdb->get_var('SELECT keyword_id FROM '.self::$table['keyword'].' WHERE keyword = "'.addslashes($form_vars['keyword']).'"');

	if ($keyword_id)
	{
		$response = array('success' => false, 'error' => 'Keyword already exists');
	}
}
else
{
	$keyword = $wpdb->get_row('SELECT * FROM '.self::$table['keyword'].' WHERE keyword_id = '.(int)$_REQUEST['keyword_id'], ARRAY_A);
}

if (!$form_vars['keyword'])
{
	$response = array('success' => false, 'error' => 'Keyword is required');
}

if ($response['success'] == true)
{
	if ($form_vars['keyword'] != $keyword['keyword'])
	{
		$response['refresh_list'] = true;
	}

	if ((int)$form_vars['keyword_id'])
	{
		$form_vars['modified'] = current_time('timestamp');

		$wpdb->update(self::$table['keyword'], $form_vars, array('keyword_id' => $form_vars['keyword_id']));

		$response['keyword_id'] = $form_vars['keyword_id'];
		$response['new_keyword'] = false;
	}
	else
	{
		$form_vars['created'] = current_time('timestamp');

		$wpdb->insert(self::$table['keyword'], $form_vars);

		$response['keyword_id'] = $wpdb->insert_id;
		$response['new_keyword'] = true;
		$response['refresh_list'] = true;
	}
}

exit(json_encode($response));