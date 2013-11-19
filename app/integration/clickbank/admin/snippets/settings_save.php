<?php

$form_vars['ucontext_api_key'] = trim($form_vars['ucontext_api_key']);

if (strlen($form_vars['ucontext_api_key']) != 32)
{
	self::$form_errors['ucontext_api_key'] = 'Your 32 character uContext API Key is required';
}

update_option('ucontext_api_key',					trim(@$form_vars['ucontext_api_key']));
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