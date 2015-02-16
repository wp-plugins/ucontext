<?php

$action = self::$action;
$action_parts = explode('_', $action);
self::$context = array_shift($action_parts);