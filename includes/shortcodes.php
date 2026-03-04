<?php
/**
 * Shortcodes for Beruang.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcodes.
 */
function shortcodes_setup() {
	add_shortcode( 'beruang-form', __NAMESPACE__ . '\shortcode_render_form' );
	add_shortcode( 'beruang-list', __NAMESPACE__ . '\shortcode_render_list' );
	add_shortcode( 'beruang-graph', __NAMESPACE__ . '\shortcode_render_graph' );
	add_shortcode( 'beruang-budget', __NAMESPACE__ . '\shortcode_render_budget' );
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
	$path = BERUANG_PLUGIN_DIR . 'templates/' . $name;
	if ( ! file_exists( $path ) ) {
		return;
	}
	foreach ( $args as $key => $val ) {
		${$key} = $val;
	}
	include $path;
}

/**
 * [beruang-form]
 *
 * @param array $atts Shortcode attributes. Unused but required by shortcode API.
 * @return string
 */
function shortcode_render_form( $atts ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! is_user_logged_in() ) {
		return '<p class="beruang-login-required">' . esc_html__( 'Please log in to add transactions.', 'beruang' ) . '</p>';
	}
	ob_start();
	shortcode_load_template(
		'form.php',
		array(
			'today'       => current_time( 'Y-m-d' ),
			'time'        => current_time( 'H:i' ),
			'currency'    => get_option( 'beruang_currency', 'IDR' ),
			'categories'  => DB::get_categories_flat( get_current_user_id(), true ),
			'amount_step' => shortcode_amount_step(),
		)
	);
	return ob_get_clean();
}

/**
 * [beruang-list]
 *
 * @param array $atts Shortcode attributes. Unused but required by shortcode API.
 * @return string
 */
function shortcode_render_list( $atts ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! is_user_logged_in() ) {
		return '<p class="beruang-login-required">' . esc_html__( 'Please log in to view transactions.', 'beruang' ) . '</p>';
	}
	ob_start();
	shortcode_load_template(
		'list.php',
		array(
			'year'        => (int) current_time( 'Y' ),
			'categories'  => DB::get_categories_flat( get_current_user_id(), true ),
			'amount_step' => shortcode_amount_step(),
		)
	);
	return ob_get_clean();
}

/**
 * [beruang-graph]
 *
 * @param array $atts Shortcode attributes. Unused but required by shortcode API.
 * @return string
 */
function shortcode_render_graph( $atts ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! is_user_logged_in() ) {
		return '<p class="beruang-login-required">' . esc_html__( 'Please log in to view graphs.', 'beruang' ) . '</p>';
	}
	ob_start();
	shortcode_load_template(
		'graph.php',
		array(
			'year' => (int) current_time( 'Y' ),
		)
	);
	return ob_get_clean();
}

/**
 * [beruang-budget]
 *
 * @param array $atts Shortcode attributes. Unused but required by shortcode API.
 * @return string
 */
function shortcode_render_budget( $atts ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! is_user_logged_in() ) {
		return '<p class="beruang-login-required">' . esc_html__( 'Please log in to view budgets.', 'beruang' ) . '</p>';
	}
	ob_start();
	shortcode_load_template(
		'budget.php',
		array(
			'currency'    => get_option( 'beruang_currency', 'IDR' ),
			'categories'  => DB::get_categories_flat( get_current_user_id(), true ),
			'year'        => (int) current_time( 'Y' ),
			'month'       => (int) current_time( 'n' ),
			'amount_step' => shortcode_amount_step(),
		)
	);
	return ob_get_clean();
}

/**
 * Get HTML step attribute for amount inputs based on decimal places setting.
 *
 * @return string
 */
function shortcode_amount_step() {
	$places = (int) get_option( 'beruang_decimal_places', 2 );
	return 0 === $places ? '1' : '0.' . str_repeat( '0', $places - 1 ) . '1';
}

/**
 * Format amount for display in amount input value (respects decimal places setting).
 *
 * @param float|string $amount Amount from DB.
 * @return string
 */
function shortcode_format_amount_input_value( $amount ) {
	$places = (int) get_option( 'beruang_decimal_places', 2 );
	$num    = (float) $amount;
	return 0 === $places ? (string) (int) round( $num ) : number_format( $num, $places, '.', '' );
}

/**
 * Format amount for display.
 *
 * @param float  $amount   Amount value.
 * @param string $currency Optional.
 * @return string
 */
function shortcode_format_amount( $amount, $currency = '' ) {
	$dec    = get_option( 'beruang_decimal_sep', ',' );
	$thou   = get_option( 'beruang_thousands_sep', '.' );
	$places = (int) get_option( 'beruang_decimal_places', 2 );
	if ( '' === $currency ) {
		$currency = get_option( 'beruang_currency', 'IDR' );
	}
	$formatted = number_format( (float) $amount, $places, $dec, $thou );
	return $formatted . ' ' . $currency;
}
