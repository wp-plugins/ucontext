<?php

if (!(int)@get_option('ucontext_settings_import_done', 0))
{
	$request = array(
	'api_key'	=> @get_option('ucontext_api_key'),
	'http_host'	=> site_url()
	);

	$response = wp_remote_post('http://ucontext.com/api.php?method=getArchivedSettings', array('method' => 'POST', 'body' => $request));

	if (isset($response['body']) && trim($response['body']))
	{
		$site = json_decode($response['body'], true);

		if (isset($site['site_id']) && (int)$site['site_id'])
		{
			update_option('ucontext_clickbank_nickname',		trim(@$site['cb_nickname']));
			update_option('ucontext_clickbank_min_gravity',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_gravity'])));
			update_option('ucontext_clickbank_min_commission',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_commission'])));
			update_option('ucontext_clickbank_min_sale',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_sale'])));
			update_option('ucontext_clickbank_min_total_sale',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_total_sale'])));
			update_option('ucontext_clickbank_min_referred',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_referred'])));
			update_option('ucontext_clickbank_min_rebill',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$site['cb_min_rebill'])));
			update_option('ucontext_clickbank_recurring_only',	intval(@$site['cb_recurring']));
		}
	}

	echo '<br />DONE !!!<br />';

	echo 'Please review your default Clickbank categories in your <a href="admin.php?page='.self::$name.'&action=settings">settings</a>';

	update_option('ucontext_settings_import_done', 1);
}