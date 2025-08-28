<?php
// Define ABSPATH to satisfy plugin guard.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Polyfill sanitize_title if WordPress is not loaded.
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = (string) $title;
		$title = strtolower( trim( $title ) );
		// Replace non-alphanumeric with dashes, collapse repeats, trim dashes.
		$title = preg_replace( '/[^a-z0-9]+/i', '-', $title );
		$title = preg_replace( '/-+/', '-', $title );
		$title = trim( $title, '-' );
		return $title;
	}
}

// Load class under test.
require_once dirname( __DIR__ ) . '/news-listing/includes/Shortcode.php'; 