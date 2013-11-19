<?php

$keyword = NULL;

$response = array('success' => true);

@include UCONTEXT_INTEGRATION_PATH.'/ajax/snippets/keyword_bulk_save.php';

$form_vars['disabled']		= (int)@$_POST['disabled'];
$form_vars['custom_search']	= stripslashes(trim(@$_POST['custom_search']));
$form_vars['product_id']	= trim(@$_POST['product_id']);
$form_vars['config']		= serialize(@$_POST['config']);

if (!$form_vars['custom_search'])
{
	$response = array('success' => false, 'error' => 'Custom Search is required');
}

if ($response['success'] == true && trim($_POST['bulk_list']))
{
	$sql = '
	UPDATE
		`'.self::$table['keyword'].'`
	SET
		`disabled`		= '.(int)$form_vars['disabled'].',
		`custom_search`	= "'.esc_sql($form_vars['custom_search']).'",
		`config`		= "'.esc_sql($form_vars['config']).'",
		`product_id`	= "'.esc_sql($form_vars['product_id']).'"
	WHERE
		`keyword_id` IN ('.$_POST['bulk_list'].')';

	$wpdb->query($sql);
}

exit(json_encode($response));