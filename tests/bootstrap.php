<?php
/**
 * PHPUnit bootstrap for Beruang plugin unit tests.
 *
 * @package Beruang
 */

// Plugin constants required before loading plugin files.
define( 'ABSPATH', '/' );
define( 'BERUANG_VERSION', '0.4.0-beta' );
define( 'BERUANG_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'BERUANG_PLUGIN_FILE', dirname( __DIR__ ) . '/beruang.php' );
define( 'BERUANG_PLUGIN_URL', 'http://example.com/wp-content/plugins/beruang/' );

// Composer autoloader (includes WP_Mock and PHPUnit).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();

// ---------------------------------------------------------------------------
// Polyfills for pure WP utility functions.
// These are simple PHP implementations so tests don't need per-function mocks
// for calls that have no side-effects.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Convert a value to a non-negative integer.
	 *
	 * @param mixed $maybeint Value to convert.
	 * @return int
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitize a string by stripping tags and trimming whitespace.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( wp_strip_all_tags( (string) $str ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- polyfill for test environment
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip all HTML tags from a string.
	 *
	 * @param string $text String to strip.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Merge user-defined arguments with defaults.
	 *
	 * @param string|array|object $args     Value to merge with $defaults.
	 * @param array               $defaults Optional. Default values. Default empty string.
	 * @return array Merged arguments.
	 */
	function wp_parse_args( $args, $defaults = '' ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args = $args;
		} else {
			parse_str( (string) $args, $parsed_args );
		}
		if ( is_array( $defaults ) ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Escape a string for use in an HTML attribute.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Return the current time in the given format.
	 *
	 * @param string $type Format string passed to date().
	 * @return string
	 */
	function current_time( $type ) {
		return date( $type ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}
}

// ---------------------------------------------------------------------------
// Load plugin source files under test.
// ---------------------------------------------------------------------------

require_once dirname( __DIR__ ) . '/includes/icon-helpers.php';
require_once dirname( __DIR__ ) . '/includes/class-beruang-db.php';
