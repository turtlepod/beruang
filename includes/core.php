<?php
/**
 * Core bootstrap for Beruang plugin.
 *
 * @package Beruang
 */

namespace BeruangBudget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-db.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-import.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/icon-helpers.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-transactions-list-table.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-categories-list-table.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-budgets-list-table.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-wallets-list-table.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/admin.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/rest.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/manifest.php';
require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/shortcodes.php';

register_activation_hook( BERUANG_BUDGET_PLUGIN_FILE, __NAMESPACE__ . '\\on_activation' );
register_deactivation_hook( BERUANG_BUDGET_PLUGIN_FILE, 'flush_rewrite_rules' );

// Bootstrap.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\on_plugins_loaded' );

// Use the logged-in user's locale for frontend requests.
add_filter( 'determine_locale', __NAMESPACE__ . '\\use_logged_in_user_locale_on_frontend', 20 );

/**
 * Fires on plugin activation: install DB tables and flush rewrite rules for manifest.
 */
function on_activation() {
	DB::install();
	manifest_register_rewrite();
	flush_rewrite_rules();
}

/**
 * Use the logged-in user's locale for frontend requests.
 *
 * WordPress applies user locale automatically in wp-admin, but not on
 * regular frontend requests. This aligns frontend locale with the profile
 * language setting.
 *
 * @param string $locale The currently determined locale.
 * @return string
 */
function use_logged_in_user_locale_on_frontend( $locale ) {
	if ( is_admin() || ! is_user_logged_in() ) {
		return $locale;
	}

	$user_locale = get_user_locale();
	return ! empty( $user_locale ) ? $user_locale : $locale;
}

/**
 * Fires on plugins_loaded: load text domain, register shortcodes, and hook actions.
 */
function on_plugins_loaded() {
	DB::maybe_upgrade();
	load_plugin_textdomain( 'beruang-budget', false, dirname( plugin_basename( BERUANG_BUDGET_PLUGIN_FILE ) ) . '/languages' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_front_scripts' );
	manifest_setup();

	// Bundled themes have been decoupled into a separate repo:
	// https://github.com/turtlepod/BeruangTheme
	// Install as a standalone WordPress theme.
}

/**
 * Whether built assets exist in dist/.
 */
function beruang_dist_exists() {
	return is_dir( BERUANG_BUDGET_PLUGIN_DIR . 'dist' );
}

/**
 * Enqueue frontend scripts and styles when shortcodes are present.
 */
function enqueue_front_scripts() {
	if ( ! beruang_dist_exists() ) {
		return;
	}

	$front_css_dist   = BERUANG_BUDGET_PLUGIN_DIR . 'dist/css/front-style.css';
	$front_css_asset  = BERUANG_BUDGET_PLUGIN_DIR . 'dist/css/front-style.asset.php';
	$front_style_deps = array();
	$front_style_ver  = BERUANG_BUDGET_VERSION;
	if ( file_exists( $front_css_dist ) ) {
		if ( file_exists( $front_css_asset ) ) {
			$front_style_asset = include $front_css_asset;
			$front_style_deps  = $front_style_asset['dependencies'] ?? array();
			$front_style_ver   = $front_style_asset['version'] ?? $front_style_ver;
		}
		$front_css_url = BERUANG_BUDGET_PLUGIN_URL . 'dist/css/front-style.css';
		wp_enqueue_style(
			'beruang-front',
			$front_css_url,
			$front_style_deps,
			$front_style_ver
		);
	}

	$chart_asset   = BERUANG_BUDGET_PLUGIN_DIR . 'assets/js/chart.umd.min.js';
	$chart_version = file_exists( $chart_asset ) ? (string) filemtime( $chart_asset ) : '4.4.1';
	wp_enqueue_script(
		'chartjs',
		BERUANG_BUDGET_PLUGIN_URL . 'assets/js/chart.umd.min.js',
		array(),
		$chart_version,
		true
	);

	$front_js_dist  = BERUANG_BUDGET_PLUGIN_DIR . 'dist/js/front.js';
	$front_js_asset = BERUANG_BUDGET_PLUGIN_DIR . 'dist/js/front.asset.php';
	$front_js_deps  = [ 'chartjs' ];
	$front_js_ver   = BERUANG_BUDGET_VERSION;
	if ( file_exists( $front_js_dist ) ) {
		if ( file_exists( $front_js_asset ) ) {
			$front_js_asset_data = include $front_js_asset;
			$front_js_deps       = array_merge( $front_js_asset_data['dependencies'] ?? array(), $front_js_deps );
			$front_js_ver        = $front_js_asset_data['version'] ?? $front_js_ver;
		}
		$front_js_url = BERUANG_BUDGET_PLUGIN_URL . 'dist/js/front.js';
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
				'currency'       => get_effective_currency(),
				'date_format'    => get_option( 'date_format', 'F j, Y' ),
				'locale'         => str_replace( '_', '-', get_locale() ),
				'decimal_sep'    => get_effective_decimal_sep(),
				'thousands_sep'  => get_effective_thousands_sep(),
				'decimal_places' => get_effective_decimal_places(),
				'i18n'           => array(
					'uncategorized'              => __( 'Uncategorized', 'beruang-budget' ),
					'no_wallet'                  => __( 'No Wallet', 'beruang-budget' ),
					'expense'                    => __( 'Expense', 'beruang-budget' ),
					'income'                     => __( 'Income', 'beruang-budget' ),
					'saved'                      => __( 'Saved.', 'beruang-budget' ),
					'error'                      => __( 'Something went wrong.', 'beruang-budget' ),
					'filter'                     => __( 'Filter', 'beruang-budget' ),
					'search'                     => __( 'Search', 'beruang-budget' ),
					'monthly'                    => __( 'Monthly', 'beruang-budget' ),
					'yearly'                     => __( 'Yearly', 'beruang-budget' ),
					'add_budget'                 => __( 'Add budget', 'beruang-budget' ),
					'budget_name'                => __( 'Budget name', 'beruang-budget' ),
					'target'                     => __( 'Target', 'beruang-budget' ),
					'categories'                 => __( 'Categories', 'beruang-budget' ),
					'loading'                    => __( 'Loading…', 'beruang-budget' ),
					'no_transactions'            => __( 'No transactions.', 'beruang-budget' ),
					'no_budgets'                 => __( 'No budgets.', 'beruang-budget' ),
					'no_data'                    => __( 'No data', 'beruang-budget' ),
					'confirm_delete'             => __( 'Delete this budget?', 'beruang-budget' ),
					'delete'                     => __( 'Delete', 'beruang-budget' ),
					'edit'                       => __( 'Edit', 'beruang-budget' ),
					'manage_categories'          => __( 'Manage categories', 'beruang-budget' ),
					'add_category'               => __( 'Add category', 'beruang-budget' ),
					'update_category'            => __( 'Update category', 'beruang-budget' ),
					'add_wallet'                 => __( 'Add wallet', 'beruang-budget' ),
					'update_wallet'              => __( 'Update wallet', 'beruang-budget' ),
					'confirm_delete_wallet'      => __( 'Delete this wallet?', 'beruang-budget' ),
					'no_wallets'                 => __( 'No wallets yet.', 'beruang-budget' ),
					'confirm_delete_category'    => __( 'Delete this category?', 'beruang-budget' ),
					'confirm_delete_transaction' => __( 'Delete this transaction?', 'beruang-budget' ),
					'no_categories'              => __( 'No categories yet.', 'beruang-budget' ),
					/* translators: 1: amount, 2: date (Y-m-d). */
					'wallet_baseline'            => __( 'Baseline: %1$s on %2$s', 'beruang-budget' ),
					/* translators: %s: current wallet amount. */
					'wallet_current'             => __( 'Current: %s', 'beruang-budget' ),
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
	include BERUANG_BUDGET_PLUGIN_DIR . 'includes/templates-js.php';
}
