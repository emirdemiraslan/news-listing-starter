<?php
use PHPUnit\Framework\TestCase;

final class NLSShortcodeParsingTest extends TestCase {
	public function test_parse_category_slugs_trims_and_sanitizes() {
		$input = ' Business, technology ,   design-dev ';
		$expected = array( 'business', 'technology', 'design-dev' );
		$this->assertSame( $expected, NLS_Shortcode::parse_category_slugs( $input ) );
	}

	public function test_parse_category_slugs_ignores_empty_and_symbols() {
		$input = ',, , @weird!!, , ,news ';
		$expected = array( 'weird', 'news' );
		$this->assertSame( $expected, NLS_Shortcode::parse_category_slugs( $input ) );
	}

	public function test_normalize_count_falls_back_to_default_when_invalid() {
		$this->assertSame( 9, NLS_Shortcode::normalize_count( 0 ) );
		$this->assertSame( 9, NLS_Shortcode::normalize_count( -5 ) );
		$this->assertSame( 9, NLS_Shortcode::normalize_count( 'not-a-number' ) );
	}

	public function test_normalize_count_accepts_positive_int() {
		$this->assertSame( 12, NLS_Shortcode::normalize_count( 12 ) );
		$this->assertSame( 3, NLS_Shortcode::normalize_count( '3' ) );
	}
} 