<?php
/*
 Plugin Name: uContext for Clickbank
 Plugin URI: http://www.uContext.com/
 Description: In-text Clickbank affiliate links
 Version: 3.9.1
 Author: Summit Media Concepts LLC
 Author URI: http://www.SummitMediaConcepts.com/
 */

define('UCONTEXT_VERSION',		'3.9.1');

define('UCONTEXT_PATH',			dirname(__FILE__));
define('UCONTEXT_APP_PATH',		UCONTEXT_PATH.'/app');
define('UCONTEXT_LIST_PATH',	UCONTEXT_APP_PATH.'/lists');
define('UCONTEXT_PLUGIN_URL',	plugins_url(NULL, __FILE__));

define('UCONTEXT_INTEGRATION_TITLE',	'Clickbank');
define('UCONTEXT_INTEGRATION_HANDLE',	'clickbank');
define('UCONTEXT_INTEGRATION_PATH',	UCONTEXT_APP_PATH.'/integration/'.UCONTEXT_INTEGRATION_HANDLE);


if (is_admin())
{
	// Do admin stuff

	require_once UCONTEXT_APP_PATH.'/Ucontext_Admin.php';
	Ucontext_Admin::init();

	function Ucontext_activatePlugin()
	{
		if (UCONTEXT_INTEGRATION_PATH.'/activate.php')
		{
			include UCONTEXT_INTEGRATION_PATH.'/activate.php';
		}

		Ucontext_Admin::upgradePlugin();

		require_once UCONTEXT_APP_PATH.'/Ucontext_Cron.php';
		Ucontext_Cron::init();
		Ucontext_Cron::updateAgents();
	}

	function Ucontext_displayView()
	{
		Ucontext_Admin::displayView();
	}

	function Ucontext_addAdminMenu()
	{
		add_menu_page('uContext for '.UCONTEXT_INTEGRATION_TITLE, 'uC for '.UCONTEXT_INTEGRATION_TITLE, 'activate_plugins', 'ucontext', 'Ucontext_displayView', UCONTEXT_PLUGIN_URL.'/includes/icons/ucontext-icon.png');
	}

	function Ucontext_enqueueScripts()
	{
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
	}

	function Ucontext_admin_notice()
	{
		$notice = get_option('ucontext_notification');

		if ($notice)
		{
			echo '<div class="updated"><p>'.$notice.'</p></div>';
		}
	}

	add_action('admin_notices', 'Ucontext_admin_notice');

	add_action('admin_menu', 'Ucontext_addAdminMenu');

	add_action('wp_enqueue_scripts', 'Ucontext_enqueueScripts');

	register_activation_hook(__FILE__, 'Ucontext_activatePlugin');

	@include(dirname(__FILE__).'/postmeta.php');

	// Carry meta from old plugin version to current
	if (!get_option('rlm_license_key_'.Ucontext_Base::$name) && get_option('ucontext_api_key'))
	{
		update_option('rlm_license_key_'.Ucontext_Base::$name, get_option('ucontext_api_key'));
	}
}
else
{
	// Do public stuff

	require_once UCONTEXT_APP_PATH.'/Ucontext_Public.php';
	Ucontext_Public::init();

	function Ucontext_filterContent($content)
	{
		global $post;

		Ucontext_Public::processPost($post->ID);

		$content = Ucontext_Public::filterContent($content);

		return $content;
	}

	function Ucontext_publicHead()
	{
		if (get_option('ucontext_active', 1))
		{
			if (get_option('ucontext_use_style', 0))
			{
				$link_css = get_option('ucontext_link_css');

				if ($link_css)
				{
					echo "\n".'<style type="text/css" media="screen">'.$link_css.'</style>'."\n";
				}
			}
		}
	}

	function Ucontext_checkRedirect()
	{
		$ucontext_redirect_slug = trim(@get_option('ucontext_redirect_slug', 'recommends'));

		if (!$ucontext_redirect_slug)
		{
			$ucontext_redirect_slug = 'recommends';
		}

		if (isset($_REQUEST[$ucontext_redirect_slug]) && $_REQUEST[$ucontext_redirect_slug])
		{
			$post_id = @$_REQUEST['post_id'];
			$keyword = @$_REQUEST[$ucontext_redirect_slug];
		}
		else
		{
			$request_url = str_ireplace(parse_url(site_url(), PHP_URL_PATH), '', $_SERVER['REQUEST_URI']);

			$parts = explode('/', trim($request_url, '/'));

			$slug		= @$parts[0];
			$post_id	= @$parts[1];
			$keyword	= urldecode(@$parts[2]);
		}

		if ($slug == $ucontext_redirect_slug && (int)$post_id && $keyword)
		{
			global $wpdb;

			$keyword = $wpdb->get_row('SELECT * FROM '.Ucontext_Base::$table['keyword'].' WHERE keyword = "'.esc_sql($keyword).'"', ARRAY_A);

			if ($keyword)
			{
				$search_results = unserialize($keyword['search_results']);

				$url = $search_results[$keyword['product_id']]['url'];

				header('location: '.$url);

				$spider = (int)$wpdb->get_var('SELECT spider_agent_id FROM '.Ucontext_Base::$table['spider_agent'].' WHERE "'.esc_sql($_SERVER['HTTP_USER_AGENT']).'" LIKE CONCAT("%", sig, "%") LIMIT 1');

				if ($spider)
				{
					$spider = 1;
				}

				//@file_put_contents('/tmp/uc_spider_sql.log', $spider.' = SELECT spider_agent_id FROM '.Ucontext_Base::$table['spider_agent'].' WHERE "'.esc_sql($_SERVER['HTTP_USER_AGENT']).'" LIKE CONCAT("%", sig, "%")'."\n", FILE_APPEND);

				$timezone = ini_get('date.timezone');
				if ($timezone)
				{
					date_default_timezone_set($timezone);
				}

				$click_log = array(
				'post_id'	=> $post_id,
				'keyword'	=> $keyword['keyword'],
				'agent'		=> $_SERVER['HTTP_USER_AGENT'],
				'spider'	=> $spider,
				'date_time'	=> date('Y-m-d H:i:s'),
				'year'		=> date('Y'),
				'month'		=> date('m'),
				'day'		=> date('d'),
				'weekday'	=> date('N'),
				'hour'		=> date('H')
				);

				$wpdb->insert(Ucontext_Public::$table['click_log'], $click_log);

				exit();
			}
		}
	}

	add_action('plugins_loaded', 'Ucontext_checkRedirect', 0);

	add_filter('the_content', 'Ucontext_filterContent', 9999);
	add_filter('the_content_feed', 'Ucontext_filterContent', 9999);

	add_action('wp_head', 'Ucontext_publicHead');
}

add_action('wp_ajax_ucontext_action', 'Ucontext_Ajax_Action');

function Ucontext_Ajax_Action()
{
	require_once UCONTEXT_APP_PATH.'/Ucontext_Ajax.php';
	Ucontext_Ajax::init();
	Ucontext_Ajax::doAjax($_REQUEST['do']);
	exit();
}

// Cron ===================================================

function Ucontext_scheduleCron()
{
	if (!wp_next_scheduled('Ucontext_5MinuteCronEvent'))
	{
		wp_schedule_event(current_time('timestamp'), '5minutes', 'Ucontext_5MinuteCronEvent');
	}

	if (!wp_next_scheduled('Ucontext_30DayCronEvent'))
	{
		wp_schedule_event(current_time('timestamp'), '30days', 'Ucontext_30DayCronEvent');
	}
}

function Ucontext_do5MinuteCron()
{
	require_once UCONTEXT_APP_PATH.'/Ucontext_Cron.php';
	Ucontext_Cron::init();
	Ucontext_Cron::updateKeywordSearchResults();
}

function Ucontext_do30DayCron()
{
	require_once UCONTEXT_APP_PATH.'/Ucontext_Cron.php';
	Ucontext_Cron::init();
	Ucontext_Cron::updateAgents();
}

function Ucontext_addSchedules( $schedules )
{
	$schedules['5minutes']	= array('interval' => 300, 'display' => __('Every 5 Minutes'));
	$schedules['30days']	= array('interval' => 2592000, 'display' => __('Every 30 Days'));
	return $schedules;
}

add_filter('cron_schedules', 'Ucontext_addSchedules');

add_action('Ucontext_5MinuteCronEvent', 'Ucontext_do5MinuteCron');
add_action('Ucontext_30DayCronEvent', 'Ucontext_do30DayCron');

add_action('wp', 'Ucontext_scheduleCron');

@include(dirname(__FILE__).'/widget.php');