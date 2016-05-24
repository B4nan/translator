<?php

date_default_timezone_set('Europe/Prague');

define('TEMP_DIR', __DIR__ . '/temp/' . getmypid());

// Load Nette Framework
require __DIR__ . '/../vendor/autoload.php';

Tester\Helpers::purge(TEMP_DIR);
Tester\Environment::setup();

mkdir(TEMP_DIR . '/sessions', 0777);
