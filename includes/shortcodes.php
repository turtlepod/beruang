<?php
/**
 * Shortcodes for Mowi.
 *
 * @package Mowi
 */

namespace Mowi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcodes.
 */
function shortcodes_setup() {
	add_shortcode( 'mowi-form', __NAMESPACE__ . '\shortcode_render_form' );
	add_shortcode( 'mowi-list', __NAMESPACE__ . '\shortcode_render_list' );
	add_shortcode( 'mowi-graph', __NAMESPACE__ . '\shortcode_render_graph' );
	add_shortcode( 'mowi-budget', __NAMESPACE__ . '\shortcode_render_budget' );
}
add_action( 'init', __NAMESPACE__ . '\shortcodes_setup' );

/**
 * Load a shortcode template.
 *
 * @param string $name Template filename (e.g. 'form.php').
 * @param array  $args Variables to extract for the template.
 * @return void
 */
function shortcode_load_template( $name, $args = array() ) {
	$path = MOWI_PLUGIN_DIR . 'templates/' . $name;
	if ( ! file_exists( $path ) ) {
		return;
	}
	if ( ! empty( $args ) ) {
		extract( $args );
	}
	include $path;
}

/**
 * [mowi-form]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function shortcode_render_form( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p class="mowi-login-required">' . esc_html__( 'Please log in to add transactions.', 'mowi' ) . '</p>';
	}
	ob_start();
	shortcode_load_template( 'form.php', array(
		'today'      => current_time( 'Y-m-d' ),
		'time'       => current_time( 'H:i' ),
		'currency'   => get_option( 'mowi_currency', 'IDR' ),
		'categories' => DB::get_categories_flat( get_current_user_id(), true ),
	) );
	return ob_get_clean();
}

/**
 * [mowi-list]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function shortcode_render_list( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p class="mowi-login-required">' . esc_html__( 'Please log in to view transactions.', 'mowi' ) . '</p>';
	}
	ob_start();
	shortcode_load_template( 'list.php', array(
		'year'       => (int) current_time( 'Y' ),
		'month'      => (int) current_time( 'n' ),
		'categories' => DB::get_categories_flat( get_current_user_id(), true ),
	) );
	return ob_get_clean();
}

/**
 * [mowi-graph]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function shortcode_render_graph( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p class="mowi-login-required">' . esc_html__( 'Please log in to view graphs.', 'mowi' ) . '</p>';
	}
	ob_start();
	shortcode_load_template( 'graph.php', array(
		'year' => (int) current_time( 'Y' ),
	) );
	return ob_get_clean();
}

/**
 * [mowi-budget]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function shortcode_render_budget( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p class="mowi-login-required">' . esc_html__( 'Please log in to view budgets.', 'mowi' ) . '</p>';
	}
	ob_start();
	shortcode_load_template( 'budget.php', array(
		'currency'   => get_option( 'mowi_currency', 'IDR' ),
		'categories' => DB::get_categories_flat( get_current_user_id(), true ),
	) );
	return ob_get_clean();
}

/**
 * Format amount for display.
 *
 * @param float  $amount   Amount value.
 * @param string $currency Optional.
 * @return string
 */
function shortcode_format_amount( $amount, $currency = '' ) {
	$dec = get_option( 'mowi_decimal_sep', ',' );
	$thou = get_option( 'mowi_thousands_sep', '.' );
	if ( $currency === '' ) {
		$currency = get_option( 'mowi_currency', 'IDR' );
	}
	$formatted = number_format( (float) $amount, $dec === '.' ? 2 : 0, $dec, $thou );
	return $formatted . ' ' . $currency;
}
