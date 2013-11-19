<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

require_once 'Ucontext4c_Base.php';

class Ucontext4c_Admin extends Ucontext4c_Base
{
	public static $form_vars	= array();

	public static $form_errors	= array();

	public static $bulk_errors	= array();


	public static function init()
	{
		self::initBase();

		define('UCONTEXT4C_SITE_PATH',	UCONTEXT4C_APP_PATH.'/sites/admin');

		if (@$_GET['page'] == self::$name)
		{
			add_action('admin_init',	array('Ucontext4c_Admin', 'doBeforeHeaders'), 1);
			add_action('admin_head',	array('Ucontext4c_Admin', 'addToHead'));
		}

		add_action('save_post', array('Ucontext4c_Admin', 'savePost'));
	}

	public static function doBeforeHeaders()
	{
		global $wpdb;

		self::getLatestPluginVersion();

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

		$action_path = UCONTEXT4C_SITE_PATH.'/actions/default.php';

		if (is_file($action_path))
		{
			require $action_path;
		}

		$action_path = UCONTEXT4C_SITE_PATH.'/actions/'.self::$action.'.php';

		if (is_file($action_path))
		{
			require $action_path;
		}
	}

	public static function addToHead()
	{
		echo '<link rel="stylesheet" href="'.UCONTEXT4C_PLUGIN_URL.'/includes/style_admin.css" type="text/css" media="all" />';
	}

	public static function displayView()
	{
		$layout_path = UCONTEXT4C_SITE_PATH.'/layouts/default.php';

		if (is_file($layout_path))
		{
			global $wpdb;

			$view_path = UCONTEXT4C_SITE_PATH.'/views/'.self::$action.'.php';

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

	public static function savePost($post_id)
	{
		self::processPost($post_id, TRUE);
	}

	public static function savePostMeta($post_id)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		{
			return $post_id;
		}

		if ((int)$post_id)
		{
			update_post_meta($post_id, 'ucontext4c_disable', (int)$_POST['ucontext4c_disable']);
			update_post_meta($post_id, 'ucontext4c_manual_keywords', trim($_POST['ucontext4c_manual_keywords']));

			self::saveKeywordsToMainList($_POST['ucontext4c_manual_keywords'], 'manual');

			// All in one SEO
			if (isset($_POST['aiosp_keywords']) && trim($_POST['aiosp_keywords']))
			{
				update_post_meta($post_id, 'ucontext4c_seo_keywords', trim($_POST['aiosp_keywords'], ','));
				self::saveKeywordsToMainList($_POST['aiosp_keywords'], 'seo');
			}

			// Platinum SEO
			if (isset($_POST['psp_keywords']) && trim($_POST['psp_keywords']))
			{
				update_post_meta($post_id, 'ucontext4c_seo_keywords', trim($_POST['psp_keywords'], ','));
				self::saveKeywordsToMainList($_POST['psp_keywords'], 'seo');
			}
		}
	}

	public static function displayPostMeta()
	{
		global $post, $wpdb;

		$post_id = $post;
		if (is_object($post_id)) $post_id = $post_id->ID;

		$ucontext4c_disable			= (int)get_post_meta($post_id, 'ucontext4c_disable', true);

		$ucontext4c_auto_keywords	= unserialize(get_post_meta($post_id, 'ucontext4c_auto_keywords', true));
		$ucontext4c_auto_keywords	= array_keys($ucontext4c_auto_keywords);
		$ucontext4c_auto_keywords	= implode(', ', $ucontext4c_auto_keywords);

		$ucontext4c_manual_keywords	= get_post_meta($post_id, 'ucontext4c_manual_keywords', true);

		$checked = '';
		if ($ucontext4c_disable)
		{
			$checked = ' checked';
		}

		$last_processed = get_post_meta($post_id, 'ucontext4c_last_process', 1);

		if ((int)$last_processed)
		{
			$last_processed = date('Y-m-d h:i A', $last_processed);
		}
		else
		{
			$last_processed = '';
		}

		$snippet =<<<END
		<table style="margin-bottom: 20px; width: 99%;" width="99%">
		<tr>
			<th scope="row" style="text-align:right; vertical-align:top;" nowrap="nowrap">Disable on this page/post:</th>
			<td width="98%"><input type="checkbox" name="ucontext4c_disable" value="1"{$checked} /></td>
		</tr>
		<tr>
			<th scope="row" style="text-align:right; vertical-align:top;" nowrap="nowrap">Automatic Keywords:</th>
			<td width="98%" style="border: 1px solid #BBB; border-radius: 3px; padding: 3px 5px;">
			{$ucontext4c_auto_keywords}<br />
			</td>
		</tr>
		<tr>
			<th scope="row" style="text-align:right; vertical-align:top;" nowrap="nowrap">Manual Keywords:</th>
			<td width="98%">
				<textarea name="ucontext4c_manual_keywords" rows="2" cols="65" style="width: 100%;">{$ucontext4c_manual_keywords}</textarea><br />
				<i>Comma separated</i>
			</td>
		</tr>
		<tr>
			<th scope="row" style="text-align:right; vertical-align:top;" nowrap="nowrap">Last Processed:</th>
			<td width="98%">{$last_processed}
			</td>
		</tr>
		</table>
		<a href="admin.php?page=ucontext&action=keywords" target="_blank">Manage All Keywords...</a>
END;

			echo $snippet;
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

	public static function array_stripslashes($array)
	{
		if (is_array($array))
		{
			foreach ($array as $field => $value)
			{
				if (is_array($value))
				{
					$array[$field] = self::array_stripslashes($value);
				}
				else
				{
					$array[$field] = stripslashes($value);
				}
			}
		}

		return $array;
	}
}