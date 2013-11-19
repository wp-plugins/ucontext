<!--

- list manual and found keywords
	- icon to identify each
	- ability to disable keywords

- manual keywords take precedence
	- find keywords on submission
	- auto-search for links

- pop-over UI to add/edit keyword
	- search w/ button to "use"
	- search by top browser nodes

- post/page editor icon to create direct link
	- use keyword search UI

actions
- keyword_search
- keyword_save

-->

<table id="ucontext_keyword_viewport" cellpadding="0" cellspacing="5">
<?php include(UCONTEXT_SITE_PATH.'/snippets/keywords_buttons.php'); ?>
<tr>
	<td style="height: 1%;">
		<form>
			<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td style="width: 98%;"><input type="text" name="search" id="ucontext_keyword_search" /></td>
				<td><input type="button" value="Search" class="ucontext_button" id="ucontext_keyword_search_button" /></td>
				<td style="padding-left: 5px;"><input type="button" value="Clear" class="ucontext_button" id="ucontext_keyword_clear_button" /></td>
			</tr>
			</table>
		</form>
	</td>
	<td id="ucontext_keyword_edit_td" rowspan="2"><div id="ucontext_keyword_edit"></div></td>
</tr>
<tr>
	<td id="ucontext_keyword_list_td"><div id="ucontext_keyword_list"></div></td>
</tr>
</table>

<div style="padding-top: 5px; line-height: 16px;">
<img src="<?php echo UCONTEXT_PLUGIN_URL ?>/includes/icons/bullet_error.png" width="16" height="16" alt="No search results" style="float: left; margin-right: 1px;" /> = No matching products found
</div>

<script type="text/javascript">

var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action';

jQuery(document).ready(function(){
	uC_loadKeywordList('');

	jQuery('#ucontext_keyword_search').keypress(function(e){
		if (e.which == 13){
			e.preventDefault();
			uC_doSearch();
		}
	});	

	jQuery('#ucontext_keyword_search_button').click(function(){
		uC_doSearch();
	});	

	jQuery('#ucontext_keyword_clear_button').click(function(){
		jQuery('#ucontext_keyword_search').val('');

		uC_loadKeywordList('');
	});	
});

function uC_doSearch()
{
	uC_loadKeywordList(jQuery('#ucontext_keyword_search').val());
}

function uC_editKeyword(keyword_id, saved)
{
	if (saved == null || saved == '')
	{
		saved = 0;
	}

	uC_mask('ucontext_keyword_edit');
	jQuery.get(ajax_url + '&do=keyword_edit&keyword_id=' + keyword_id + '&saved=' + parseInt(saved), function(data) {
		jQuery('#ucontext_keyword_edit').html(data);
	});
}

function uC_deleteKeyword(keyword_id)
{
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_delete&keyword_id=' + keyword_id,
		dataType: 'json',
		success: function( response ) {
			if (response.success == false)
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
			else
			{
				jQuery('#ucontext_keyword_edit').html('');
				uC_loadKeywordList('');
			}
  		}
	});
}

function uC_disableSelectedKeywords()
{
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_disable',
		dataType: 'json',
		data: jQuery('#ucontext_keyword_list_form').serialize(),
		success: function( response ) {
			if (response.success == false)
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
			else
			{
				jQuery('#ucontext_keyword_edit').html('');
				uC_loadKeywordList('');
			}
  		}
	});
}

function uC_deleteSelectedKeywords()
{
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo admin_url( 'admin-ajax.php' ) ?>?action=ucontext_action&do=keyword_delete',
		dataType: 'json',
		data: jQuery('#ucontext_keyword_list_form').serialize(),
		success: function( response ) {
			if (response.success == false)
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
			else
			{
				jQuery('#ucontext_keyword_edit').html('');
				uC_loadKeywordList('');
			}
  		}
	});
}

function uC_showNoResults()
{
	uC_mask('ucontext_keyword_list');
	jQuery.get(ajax_url + '&do=keyword_grid_list&no_results=1', function(data) {
		jQuery('#ucontext_keyword_list').html(data);
	});
}

function uC_bulkEditSelectedKeywords()
{
	uC_mask('ucontext_keyword_edit');

	var bulk_list = [];

	jQuery('#ucontext_keyword_list_form input:checked').each(function(index, value) {
		bulk_list.push(jQuery(this).val());
	});

	jQuery.get(ajax_url + '&do=keyword_bulk_edit&bulk_list=' + bulk_list.join(','), function(data) {
		jQuery('#ucontext_keyword_edit').html(data);
	});
}

function uC_loadKeywordList(search)
{
	uC_mask('ucontext_keyword_list');
	jQuery.get(ajax_url + '&do=keyword_grid_list&search=' + search, function(data) {
		jQuery('#ucontext_keyword_list').html(data);
	});
}

function uC_goToPage(page)
{
	uC_mask('ucontext_keyword_list');
	jQuery.get(ajax_url + '&do=keyword_grid_list&search=' + jQuery('#ucontext_keyword_search').val() + '&p=' + page, function(data) {
		jQuery('#ucontext_keyword_list').html(data);
	});
}

function uC_mask(id)
{
	jQuery('#' + id).html('<div class="ucontext_loader" style="line-height: ' + jQuery('#' + id).height() + 'px;"><img src="<?php echo UCONTEXT_PLUGIN_URL.'/includes/images/ajax-loading.gif'; ?>" width="220" height="19" alt="Loading..." /></div>');
}

</script>