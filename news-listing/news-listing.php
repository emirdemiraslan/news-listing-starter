<?php
/**
 * Plugin Name: News Listing Shortcode
 * Description: Configurable news listings via [news_listing] shortcode with grid or carousel layouts, tag badges, and ACF-powered category icons.
 * Version: 1.0.1
 * Requires at least: 4.9.8
 * Requires PHP: 7.2.1
 * Author: Your Name
 * License: GPLv2 or later
 * Text Domain: news-listing
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Basic constants.
if ( ! defined( 'NLS_VERSION' ) ) {
    define( 'NLS_VERSION', '1.0.1' );
}
if ( ! defined( 'NLS_FILE' ) ) {
    define( 'NLS_FILE', __FILE__ );
}
if ( ! defined( 'NLS_DIR' ) ) {
    define( 'NLS_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'NLS_URL' ) ) {
    define( 'NLS_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load text domain.
 */
function nls_load_textdomain() {
    load_plugin_textdomain( 'news-listing', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'nls_load_textdomain' );

// Include shortcode.
require_once NLS_DIR . 'includes/Shortcode.php';

/**
 * Initialize shortcode.
 */
add_action( 'init', array( 'NLS_Shortcode', 'init' ) );
