<form action="admin.php?page=<?php echo self::$name ?>&action=settings_save" method="POST">
<?php

require UCONTEXT_APP_PATH.'/Ucontext_Form.php';

Ucontext_Form::fadeSave();

Ucontext_Form::startTable();

Ucontext_Form::listErrors(self::$form_errors);

@include UCONTEXT_INTEGRATION_PATH.'/admin/snippets/settings.php';

Ucontext_Form::clearRow();
Ucontext_Form::section('Link Settings');

$max_links_list = array();
for ($i = 1; $i <= 25; $i++)
{
	$max_links_list[$i] = $i;
}
Ucontext_Form::selectField('Max. Number of Links', 'form_vars[ucontext_max_links]', @get_option('ucontext_max_links', 5), $max_links_list);

$display_list = array(
'Pages &amp; Posts',
'Posts only',
'Pages only'
);

Ucontext_Form::selectField('Show Links on', 'form_vars[ucontext_links_display]', @get_option('ucontext_links_display'), $display_list);

Ucontext_Form::checkboxField('No Links in RSS', 'form_vars[ucontext_hide_rss_links]', @get_option('ucontext_hide_rss_links'), 'Check this box to remove uContext links from your standard RSS feed');

Ucontext_Form::checkboxField('Disable Auto-Keywords', 'form_vars[ucontext_no_autokeywords]', @get_option('ucontext_no_autokeywords', 0), 'If you don\'t want this plugin to find keywords for you, check this box.  When checked, only manually entered keywords will be used.');

?>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" class="button-primary action" value="Save" />
			</td>
		</tr>
	</table>

	</form>