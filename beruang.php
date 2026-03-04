<?php
/**
 * Plugin Name: Beruang Budget
 * Description: Track money, transactions, and budgets.
 * Plugin URI: https://beruang.web.id/
 * Version: 0.2.1-beta
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: David Chandra Purnama
 * Author URI: https://turtlepod.xyz/
 * License: GPLv2 or later
 * Text Domain: beruang
 * Domain Path: /languages/
 *
 * @author David Chandra Purnama <turtlepod.xyz@gmail.com>
 * @copyright Copyright (c) 2026, David Chandra Purnama
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BERUANG_VERSION', '0.2.1-beta' );
define( 'BERUANG_PLUGIN_FILE', __FILE__ );
define( 'BERUANG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BERUANG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BERUANG_PLUGIN_DIR . 'includes/core.php';

if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once BERUANG_PLUGIN_DIR . 'includes/class-beruang-cli.php';
	\WP_CLI::add_command( 'beruang', CLI::class );
}
