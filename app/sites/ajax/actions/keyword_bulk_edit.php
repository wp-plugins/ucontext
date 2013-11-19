<?php

$_REQUEST['bulk_list'] = preg_replace('/[^0-9\,]+/is', '', $_REQUEST['bulk_list']);

$keyword_id_list = explode(',', $_REQUEST['bulk_list']);

$_REQUEST['keyword_id'] = array_shift($keyword_id_list);

$form_vars = $wpdb->get_row('SELECT * FROM '.self::$table['keyword'].' WHERE keyword_id = '.(int)$_REQUEST['keyword_id'], ARRAY_A);
$form_vars['config'] = unserialize($form_vars['config']);

?>

<div style="padding: 20px;">

<form id="ucontext_keyword_edit_form">
<input type="hidden" name="bulk_list" value="<?php echo $_REQUEST['bulk_list'] ?>" />
<?php

require UCONTEXT_APP_PATH.'/Ucontext_Form.php';

function array_unshift_assoc($arr, $key, $val)
{
	$arr = array_reverse($arr, true);
	$arr[$key] = $val;
	return array_reverse($arr, true);
}

Ucontext_Form::fadeSave();

Ucontext_Form::startTable();

Ucontext_Form::checkboxField('Disable', 'disabled', @$form_vars['disabled']);

Ucontext_Form::textField('Customize Search', 'custom_search', @$form_vars['custom_search'], 50, 'Alternate keyword to be searched in the catalog.');

@include UCONTEXT_INTEGRATION_PATH.'/ajax/snippets/keyword_bulk_edit.php';

?>
	<tr>
		<td colspan="2"><div id="ucontext_search_results"></div></td>
	</tr>
	<tr>
		<td colspan="2">
			<input id="ucontext_save_button" type="button" class="ucontext_button" value="Save" />&nbsp;
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
jQuery(document).ready(function(){

	<?php if (@$_REQUEST['bulk_list']){ ?>
	jQuery.get('<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_search&keyword_id=<?php echo @$_REQUEST['bulk_list'] ?>', function(data) {
		jQuery('#ucontext_search_results').html(data);
	});
	<?php } ?>

	jQuery('#ucontext_save_button').click( function() {
		jQuery.ajax({
			type: 'POST',
			url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_bulk_save&bulk_list=<?php echo @$_REQUEST['bulk_list'] ?>',
			data: jQuery('#ucontext_keyword_edit_form').serialize(),
			dataType: 'json',
			success: function( response ){
				jQuery.get('<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_search&keyword_id=<?php echo @$_REQUEST['bulk_list'] ?>', function(data) {
					jQuery('#ucontext_search_results').html(data);
				});
				ucontext_fadeSaved("ucontext_saved");
			}
		});
	});
});
</script>
</div>