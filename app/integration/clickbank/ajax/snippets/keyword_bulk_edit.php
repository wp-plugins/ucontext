<tr>
	<th>Category</th>
	<td>
		<div style="padding: 5px; border: 1px solid #BBB; height: 150px; overflow-y: scroll;">
<?php

require UCONTEXT_INTEGRATION_PATH.'/lists/category_list.php';
//$category_list = array_unshift_assoc($category_list, 0, '-- Default categories --');

foreach ($category_list as $category_id => $category)
{
	$checked = '';
	if ((int)@$form_vars['config']['category'][$category_id])
	{
		$checked = ' checked';
	}

	echo '<input type="checkbox" name="config[category]['.$category_id.']"'.$checked.' value="1" /> '.$category.'<br />';
}

?>
		</div>
	</td>
</tr>