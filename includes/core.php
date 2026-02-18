<?php
/**
 * Core bootstrap for Mowi plugin.
 *
 * @package Mowi
 */

namespace Mowi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MOWI_PLUGIN_DIR . 'includes/class-mowi-db.php';
require_once MOWI_PLUGIN_DIR . 'includes/icon-helpers.php';
require_once MOWI_PLUGIN_DIR . 'includes/admin.php';
require_once MOWI_PLUGIN_DIR . 'includes/ajax.php';
require_once MOWI_PLUGIN_DIR . 'includes/shortcodes.php';

register_activation_hook( MOWI_PLUGIN_FILE, array( DB::class, 'install' ) );

// Bootstrap.
add_action( 'plugins_loaded', __NAMESPACE__ . '\on_plugins_loaded' );

/**
 * Fires on plugins_loaded: load text domain, register shortcodes, and hook actions.
 */
function on_plugins_loaded() {
	load_plugin_textdomain( 'mowi', false, dirname( plugin_basename( MOWI_PLUGIN_FILE ) ) . '/languages' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_front_scripts' );
}

/**
 * Enqueue frontend scripts and styles when shortcodes are present.
 */
function enqueue_front_scripts() {
	$post = get_post( get_queried_object_id() );

	if ( ! $post || ! has_shortcode( $post->post_content ?? '', 'mowi-form' ) && ! has_shortcode( $post->post_content ?? '', 'mowi-list' ) && ! has_shortcode( $post->post_content ?? '', 'mowi-graph' ) && ! has_shortcode( $post->post_content ?? '', 'mowi-budget' ) ) {
		return;
	}

	wp_enqueue_style(
		'mowi-front',
		MOWI_PLUGIN_URL . 'assets/css/mowi-front.css',
		array(),
		MOWI_VERSION
	);
	$deps = array( 'jquery' );
	if ( has_shortcode( $post->post_content ?? '', 'mowi-graph' ) ) {
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			array(),
			'4.4.1',
			true
		);
		$deps[] = 'chartjs';
	}
	wp_enqueue_script(
		'mowi-front',
		MOWI_PLUGIN_URL . 'assets/js/mowi-front.js',
		$deps,
		MOWI_VERSION,
		true
	);
	wp_localize_script( 'mowi-front', 'mowiData', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'mowi_ajax' ),
		'currency' => get_option( 'mowi_currency', 'IDR' ),
		'decimal_sep' => get_option( 'mowi_decimal_sep', ',' ),
		'thousands_sep' => get_option( 'mowi_thousands_sep', '.' ),
		'i18n' => array(
			'uncategorized' => __( 'Uncategorized', 'mowi' ),
			'expense' => __( 'Expense', 'mowi' ),
			'income' => __( 'Income', 'mowi' ),
			'saved' => __( 'Saved.', 'mowi' ),
			'error' => __( 'Something went wrong.', 'mowi' ),
			'filter' => __( 'Filter', 'mowi' ),
			'search' => __( 'Search', 'mowi' ),
			'monthly' => __( 'Monthly', 'mowi' ),
			'yearly' => __( 'Yearly', 'mowi' ),
			'add_budget' => __( 'Add budget', 'mowi' ),
			'budget_name' => __( 'Budget name', 'mowi' ),
			'target' => __( 'Target', 'mowi' ),
			'categories' => __( 'Categories', 'mowi' ),
			'loading' => __( 'Loading…', 'mowi' ),
			'no_transactions' => __( 'No transactions.', 'mowi' ),
			'no_budgets' => __( 'No budgets.', 'mowi' ),
			'no_data' => __( 'No data', 'mowi' ),
			'confirm_delete' => __( 'Delete this budget?', 'mowi' ),
			'delete' => __( 'Delete', 'mowi' ),
			'edit' => __( 'Edit', 'mowi' ),
			'manage_categories' => __( 'Manage categories', 'mowi' ),
			'add_category' => __( 'Add category', 'mowi' ),
			'update_category' => __( 'Update category', 'mowi' ),
			'confirm_delete_category' => __( 'Delete this category?', 'mowi' ),
			'no_categories' => __( 'No categories yet.', 'mowi' ),
		),
	) );
}
