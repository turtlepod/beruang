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
require_once BERUANG_PLUGIN_DIR . 'includes/ajax.php';
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
 * Enqueue frontend scripts and styles when shortcodes are present.
 */
function enqueue_front_scripts() {
	$post = get_post( get_queried_object_id() );

	if ( ! $post || ( ! has_shortcode( $post->post_content ?? '', 'beruang-form' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-list' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-graph' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-budget' ) ) ) {
		return;
	}

	wp_enqueue_style(
		'beruang-front',
		BERUANG_PLUGIN_URL . 'assets/css/beruang-front.css',
		array(),
		filemtime( BERUANG_PLUGIN_DIR . 'assets/css/beruang-front.css' )
	);
	$deps = array( 'jquery', 'wp-util' );
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
	wp_enqueue_script(
		'beruang-front',
		BERUANG_PLUGIN_URL . 'assets/js/beruang-front.js',
		$deps,
		BERUANG_VERSION,
		true
	);
	add_action( 'wp_footer', __NAMESPACE__ . '\print_front_templates', 5 );
	wp_localize_script(
		'beruang-front',
		'beruangData',
		array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'beruang_ajax' ),
			'currency'      => get_option( 'beruang_currency', 'IDR' ),
			'date_format'   => get_option( 'date_format', 'F j, Y' ),
			'locale'        => str_replace( '_', '-', get_locale() ),
			'decimal_sep'   => get_option( 'beruang_decimal_sep', ',' ),
			'thousands_sep' => get_option( 'beruang_thousands_sep', '.' ),
			'i18n'          => array(
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
		)
	);
}

/**
 * Output wp.template script blocks in footer.
 */
function print_front_templates() {
	$post = get_post( get_queried_object_id() );
	if ( ! $post || ( ! has_shortcode( $post->post_content ?? '', 'beruang-form' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-list' ) && ! has_shortcode( $post->post_content ?? '', 'beruang-budget' ) ) ) {
		return;
	}
	include BERUANG_PLUGIN_DIR . 'includes/templates-js.php';
}
