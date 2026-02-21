<?php
/**
 * Plugin Name:       Beruang
 * Description:       Track money, transactions, and budgets. Per-user data with shortcodes and admin pages.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Author:            Beruang
 * Text Domain:       beruang
 * Domain Path:       /languages
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BERUANG_VERSION', '1.0.0' );
define( 'BERUANG_PLUGIN_FILE', __FILE__ );
define( 'BERUANG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BERUANG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BERUANG_PLUGIN_DIR . 'includes/core.php';

if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once BERUANG_PLUGIN_DIR . 'includes/class-beruang-cli.php';
	\WP_CLI::add_command( 'beruang', CLI::class );
}
