<?php
namespace gkd\WP_Interface;

class Renderer {
    public static function render( $attributes, $content ) {
        error_log( 'GKD Gallery Renderer::render() called' );
        error_log( 'GKD Gallery Renderer attributes: ' . print_r( $attributes, true ) );
        error_log( 'GKD Gallery Renderer content: ' . $content );

        $result = Engine::run( $attributes );
        error_log( 'GKD Gallery Renderer result length: ' . strlen( $result ) );
        return $result;
    }
}