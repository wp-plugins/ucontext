<?php

Ucontext_Form::section('uContext Settings');

$extra = '';
if (get_option('rlm_notification_'.Ucontext_Base::$name))
{
	$extra .= get_option('rlm_notification_'.Ucontext_Base::$name);
}
elseif (get_option('rlm_license_status_'.Ucontext_Base::$name, 0))
{
	$extra .= '<div class="ucontext_valid_license">License is valid</div>';
}
$extra .= 'Get your uContext API Key from the <a href="http://ucontext.com/wp-login.php" target="_blank">members area...</a>';

Ucontext_Form::textField('uContext License Key', 'form_vars[rlm_license_key]', @get_option('rlm_license_key_'.Ucontext_Base::$name), NULL, $extra, TRUE);

Ucontext_Form::clearRow();

Ucontext_Form::section('Clickbank Settings');

Ucontext_Form::textField('Clickbank Nickname/ID', 'form_vars[ucontext_clickbank_nickname]', @get_option('ucontext_clickbank_nickname'), NULL, 'Your Clickbank affiliate ID, also called nickname, from the <a href="https://accounts.clickbank.com/login.htm" target="_blank">account area...</a>', TRUE);

Ucontext_Form::textField('Min. Gravity', 'form_vars[ucontext_clickbank_min_gravity]', @get_option('ucontext_clickbank_min_gravity', '0.01'), 10);

Ucontext_Form::textField('Min. Commission %', 'form_vars[ucontext_clickbank_min_commission]', @get_option('ucontext_clickbank_min_commission'), 10);

Ucontext_Form::textField('Min. $/sale', 'form_vars[ucontext_clickbank_min_sale]', @get_option('ucontext_clickbank_min_sale'), 10);

Ucontext_Form::textField('Min. Total $/sale', 'form_vars[ucontext_clickbank_min_total_sale]', @get_option('ucontext_clickbank_min_total_sale'), 10);

Ucontext_Form::textField('Min. Referred %', 'form_vars[ucontext_clickbank_min_referred]', @get_option('ucontext_clickbank_min_referred'), 10);

Ucontext_Form::textField('Min. Rebilled $', 'form_vars[ucontext_clickbank_min_rebill]', @get_option('ucontext_clickbank_min_rebill'), 10);

Ucontext_Form::checkboxField('Recurring Only', 'form_vars[ucontext_clickbank_recurring_only]', @get_option('ucontext_clickbank_recurring_only'));

?>
<tr>
	<th>Categories:</th>
	<td>
	<div
		style="padding: 5px; border: 1px solid #BBB; height: 150px; overflow-y: scroll;">
		<?php

		$ucontext_clickbank_category_list = @get_option('ucontext_clickbank_category_list');

		require UCONTEXT_INTEGRATION_PATH.'/lists/category_list.php';

		foreach ($category_list as $category_id => $category)
		{
			$checked = '';
			if ((int)$ucontext_clickbank_category_list[$category_id])
			{
				$checked = ' checked';
			}

			echo '<input type="checkbox" name="form_vars[ucontext_clickbank_category_list]['.$category_id.']"'.$checked.' value="1" /> '.$category.'<br />';
		}

		?></div>
	<p>Uncheck all categories to search the entire Clickbank catalog.</p>
	</td>
</tr>

		<?php
		if (@get_option('rlm_license_key_'.Ucontext_Base::$name))
		{
			if (!(int)@get_option('ucontext_settings_import_done', 0) && UCONTEXT_INTEGRATION_HANDLE == 'clickbank')
			{
				?>
<tr>
	<th>Import Settings/Filters:</th>
	<td><input type="button" value="Import Now"
		onclick="window.location.href='admin.php?page=ucontext&action=import_settings'" />
	<p>Click this button to import settings and filters from the previous
	plug-in version.</p>
	</td>
</tr>
				<?php
			}

			if (!(int)@get_option('ucontext_log_archive_done', 0) && UCONTEXT_INTEGRATION_HANDLE == 'clickbank')
			{
				?>
<tr>
	<th>Import Logs:</th>
	<td><input type="button" value="Import Now"
		onclick="window.location.href='admin.php?page=ucontext&action=import_logs'" />
	<p>Click this button to import logs from the previous plug-in version.</p>
	</td>
</tr>
				<?php
			}
		}