<?php

use PhpConsole\Application;

require __DIR__ . '/../vendor/autoload.php';


Application::create(__DIR__ . '/app', 'Commands')->run();

