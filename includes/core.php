<?php
/**
 * Core bootstrap for Beruang plugin.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BERUANG_PLUGIN_DIR . 'includes/class-beruang-db.php';
require_once BERUANG_PLUGIN_DIR . 'includes/icon-helpers.php';
require_once BERUANG_PLUGIN_DIR . 'includes/seed.php';
require_once BERUANG_PLUGIN_DIR . 'includes/admin.php';
require_once BERUANG_PLUGIN_DIR . 'includes/rest.php';
require_once BERUANG_PLUGIN_DIR . 'includes/manifest.php';
require_once BERUANG_PLUGIN_DIR . 'includes/shortcodes.php';

register_activation_hook( BERUANG_PLUGIN_FILE, __NAMESPACE__ . '\on_activation' );
register_deactivation_hook( BERUANG_PLUGIN_FILE, 'flush_rewrite_rules' );

// Bootstrap.
add_action( 'plugins_loaded', __NAMESPACE__ . '\on_plugins_loaded' );

/**
 * Fires on plugin activation: install DB tables and flush rewrite rules for manifest.
 */
function on_activation() {
	DB::install();
	manifest_register_rewrite();
	flush_rewrite_rules();
}

/**
 * Fires on plugins_loaded: load text domain, register shortcodes, and hook actions.
 */
function on_plugins_loaded() {
	load_plugin_textdomain( 'beruang', false, dirname( plugin_basename( BERUANG_PLUGIN_FILE ) ) . '/languages' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_front_scripts' );
	manifest_setup();
}

/**
 * Whether built assets exist in dist/.
 */
function beruang_dist_exists() {
	return is_dir( BERUANG_PLUGIN_DIR . 'dist' );
}

/**
 * Enqueue frontend scripts and styles when shortcodes are present.
 */
function enqueue_front_scripts() {
	$post = get_post( get_queried_object_id() );

	if ( ! $post || ( ! has_shortcode( $post->post_content ?? '', 'beruang-form' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-list' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-graph' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-budget' ) ) ) {
		return;
	}

	if ( ! beruang_dist_exists() ) {
		return;
	}

	$front_css_dist   = BERUANG_PLUGIN_DIR . 'dist/css/front-style.css';
	$front_css_asset  = BERUANG_PLUGIN_DIR . 'dist/css/front-style.asset.php';
	$front_style_deps = array();
	$front_style_ver  = BERUANG_VERSION;
	if ( file_exists( $front_css_dist ) ) {
		if ( file_exists( $front_css_asset ) ) {
			$front_style_asset = include $front_css_asset;
			$front_style_deps  = $front_style_asset['dependencies'] ?? array();
			$front_style_ver   = $front_style_asset['version'] ?? $front_style_ver;
		}
		$front_css_url = BERUANG_PLUGIN_URL . 'dist/css/front-style.css';
		wp_enqueue_style(
			'beruang-front',
			$front_css_url,
			$front_style_deps,
			$front_style_ver
		);
	}
	$deps = array();
	if ( has_shortcode( $post->post_content ?? '', 'beruang-graph' ) ) {
		$chart_asset = BERUANG_PLUGIN_DIR . 'assets/js/chart.umd.min.js';
		wp_enqueue_script(
			'chartjs',
			BERUANG_PLUGIN_URL . 'assets/js/chart.umd.min.js',
			array(),
			file_exists( $chart_asset ) ? (string) filemtime( $chart_asset ) : '4.4.1',
			true
		);
		$deps[] = 'chartjs';
	}
	$front_js_dist  = BERUANG_PLUGIN_DIR . 'dist/js/front.js';
	$front_js_asset = BERUANG_PLUGIN_DIR . 'dist/js/front.asset.php';
	$front_js_deps  = $deps;
	$front_js_ver   = BERUANG_VERSION;
	if ( file_exists( $front_js_dist ) ) {
		if ( file_exists( $front_js_asset ) ) {
			$front_js_asset_data = include $front_js_asset;
			$front_js_deps       = array_merge( $front_js_asset_data['dependencies'] ?? array(), $deps );
			$front_js_ver        = $front_js_asset_data['version'] ?? $front_js_ver;
		}
		$front_js_url = BERUANG_PLUGIN_URL . 'dist/js/front.js';
		wp_enqueue_script(
			'beruang-front',
			$front_js_url,
			$front_js_deps,
			$front_js_ver,
			true
		);
		add_action( 'wp_footer', __NAMESPACE__ . '\print_front_templates', 5 );
		wp_localize_script(
			'beruang-front',
			'beruangData',
			array(
				'rest_url'       => get_rest_url( null, 'beruang/v1' ),
				'rest_nonce'     => wp_create_nonce( 'wp_rest' ),
				'currency'       => get_option( 'beruang_currency', 'IDR' ),
				'date_format'    => get_option( 'date_format', 'F j, Y' ),
				'locale'         => str_replace( '_', '-', get_locale() ),
				'decimal_sep'    => get_option( 'beruang_decimal_sep', ',' ),
				'thousands_sep'  => get_option( 'beruang_thousands_sep', '.' ),
				'decimal_places' => (int) get_option( 'beruang_decimal_places', 2 ),
				'i18n'           => array(
					'uncategorized'              => __( 'Uncategorized', 'beruang' ),
					'expense'                    => __( 'Expense', 'beruang' ),
					'income'                     => __( 'Income', 'beruang' ),
					'saved'                      => __( 'Saved.', 'beruang' ),
					'error'                      => __( 'Something went wrong.', 'beruang' ),
					'filter'                     => __( 'Filter', 'beruang' ),
					'search'                     => __( 'Search', 'beruang' ),
					'monthly'                    => __( 'Monthly', 'beruang' ),
					'yearly'                     => __( 'Yearly', 'beruang' ),
					'add_budget'                 => __( 'Add budget', 'beruang' ),
					'budget_name'                => __( 'Budget name', 'beruang' ),
					'target'                     => __( 'Target', 'beruang' ),
					'categories'                 => __( 'Categories', 'beruang' ),
					'loading'                    => __( 'Loading…', 'beruang' ),
					'no_transactions'            => __( 'No transactions.', 'beruang' ),
					'no_budgets'                 => __( 'No budgets.', 'beruang' ),
					'no_data'                    => __( 'No data', 'beruang' ),
					'confirm_delete'             => __( 'Delete this budget?', 'beruang' ),
					'delete'                     => __( 'Delete', 'beruang' ),
					'edit'                       => __( 'Edit', 'beruang' ),
					'manage_categories'          => __( 'Manage categories', 'beruang' ),
					'add_category'               => __( 'Add category', 'beruang' ),
					'update_category'            => __( 'Update category', 'beruang' ),
					'confirm_delete_category'    => __( 'Delete this category?', 'beruang' ),
					'confirm_delete_transaction' => __( 'Delete this transaction?', 'beruang' ),
					'no_categories'              => __( 'No categories yet.', 'beruang' ),
				),
				'edit_icon'      => beruang_get_icon( 'edit' ),
				'delete_icon'    => beruang_get_icon( 'trash' ),
			)
		);
	}
}

/**
 * Output Beruang JS template script blocks in footer.
 */
function print_front_templates() {
	$post = get_post( get_queried_object_id() );
	if ( ! $post || ( ! has_shortcode( $post->post_content ?? '', 'beruang-form' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-list' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-budget' ) ) ) {
		return;
	}
	include BERUANG_PLUGIN_DIR . 'includes/templates-js.php';
}
