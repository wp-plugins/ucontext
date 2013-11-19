<?php

if (!is_array(@$_POST['config']['category']))
{
	$_POST['config']['category'] = array();
}