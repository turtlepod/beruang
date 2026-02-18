<?php
/**
 * Plugin Name:       Mowi
 * Description:       Track money, transactions, and budgets. Per-user data with shortcodes and admin pages.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Author:            Mowi
 * Text Domain:       mowi
 * Domain Path:       /languages
 *
 * @package Mowi
 */

namespace Mowi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOWI_VERSION', '1.0.0' );
define( 'MOWI_PLUGIN_FILE', __FILE__ );
define( 'MOWI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOWI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MOWI_PLUGIN_DIR . 'includes/core.php';

if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once MOWI_PLUGIN_DIR . 'includes/class-mowi-cli.php';
	\WP_CLI::add_command( 'mowi', CLI::class );
}
