<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once 'Ucontext_Base.php';

class Ucontext_Ajax extends Ucontext_Base
{
	public static $form_vars	= array();

	public static $form_errors	= array();

	public static $bulk_errors	= array();


	public static function init()
	{
		self::initBase();

		if (!defined('UCONTEXT_SITE_PATH'))
		{
			define('UCONTEXT_SITE_PATH', UCONTEXT_APP_PATH.'/sites/ajax');
		}
	}

	public static function doAjax($action)
	{
		global $wpdb;

		$action = preg_replace('/[^a-z\_]+/is', '', $action);

		$action_path = UCONTEXT_APP_PATH.'/sites/ajax/actions/default.php';

		if (is_file($action_path))
		{
			require $action_path;
		}

		$action_path = UCONTEXT_APP_PATH.'/sites/ajax/actions/'.$action.'.php';
			
		if (is_file($action_path))
		{
			require $action_path;
		}
	}
}