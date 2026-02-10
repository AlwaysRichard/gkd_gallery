<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

error_log('GKD Gallery: Loading PHP classes for block');

require_once __DIR__ . '/Renderer.php';
require_once __DIR__ . '/Engine.php';

require_once __DIR__ . '/src/main/Main.php';
require_once __DIR__ . '/src/impl/Queries.php';
require_once __DIR__ . '/src/impl/HtmlBuilder.php';
require_once __DIR__ . '/src/library/Exif.php';

error_log('GKD Gallery: All PHP classes loaded successfully');
