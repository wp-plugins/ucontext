<?php

$form_vars['rlm_license_key'] = trim($form_vars['rlm_license_key']);

if (strlen($form_vars['rlm_license_key']) != 32)
{
	self::$form_errors['rlm_license_key'] = 'Your 32 character uContext API Key is required';
}
elseif (get_option('rlm_license_key_'.Ucontext_Base::$name) != trim(@$form_vars['rlm_license_key']))
{
	update_option('rlm_license_key_'.Ucontext_Base::$name, trim(@$form_vars['rlm_license_key']));
	require_once UCONTEXT_INTEGRATION_PATH.'/Ucontext_Integration.php';
	Ucontext_Integration::isValidLicense(TRUE);
}

update_option('ucontext_clickbank_nickname',		trim(@$form_vars['ucontext_clickbank_nickname']));
update_option('ucontext_clickbank_min_gravity',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_gravity'])));
update_option('ucontext_clickbank_min_commission',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_commission'])));
update_option('ucontext_clickbank_min_sale',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_sale'])));
update_option('ucontext_clickbank_min_total_sale',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_total_sale'])));
update_option('ucontext_clickbank_min_referred',	doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_referred'])));
update_option('ucontext_clickbank_min_rebill',		doubleval(preg_replace('/[^0-9\.]+/is', '', @$form_vars['ucontext_clickbank_min_rebill'])));
update_option('ucontext_clickbank_recurring_only',	intval(@$form_vars['ucontext_clickbank_recurring_only']));
update_option('ucontext_clickbank_category_list',	@$form_vars['ucontext_clickbank_category_list']);

update_option('ucontext_api_disabled', 0);
update_option('ucontext_notification', '');

global $wpdb;
$wpdb->query('TRUNCATE '.self::$table['cache']);