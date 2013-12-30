<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once 'Ucontext_Base.php';

class Ucontext_Admin extends Ucontext_Base
{
	public static $form_vars	= array();

	public static $form_errors	= array();

	public static $bulk_errors	= array();


	public static function init()
	{
		self::initBase();

		define('UCONTEXT_SITE_PATH',	UCONTEXT_APP_PATH.'/sites/admin');

		if (@$_GET['page'] == self::$name)
		{
			add_action('admin_init',	array('Ucontext_Admin', 'doBeforeHeaders'), 1);
			add_action('admin_head',	array('Ucontext_Admin', 'addToHead'));
		}
	}

	public static function doBeforeHeaders()
	{
		global $wpdb;

		if (isset($_POST['form_vars']))
		{
			$_POST['form_vars'] = self::array_stripslashes($_POST['form_vars']);

			self::$form_vars = $_POST['form_vars'];
		}

		if (isset($_GET['action']))
		{
			self::$action = preg_replace('/[^0-9a-zA-Z\_\-]+/is', '', strtolower($_GET['action']));
		}

		if (!self::$action)
		{
			self::$action = 'keywords';
		}

		$action_path = UCONTEXT_SITE_PATH.'/actions/default.php';

		if (is_file($action_path))
		{
			require $action_path;
		}

		$action_path = UCONTEXT_SITE_PATH.'/actions/'.self::$action.'.php';

		if (is_file($action_path))
		{
			require $action_path;
		}
	}

	public static function addToHead()
	{
		echo '<link rel="stylesheet" href="'.UCONTEXT_PLUGIN_URL.'/includes/style_admin.css" type="text/css" media="all" />';
	}

	public static function displayView()
	{
		$layout_path = UCONTEXT_SITE_PATH.'/layouts/default.php';

		if (is_file($layout_path))
		{
			global $wpdb;

			$view_path = UCONTEXT_SITE_PATH.'/views/'.self::$action.'.php';

			if (!is_file($view_path))
			{
				exit('Invalid View: '.$view_path);
			}

			require($layout_path);
		}
		else
		{
			exit('Invalid Layout: '.$layout_path);
		}
	}

	public static function upgradePlugin()
	{
		global $wpdb;

		if (file_exists(ABSPATH.'/wp-admin/includes/upgrade.php'))
		{
			require_once(ABSPATH.'/wp-admin/includes/upgrade.php');
		}
		else
		{
			require_once(ABSPATH.'/wp-admin/upgrade-functions.php');
		}

		dbDelta('CREATE TABLE `'.self::$table['cache'].'` (
			`namespace` VARCHAR(50) NOT NULL,
			`key` VARCHAR(50) NOT NULL,
			`data` LONGTEXT,
			`expire_datetime` DATETIME NOT NULL,
			UNIQUE KEY (`namespace`, `key`)
		) CHARSET=utf8;');

		dbDelta('CREATE TABLE `'.self::$table['click_log'].'` (
			`post_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
			`keyword` TINYTEXT,
			`agent` TINYTEXT NOT NULL,
			`spider` TINYINT(1) UNSIGNED NOT NULL,
			`date_time` datetime,
			`year` INT(4) UNSIGNED,
			`month` INT(2) UNSIGNED,
			`day` INT(2) UNSIGNED,
			`weekday` INT(1) UNSIGNED,
			`hour` INT(7) UNSIGNED
		) CHARSET=utf8;');

		dbDelta('CREATE TABLE `'.self::$table['keyword'].'` (
			`keyword_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`keyword` VARCHAR(100) NOT NULL,
			`custom_search` VARCHAR(100) NOT NULL,
			`config` MEDIUMTEXT,
			`disabled` TINYINT(1) UNSIGNED NOT NULL,
			`product_id` VARCHAR(50) NOT NULL,
			`search_results` MEDIUMTEXT,
			`num_results` INT(11) UNSIGNED NOT NULL,
			`last_updated` INT(11) UNSIGNED NOT NULL,
			`created` INT(11) UNSIGNED NOT NULL,
			`modified` INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY (`keyword_id`)
		) CHARSET=utf8;');

		dbDelta('CREATE TABLE `'.self::$table['spider_agent'].'` (
			`spider_agent_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`sig` VARCHAR(100),
			PRIMARY KEY (`spider_agent_id`),
			KEY `sig` (`sig`)
		) CHARSET=utf8;');
	}
}