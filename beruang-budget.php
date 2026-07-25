<?php
/**
 * Plugin Name: Beruang Budget
 * Description: Track money, transactions, and budgets.
 * Plugin URI: https://pandaplugin.com/beruang/
 * Version: 0.6.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: David Chandra Purnama
 * Author URI: https://turtlepod.xyz
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: beruang-budget
 * Domain Path: /languages/
 *
 * @author David Chandra Purnama <turtlepod.xyz@gmail.com>
 * @copyright Copyright (c) 2026, David Chandra Purnama
 * @package Beruang
 */

namespace BeruangBudget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BERUANG_BUDGET_VERSION', '0.6.0' );
define( 'BERUANG_BUDGET_PLUGIN_FILE', __FILE__ );
define( 'BERUANG_BUDGET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BERUANG_BUDGET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'BERUANG_BUDGET_ADMIN_SLUG', 'beruang' );
define( 'BERUANG_BUDGET_ADMIN_CAPABILITY', 'manage_options' );

require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/core.php';

if ( defined( 'WP_CLI' ) && \WP_CLI ) {
	require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/seed.php';
	require_once BERUANG_BUDGET_PLUGIN_DIR . 'includes/class-beruang-cli.php';
	\WP_CLI::add_command( 'beruang', CLI::class );
}
