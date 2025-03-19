<?php
namespace Core;

define('CORE_ROOT', __dir__);
$root = CORE_ROOT;
while (!is_dir($root .'/config')) {
	$root = realpath($root. '/..');
}
define('SERVER_ROOT', $root);
define('CONFIG_PATH', $root . '/config');
