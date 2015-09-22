<?php

// load the the base file
$base=require('app/lib/base.php');

if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

// Load configuration
$base->config('config/config.ini');

$base->main();