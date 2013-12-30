<?php

$form_vars = self::$form_vars;

@include UCONTEXT_INTEGRATION_PATH.'/admin/snippets/settings_save.php';

$form_vars['ucontext_redirect_slug'] = preg_replace('/[^0-9a-zA-Z\-\_]+/is', '', trim(@$form_vars['ucontext_redirect_slug']));

update_option('ucontext_max_links',			(int)@$form_vars['ucontext_max_links']);
update_option('ucontext_redirect_slug',		$form_vars['ucontext_redirect_slug']);
update_option('ucontext_no_autokeywords',	(int)@$form_vars['ucontext_no_autokeywords']);
update_option('ucontext_site_keywords',		trim(@$form_vars['ucontext_site_keywords'], ','));
update_option('ucontext_links_display',		(int)@$form_vars['ucontext_links_display']);
update_option('ucontext_hide_rss_links',	(int)@$form_vars['ucontext_hide_rss_links']);

Ucontext_Admin::saveKeywordsToMainList($form_vars['ucontext_site_keywords'], 'manual');

$wpdb->query('UPDATE '.Ucontext_Base::$table['keyword'].' SET last_updated = 0');
$wpdb->query('DELETE FROM '.$wpdb->base_prefix.'postmeta WHERE meta_key = "ucontext_auto_keywords"');

if (!self::$form_errors)
{
	update_option('ucontext_notification', '');
	update_option('ucontext_api_disabled', 0);

	header('location: admin.php?page='.self::$name.'&action=settings&saved=1');
	exit();
}

self::$action = 'settings';