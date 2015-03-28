<script type="text/javascript">

function ucontext_fadeSaved(id)
{
	jQuery('#' + id).show();
	jQuery('#' + id).fadeOut(2000);
}

</script>

<div id="ucontext_admin">

	<img src="<?php echo UCONTEXT_PLUGIN_URL ?>/app/integration/<?php echo strtolower(UCONTEXT_INTEGRATION_HANDLE); ?>/images/logo.png" width="200" height="55" border="0" alt="uContext for <?php echo UCONTEXT_INTEGRATION_TITLE; ?>" style="float: left;" /> <div style="float: left; width: 100px;"><strong>v<?php echo UCONTEXT_VERSION ?></strong></div>
	<div id="ucontext_ads"><?php echo @wp_remote_retrieve_body(@wp_remote_get('http://ucontext.com/plugin_ads/ucontext_ads.php?version='.UCONTEXT_VERSION.'&type=free')); ?></div>
	<div style="clear: both;"></div>
<?php

if (version_compare(UCONTEXT_VERSION, get_option('rlm_version_'.self::$name, UCONTEXT_VERSION), '<'))
{
	echo '<div style="color: #900;"><a href="http://ucontext.com/wp-login.php" target="_blank">Click here to download the latest version</a></div>';
}

?>
	<br />
	<table id="ucontext_layout_table" style="width: 99%;">
	<tr>
		<td>
			<ul id="ucontext_nav_tabs">
				<li<?php if (self::$action == 'keywords'){ echo ' class="selected"'; } ?>><a href="admin.php?page=<?php echo self::$name ?>&action=keywords"><img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/application_side_list.png" width="16" height="16" border="0" />Keywords</a></li>
				<li<?php if (self::$action == 'reports'){ echo ' class="selected"'; } ?>><a href="admin.php?page=<?php echo self::$name ?>&action=reports"><img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/chart_bar.png" width="16" height="16" border="0" />Reports</a></li>
				<li<?php if (self::$action == 'designer'){ echo ' class="selected"'; } ?>><a href="admin.php?page=<?php echo self::$name ?>&action=designer"><img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/palette.png" width="16" height="16" border="0" />Link Designer</a></li>
				<li<?php if (self::$action == 'settings'){ echo ' class="selected"'; } ?>><a href="admin.php?page=<?php echo self::$name ?>&action=settings"><img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/cog.png" width="16" height="16" border="0" />Settings</a></li>
				<li<?php if (self::$action == 'help'){ echo ' class="selected"'; } ?>><a href="admin.php?page=<?php echo self::$name ?>&action=help');"><img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/help.png" width="16" height="16" border="0" />Help</a></li>
			</ul>
			<div id="ucontext_view_wrapper">
				<div id="ucontext_view_box">
					<div style="margin: 15px;">
					<?php require($view_path); ?>
					</div>
				</div>
				<div id="ucontext_spacer"></div>
				<div style="clear: both;"></div>
			</div>

			<p>
				<center><a href="http://ucontext.com">uContext</a> is not affiliated with <?php echo UCONTEXT_INTEGRATION_TITLE ?></center>
			</p>
		</td>
	</tr>
	</table>
</div>
<?php
require_once UCONTEXT_APP_PATH.'/Ucontext_Cron.php';
Ucontext_Cron::init();
Ucontext_Cron::updateKeywordSearchResults();