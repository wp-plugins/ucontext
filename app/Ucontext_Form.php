<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext_Form
{

	public static function textField($label, $name, $value, $size = 50, $caption = '', $required = FALSE)
	{
		if (!intval($size)){ $size = 50; }
		echo '<tr><th>'.$label.':</th><td>';
		echo '<input type="text" name="'.$name.'" value="'.htmlspecialchars($value).'" size="'.$size.'" onchange="ucontext_form_changed = true;" />';
		if ($required)
		{
			echo '<span class="required">Required</span>';
		}
		if (strlen(trim($caption)))
		{
			echo '<div class="caption">'.$caption.'</div>';
		}
		echo '</td></tr>';
	}

	public static function checkboxField($label, $name, $value, $caption = '', $required = FALSE)
	{
		echo '<tr><th>'.$label.':</th><td>';
		$checked = '';
		if (intval($value))
		{
			$checked = ' checked';
		}
		echo '<input type="checkbox" name="'.$name.'" value="1"'.$checked.' onchange="ucontext_form_changed = true;" />';
		if ($required)
		{
			echo '<span class="required">Required</span>';
		}
		if (strlen(trim($caption)))
		{
			echo '<div class="caption">'.$caption.'</div>';
		}
		echo '</td></tr>';
	}

	public static function textareaField($label, $name, $value, $rows = 3, $cols = 40, $caption = '', $max_length = NULL, $required = FALSE)
	{
		if (!intval($rows)){ $row = 3; }
		if (!intval($cols)){ $cols = 40; }

		if (intval($max_length)){ $max_length = ' maxlength="'.$max_length.'"'; }

		echo '<tr><th>'.$label.':</th><td>';
		echo '<textarea name="'.$name.'" rows="'.$rows.'" cols="'.$cols.'"'.$max_length.' onchange="ucontext_form_changed = true;">'.htmlspecialchars($value).'</textarea>';
		if ($required)
		{
			echo '<div class="required">Required</div>';
		}
		if (strlen(trim($caption)))
		{
			echo '<div class="caption">'.$caption.'</div>';
		}
		echo '</td></tr>';
	}

	public static function selectField($label, $name, $value, $list, $caption = '', $required = FALSE)
	{
		echo '<tr><th>'.$label.':</th><td>';
		echo '<select name="'.$name.'" onchange="ucontext_form_changed = true;">';

		if (is_array($list))
		{
			foreach ($list as $key => $opt_value)
			{
				$selected = '';
				if (trim($key) == trim($value))
				{
					$selected = ' selected';
				}
				echo '<option value="'.$key.'"'.$selected.'>'.$opt_value.'</option>';
			}
		}
		echo '</select>';
		if ($required)
		{
			echo '<span class="required">Required</span>';
		}
		if (strlen(trim($caption)))
		{
			echo '<div class="caption">'.$caption.'</div>';
		}
		echo '</td></tr>';
	}

	public static function startTable()
	{
		echo '<table class="ucontext_form_table" cellpadding="0" cellspacing="5">';
	}

	public static function fadeSave()
	{
		echo '<div id="ucontext_saved" style="display: none; padding: 10px;">Saved</div>';
		if (isset($_GET['saved']) && $_GET['saved'])
		{
			echo '<script language="Javascript">ucontext_fadeSaved("ucontext_saved");</script>';
		}
	}

	public static function listErrors($error_list)
	{
		if (is_array($error_list) && count($error_list))
		{
			echo '<tr><td colspan="2">';
			echo '<ul class="ucontext_error_list">';
			foreach ($error_list as $msg)
			{
				echo '<li>'.$msg.'</li>';
			}
			echo '</ul>';
			echo '</td></tr>';
		}
	}

	public static function labelField($label, $value)
	{
		echo '<tr><th>'.$label.':</th><td style="vertical-align: middle;">'.$value.'</td></tr>';
	}

	public static function blankRow()
	{
		echo '<tr><th>&nbsp;</th><td>&nbsp;</td></tr>';
	}

	public static function clearRow($text = '')
	{
		if (!$text)
		{
			$text = '&nbsp;';
		}
		echo '<tr><td colspan="2">'.$text.'</td></tr>';
	}

	public static function section($title)
	{
		echo '<tr><td colspan="2" class="section"><h3>'.$title.'</h3></td></tr>';
	}

	public static function endTable()
	{
		echo '</table>';
	}
}