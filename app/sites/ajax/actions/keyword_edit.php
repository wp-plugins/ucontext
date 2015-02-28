<div style="padding: 20px;">
<?php

require UCONTEXT_APP_PATH.'/Ucontext_Form.php';

if (isset($_REQUEST['keyword_id']) && (int)$_REQUEST['keyword_id'])
{
	$form_vars = $wpdb->get_row('SELECT * FROM '.self::$table['keyword'].' WHERE keyword_id = '.(int)$_REQUEST['keyword_id'], ARRAY_A);
	$form_vars['config'] = unserialize($form_vars['config']);
}

if (!@$form_vars['custom_search'] && @$form_vars['keyword'])
{
	$form_vars['custom_search'] = $form_vars['keyword'];
}

?>
<form id="ucontext_keyword_edit_form">
<?php

function array_unshift_assoc($arr, $key, $val)
{
	$arr = array_reverse($arr, true);
	$arr[$key] = $val;
	return array_reverse($arr, true);
}

Ucontext_Form::fadeSave();

Ucontext_Form::startTable();

Ucontext_Form::textField('Keyword', 'keyword', @$form_vars['keyword'], 50);

Ucontext_Form::checkboxField('Disable', 'disabled', @$form_vars['disabled'], 'Disables this keyword site-wide');

Ucontext_Form::textField('Customize Search', 'custom_search', @$form_vars['custom_search'], 50, 'Alternate keyword to be searched in the catalog.');

@include UCONTEXT_INTEGRATION_PATH.'/ajax/snippets/keyword_edit.php';

?>
	<tr>
		<td colspan="2"><div id="ucontext_search_results"></div></td>
	</tr>
	<tr>
		<td colspan="2">
			<input id="ucontext_save_button" type="button" class="ucontext_button" value="Save" />&nbsp;
			<?php if ((int)@$form_vars['keyword_id']){ ?>
			<input id="ucontext_delete_button" type="button" class="ucontext_button" value="Delete" onclick="uC_deleteKeyword(<?php echo (int)@$form_vars['keyword_id'] ?>)" />
			<?php } ?>
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
jQuery(document).ready(function(){

	<?php if ((int)@$form_vars['keyword_id']){ ?>
	jQuery.get('<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_search&keyword_id=<?php echo (int)@$form_vars['keyword_id'] ?>', function(data) {
		jQuery('#ucontext_search_results').html(data);
	});
	<?php } ?>

	jQuery('#ucontext_save_button').click( function() {
		jQuery.ajax({
			type: 'POST',
			url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_save&keyword_id=<?php echo (int)@$form_vars['keyword_id'] ?>',
			data: jQuery('#ucontext_keyword_edit_form').serialize(),
			dataType: 'json'
		}).done(function( response ) {
			if (response.success == true || response.success == 'true')
			{
				if (response.keyword_id != null && response.keyword_id > 0)
				{
					uC_editKeyword(response.keyword_id, 1);

					if (response.new_keyword == true || response.refresh_list == true)
					{
						uC_loadKeywordList('');
					}
				}
				else
				{
					alert('There was an error. Please check your information.');
				}
			}
			else
			{
				if (response.error != null && response.error != '')
				{
					alert(response.error);
				}
				else
				{
					alert('There was an error. Please check your information.');
				}
			}
  		});
	});

	jQuery('#ucontext_delete_button').click( function() {
		jQuery.ajax({
			type: 'POST',
			url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_delete&keyword_id=<?php echo (int)@$form_vars['keyword_id'] ?>',
			dataType: 'json'
		}).done(function(response) {
			if (response.success == true || response.success == 'true')
			{
				jQuery('#ucontext_keyword_edit').html('');
				uC_loadKeywordList('');
			}
			else
			{
				if (response.error != null && response.error != '')
				{
					alert(response.error);
				}
				else
				{
					alert('There was an error. Please check your information!');
				}
			}
  		});
	});
});
</script>
</div>