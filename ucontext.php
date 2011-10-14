<?php
/*
 Plugin Name: uContext - Clickbank In-Text Affiliate Links
 Plugin URI: http://www.uContext.com
 Description: Automatically finds keyword phrases and converts them into contextual in-text Clickbank affiliate links.
 Author: Summit Media Concepts LLC
 Author URI: http://www.SummitMediaConcepts.com
 Tags: clickbank, affiliate, links, ads, advertising, post, context, contextual
 Version: 2.0
 */

/**

Copyright 2010  Summit Media Concepts LLC (email : info@SummitMediaConcepts.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

define('UCONTEXT_VERSION', '1.9');

require dirname(__FILE__).'/ucontext_library.php';

add_action('admin_init', 'uContext_activate');

add_action('widgets_init', create_function('', 'return register_widget("uContext_Widget");'));

add_action('admin_menu', 'uContext_addAdminPages');

add_action('admin_notices', 'uContext_init');

if (!is_admin())
{
	add_filter('the_content', 'uContext_filterInText');
}

function uContext_addAdminPages()
{
	add_options_page('uContext Options', 'uContext', 'administrator', 'ucontext', 'uContext_Settings');

	add_action('admin_init', 'uContext_registerSettings');
}

function uContext_registerSettings()
{
	register_setting('ucontext-settings-group', 'ucontext_api_key');
	register_setting('ucontext-settings-group', 'ucontext_code');
	register_setting('ucontext-settings-group', 'ucontext_intext_class');
	register_setting('ucontext-settings-group', 'ucontext_nofollow');
	register_setting('ucontext-settings-group', 'ucontext_new_window');
}

function uContext_Settings()
{
	$multisite = intval(constant('MULTISITE'));

	$aid = '';

	if ($multisite)
	{
		global $current_site, $current_blog;

		$affiliate_token = get_blog_option($current_site->blog_id, 'ucontext_code');

		if ($affiliate_token)
		{
			$aid = '?aid='.$affiliate_token;
		}
	}

	if (intval($_REQUEST['clear_cache']))
	{
		global $wpdb;
		$wpdb->query('DELETE FROM '.$wpdb->base_prefix.'ucontext_cache');
	}

	?>
<div class="wrap">
<h2>uContext Settings</h2>

<form method="post" action="options.php"><?php settings_fields('ucontext-settings-group'); ?>

<table class="form-table">

	<tr valign="top">
		<th scope="row">API Key</th>
		<td><input type="text" name="ucontext_api_key"
			value="<?php echo get_option('ucontext_api_key'); ?>" size="50"
			maxlength="32" /><br />
		Sign-up for your API Key at <a
			href="http://www.uContext.com/<?php echo $aid; ?>" target="_blank">http://www.uContext.com</a>.
		This is required for the plug-in to work.<br />
		<br />
		Please watch the <a href="http://www.ucontext.com/start_here/"
			target="_blank" style="color: #900;">getting started video here...</a><br />
		For additional help, please contact us using our <a
			href="http://www.ucontext.com/support/" target="_blank">support
		system</a>.</td>
	</tr>

	<?php
	if ($multisite && $current_site->blog_id == $current_blog->blog_id)
	{
		?>
	<tr valign="top">
		<th scope="row">Affiliate Code</th>
		<td><input type="text" name="ucontext_code"
			value="<?php echo get_option('ucontext_code'); ?>" size="35"
			maxlength="32" /></td>
	</tr>
	<?php
	}
	?>

	<tr valign="top">
		<td scope="row" colspan="2"
			style="color: #FFF; background-color: #999; font-size: 12px; font-weight: bold; padding: 2px 10px;">Optional
		Settings</td>
	</tr>

	<tr valign="top">
		<th scope="row">Anchor CSS Class</th>
		<td><input type="text" name="ucontext_intext_class"
			value="<?php echo get_option('ucontext_intext_class'); ?>" /><br />
		<div style="width: 400px;">This is a style sheet class to included on
		all links (anchor tags) created by this plug-in.<br />
		HTML will look like:<br />
		<pre>&lt;a href="link_to_product" class="<b>your_css_class</b>"&gt;keyord_phrase&lt;/a&gt;</pre>
		<a href="http://www.w3schools.com/css/" target="_blank">Click here for
		more information about CSS</a><br />
		</div>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">Use nofollow</th>
		<td><input type="checkbox" name="ucontext_nofollow" value="1"
		<?php if (intval(get_option('ucontext_nofollow'))){ echo ' checked'; } ?> /><br />
		Includes "nofollow" attribute on links (anchor tags) created by this
		plug-in.</td>
	</tr>

	<tr valign="top">
		<th scope="row">Open New Window</th>
		<td><input type="checkbox" name="ucontext_new_window" value="1"
		<?php if (intval(get_option('ucontext_new_window'))){ echo ' checked'; } ?> /><br />
		Includes target="_blank" attribute on links (anchor tags) created by
		this plug-in.</td>
	</tr>

</table>

<input type="hidden" name="action" value="update" /> <input
	type="hidden" name="page_options"
	value="ucontext_intext_class,ucontext_nofollow" />

<p class="submit"><input type="submit" class="button-primary"
	value="<?php _e('Save Changes') ?>" /> <input type="button"
	value="<?php _e('Clear Cache') ?>"
	onclick="window.location.href='options-general.php?page=ucontext&clear_cache=1'" />
</p>

</form>
</div>

		<?php
}

class uContext_Widget extends WP_Widget
{

	function uContext_Widget()
	{
		$widget_ops = array(
		'classname' => 'uContext - CB HopAds',
		'description' => 'Let uContext auto-fill your Clickbank HopAd keywords'
		);

		parent::WP_Widget(false, $name = 'uContext - CB HopAds', $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		extract($instance);

		global $wpdb, $post;

		$data = $wpdb->get_var('SELECT data FROM '.$wpdb->base_prefix.'ucontext_cache WHERE post_id = '.intval($post->ID));

		if ($data)
		{
			$data = unserialize($data);

			$keywords = array_keys($data['link_list']);

			$instance['ucontext_hopad_code'] = preg_replace('/hopfeed_keywords\=\'.*?\';/is', 'hopfeed_keywords=\''.implode(',', $keywords).'\';', $instance['ucontext_hopad_code']);
			$instance['ucontext_hopad_code'] = preg_replace('/hopfeed_tab1_keywords\=\'.*?\';/is', 'hopfeed_tab1_keywords=\''.implode(',', $keywords).'\';', $instance['ucontext_hopad_code']);
				
			echo $instance['ucontext_hopad_code'];
		}
	}

	function update($new_instance, $old_instance)
	{
		$new_instance['ucontext_hopad_code'] = trim($new_instance['ucontext_hopad_code']);

		return $new_instance;
	}

	function form($instance)
	{
		echo '
		<p>
			<label for="' . $this->get_field_id('ucontext_hopad_code') . '">HopAd Code:</label>
			<textarea id="' . $this->get_field_id('ucontext_hopad_code') . '" name="' . $this->get_field_name('ucontext_hopad_code') . '" style="width: 100%;">' . $instance['ucontext_hopad_code'] . '</textarea>
		</p>
		';
	}
}

function uContext_filterInText($body)
{
	$uc = new uContext();

	global $post, $wpdb;

	$uc->setApiKey(get_option('ucontext_api_key'));
	$uc->setInTextClass(get_option('ucontext_intext_class'));
	$uc->setNoFollow(get_option('ucontext_nofollow'));
	$uc->setNewWindow(get_option('ucontext_new_window'));
	$uc->setTitle($post->post_title);
	$uc->setBody($body);

	$permalink = get_permalink($post->ID);

	$uc->setPermalink($permalink);

	$config = get_option('ucontext_config');

	if ($config && !is_array($config))
	{
		$config = unserialize($config);
	}

	$use_cache = FALSE;
	$cache_valid = FALSE;

	if (isset($config['use_cache']) && intval($config['use_cache']))
	{
		$use_cache = TRUE;

		$data = $wpdb->get_var('SELECT data FROM '.$wpdb->base_prefix.'ucontext_cache WHERE post_id = '.intval($post->ID).' AND expire > '.time());

		if ($data)
		{
			$data = unserialize($data);
			$data['from_cache'] = 1;

			$uc->setCacheData($data);
			$cache_valid = TRUE;
		}
	}

	if (strtolower($_SERVER['HTTPS']) == 'on')
	{
		$uc->setUrl('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
	}
	else
	{
		$uc->setUrl('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
	}

	$result = $uc->getInText();

	if (is_array($uc->data) && count($uc->data) && $uc->data['use_cache'] && !$cache_valid)
	{
		$wpdb->query('REPLACE INTO '.$wpdb->base_prefix.'ucontext_cache (post_id, data, expire) VALUES ('.intval($post->ID).', "'.addslashes(serialize($uc->data)).'", '.intval($uc->data['expire']).')');

		$config = $uc->data;
		unset($config['link_list']);

		update_option('ucontext_config', serialize($config));
	}

	$wpdb->query('DELETE FROM '.$wpdb->base_prefix.'ucontext_cache WHERE expire < '.time());

	return $result;
}

function uContext_init()
{
	if (!get_option('ucontext_api_key'))
	{
		echo "<div id='ucontext-warning' class='updated fade'><p><strong>".__('uContext is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your API Key</a> for it to work.'), "options-general.php?page=ucontext")."</p></div>";
	}
}

function uContext_activate()
{
	if (UCONTEXT_VERSION != get_option('ucontext_version'))
	{
		global $wpdb;

		if (file_exists(ABSPATH.'/wp-admin/upgrade-functions.php'))
		{
			require_once(ABSPATH.'/wp-admin/upgrade-functions.php');
		}
		else
		{
			require_once(ABSPATH.'/wp-admin/includes/upgrade.php');
		}

		dbDelta('CREATE TABLE `'.$wpdb->base_prefix.'ucontext_cache` (
			`post_id` int(11) unsigned NOT NULL,
			`data` text,
			`expire` int(11) unsigned NOT NULL,
			PRIMARY KEY (`post_id`)
		);');

		update_option('ucontext_version', UCONTEXT_VERSION);
	}

	if (!get_option('ucontext_api_key'))
	{
		$api_key_file = dirname(__FILE__).'/api_key.txt';
		if (is_file($api_key_file))
		{
			$api_key = file_get_contents($api_key_file);
			if (strlen(trim($api_key)) == 32)
			{
				update_option('ucontext_api_key', $api_key);
			}
		}
	}
}