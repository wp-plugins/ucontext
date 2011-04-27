<?php
/*
Plugin Name: Clickbank In-Text Affiliate Links
Plugin URI: http://www.uContext.com
Description: Automatically finds keyword phrases and converts them into contextual in-text Clickbank affiliate links.
Author: Summit Media Concepts LLC
Author URI: http://www.SummitMediaConcepts.com
Tags: clickbank, affiliate, links, ads, advertising, post, context, contextual
Version: 1.3
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

require dirname(__FILE__).'/ucontext_library.php';

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
?>
<div class="wrap">
<h2>uContext Settings</h2>

<form method="post" action="options.php">
<?php settings_fields('ucontext-settings-group'); ?>

<table class="form-table">

<tr valign="top">
<th scope="row">API Key</th>
<td>
	<input type="text" name="ucontext_api_key" value="<?php echo get_option('ucontext_api_key'); ?>" size="50" maxlength="32" /><br />
	Sign-up for your API Key at <a href="http://www.uContext.com/<?php echo $aid; ?>" target="_blank">http://www.uContext.com</a>
</td>
</tr>

<?php
	if ($multisite && $current_site->blog_id == $current_blog->blog_id)
	{
?>
<tr valign="top">
<th scope="row">Affiliate Code</th>
<td>
	<input type="text" name="ucontext_code" value="<?php echo get_option('ucontext_code'); ?>" size="35" maxlength="32" />
</td>
</tr>
<?php
	}
?>

<tr valign="top">
<td scope="row" colspan="2"><br /></td>
</tr>

<tr valign="top">
<td scope="row" colspan="2" style="color: #FFF; background-color: #999; font-size: 12px; font-weight: bold; padding: 2px 10px;">In-Text Settings</td>
</tr>

<tr valign="top">
<th scope="row">Anchor CSS Class</th>
<td>
	<input type="text" name="ucontext_intext_class" value="<?php echo get_option('ucontext_intext_class'); ?>" /><br />
	This is your CSS class to included on all links (anchor tags) created by this plug-in
</td>
</tr>

<tr valign="top">
<th scope="row">Use nofollow</th>
<td>
	<input type="checkbox" name="ucontext_nofollow" value="1" <?php if (intval(get_option('ucontext_nofollow'))){ echo ' checked'; } ?> /><br />
	Includes "nofollow" attribute on links (anchor tags) created by this plug-in
</td>
</tr>

<tr valign="top">
<th scope="row">Open New Window</th>
<td>
	<input type="checkbox" name="ucontext_new_window" value="1" <?php if (intval(get_option('ucontext_new_window'))){ echo ' checked'; } ?> /><br />
	Includes target="_blank" attribute on links (anchor tags) created by this plug-in
</td>
</tr>

</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="ucontext_intext_class,ucontext_nofollow" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
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
		'classname' => 'uContext',
		'description' => 'Display your uContext snippets'
		);

		parent::WP_Widget(false, $name = 'uContext', $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		extract($instance);

		global $single, $post;

		$uc = new uContext();
		$uc->setApiKey(get_option('ucontext_api_key'));
		$uc->setSnippetID($ucontext_snippet_id);
		$uc->setTitle($post->post_title);
		$uc->setBody($post->post_content);

		echo $uc->getSnippet();
	}

	function update($new_instance, $old_instance)
	{
		$new_instance['ucontext_snippet_id'] = intval($new_instance['ucontext_snippet_id']);

		return $new_instance;
	}

	function form($instance)
	{
		echo '
		<p>
			<label for="' . $this->get_field_id('ucontext_snippet_id') . '">Snippet ID:</label>
			<input id="' . $this->get_field_id('ucontext_snippet_id') . '" name="' . $this->get_field_name('ucontext_snippet_id') . '" value="' . $instance['ucontext_snippet_id'] . '" style="width: 100px;" />
		</p>
		';
	}
}

function uContext_filterInText($body)
{
	$uc = new uContext();

	global $post;

	$uc->setApiKey(get_option('ucontext_api_key'));
	$uc->setInTextClass(get_option('ucontext_intext_class'));
	$uc->setNoFollow(get_option('ucontext_nofollow'));
	$uc->setNewWindow(get_option('ucontext_new_window'));
	$uc->setTitle($post->post_title);
	$uc->setBody($body);

	if (strtolower($_SERVER['HTTPS']) == 'on')
	{
		$uc->setUrl('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
	}
	else
	{
		$uc->setUrl('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
	}

	return $uc->getInText();
}

function uContext_init()
{
	if (!get_option('ucontext_api_key'))
	{
		echo "<div id='ucontext-warning' class='updated fade'><p><strong>".__('uContext is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your API Key</a> for it to work.'), "options-general.php?page=ucontext")."</p></div>";
	}
}