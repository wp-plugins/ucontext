<?php

$limit = 50;

$page = '';
$result = '';
$search_exp = '';

if (isset($_REQUEST['p']))
{
	$page = (int)$_REQUEST['p'];
}

if (!$page)
{
	$page = 1;
}

if (isset($_REQUEST['search']) && trim($_REQUEST['search']))
{
	$search = trim(strtolower($_REQUEST['search']));

	if ($search)
	{
		$search_exp = ' WHERE keyword LIKE "%'.$search.'%"';
	}
}

if (isset($_REQUEST['no_results']) && (int)$_REQUEST['no_results'])
{
	$search_exp = ' WHERE last_updated > 0 AND num_results = 0';
}

$start = ($page - 1) * $limit;

$sql = '
SELECT
	COUNT(*)
FROM
	'.self::$table['keyword'].'
'.$search_exp;

$total = $wpdb->get_var($sql);

$max_pages = ceil($total / $limit);

$sql = '
SELECT
	*
FROM
	'.self::$table['keyword'].'
'.$search_exp.'
ORDER BY
	`keyword`
LIMIT
	'.$start.', '.$limit;

$keyword_list = $wpdb->get_results($sql, ARRAY_A);

$prev = '';
if ($page > 1)
{
	$prev = '<a href="javascript:uC_goToPage('.($page - 1).')">&lt;&lt;&nbsp;Previous</a>';
}

$next = '';
if ($page < $max_pages)
{
	$next = '<a href="javascript:uC_goToPage('.($page + 1).')">Next&nbsp;&gt;&gt;</a>';
}

ob_end_clean();

if ($prev || $next)
{
	$result .=<<<END
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td colspan="2">
		<table width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td width="50%">{$prev}</td>
			<td width="50%" style="text-align: right;">{$next}</td>
		</tr>
		</table>
	</td>
</tr>
</table>
END;
}

if ($keyword_list)
{
	foreach ($keyword_list as $keyword)
	{
		$icon = '';

		if ($keyword['disabled'])
		{
			$icon = '<img src="'.UCONTEXT_PLUGIN_URL.'/includes/icons/bullet_red.png" width="16" height="16" alt="Disabled" style="float: right; margin: 0 0 0 5px;" />';
		}
		elseif ($keyword['last_updated'] && !$keyword['num_results'])
		{
			$icon = '<img src="'.UCONTEXT_PLUGIN_URL.'/includes/icons/bullet_error.png" width="16" height="16" alt="No search results" style="float: right; margin: 0 0 0 5px;" />';
		}

		$result .=<<<END
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td width="1%"><input type="checkbox" name="keyword_list[{$keyword['keyword_id']}]" value="{$keyword['keyword_id']}" /></td>
	<td onclick="uC_editKeyword({$keyword['keyword_id']})">{$keyword['keyword']}{$icon}</td>
</tr>
</table>
END;
	}
}
else
{
	$result .=<<<END
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td colspan="2"><br /><center><strong>No keywords found yet</strong></center><br /></td>
</tr>
</table>
END;
}

if ($prev || $next)
{
	$result .=<<<END
<table width="100%" cellspacing="0" cellpadding="0" class="ucontext_list_table">
<tr>
	<td colspan="2">
		<table width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td width="50%">{$prev}</td>
			<td width="50%" style="text-align: right;">{$next}</td>
		</tr>
		</table>
	</td>
</tr>
</table>
END;
}

exit('<form id="ucontext_keyword_list_form">'.$result.'</form>');