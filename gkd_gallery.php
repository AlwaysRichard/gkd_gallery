<?php
/**
 * Plugin Name: GKD Gallery
 * Description: WordPress Gutenberg block for photo galleries with tiled, grid, masonry, and collage layouts.
 * Author: AlwaysRichard
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gkd-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 1. Dynamically load and instantiate all taxonomy classes
 *    (Runs early so their init hooks register correctly)
 */
function gkd_gallery_load_taxonomies() {
	$taxonomy_dir = plugin_dir_path( __FILE__ ) . '/includes/taxonomies/';

	foreach ( glob( $taxonomy_dir . 'class-*.php' ) as $file ) {
		require_once $file;

		// Extract class name from filename
		$base  = basename( $file, '.php' );
		$parts = explode( '-', $base );
		$class = end( $parts ); // e.g. Page_Type

		if ( class_exists( $class ) ) {
			new $class(); // constructor attaches its own init hook
		}
	}
}

// IMPORTANT: load classes BEFORE init fires
add_action( 'plugins_loaded', 'gkd_gallery_load_taxonomies' );

/**
 * 2. Register blocks dynamically
 */
function gkd_gallery_register_blocks() {
    // Path to your blocks directory
    $blocks_dir = plugin_dir_path( __FILE__ ) . '/blocks/';

    // Use a glob to find every block.json inside your plugin folders
    $block_json_files = glob( $blocks_dir . '*/block.json' );

    foreach ( $block_json_files as $file ) {
        $block_dir = dirname( $file );

        // Load block_init.php if it exists (registers PHP classes for render callback)
        $init_file = $block_dir . '/block_init.php';
        if ( file_exists( $init_file ) ) {
            require_once $init_file;
        }

        // Register the block with explicit render callback
        register_block_type( $block_dir, [
            'render_callback' => [ 'gkd\WP_Interface\Renderer', 'render' ]
        ] );
    }
}

add_action( 'init', 'gkd_gallery_register_blocks' );

/**
 * 3. Activation hook
 *    - Load taxonomy classes
 *    - Trigger init so they register
 *    - Flush rewrite rules
 */
function gkd_gallery_activate_taxonomies() {
	// Load classes so their init hooks exist
	gkd_gallery_load_taxonomies();

	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'gkd_gallery_activate_taxonomies' );

/**
 * 4. Deactivation hook
 *    - Flush rewrite rules
 */
function gkd_gallery_deactivate_taxonomies() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'gkd_gallery_deactivate_taxonomies' );
