<?php
namespace gkd\WP_Interface;

use gkd\Impl\Main;

class Engine {
    public static function run( $attributes ) {
	$main = new Main();
        return $main->run( $attributes );
    }
}
