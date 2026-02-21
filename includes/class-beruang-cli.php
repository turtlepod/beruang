<?php
/**
 * WP-CLI commands for Beruang.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CLI
 */
class CLI {

	/**
	 * Transaction operations.
	 *
	 * ## OPTIONS
	 *
	 * <subcommand>
	 * : Subcommand: list
	 *
	 * [--user_id=<id>]
	 * : Filter by user ID.
	 *
	 * [--year=<year>]
	 * : Filter by year.
	 *
	 * [--month=<month>]
	 * : Filter by month (1-12).
	 *
	 * [--type=<type>]
	 * : expense or income.
	 *
	 * [--format=<format>]
	 * : table, json, csv.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields.
	 *
	 * [--per_page=<n>]
	 * : Number of items per page.
	 *
	 * [--page=<n>]
	 * : Page number.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function transaction( $args, $assoc_args ) {
		$sub = isset( $args[0] ) ? $args[0] : '';
		if ( 'list' === $sub ) {
			$this->transaction_list( $assoc_args );
			return;
		}
		\WP_CLI::error( 'Usage: wp beruang transaction list [--user_id=] [--year=] [--month=] [--type=] [--format=] [--fields=] [--per_page=] [--page=]' );
	}

	/**
	 * List transactions for a user.
	 *
	 * @param array $assoc_args Associative args from CLI.
	 */
	private function transaction_list( $assoc_args ) {
		$user_id = isset( $assoc_args['user_id'] ) ? absint( $assoc_args['user_id'] ) : 0;
		if ( ! $user_id ) {
			\WP_CLI::error( '--user_id is required.' );
		}
		$year  = isset( $assoc_args['year'] ) ? absint( $assoc_args['year'] ) : (int) gmdate( 'Y' );
		$month = isset( $assoc_args['month'] ) ? absint( $assoc_args['month'] ) : 0;
		$type  = isset( $assoc_args['type'] ) ? $assoc_args['type'] : '';
		if ( $type && ! in_array( $type, array( 'expense', 'income' ), true ) ) {
			\WP_CLI::error( '--type must be expense or income.' );
		}
		$per_page = isset( $assoc_args['per_page'] ) ? absint( $assoc_args['per_page'] ) : 20;
		$page     = isset( $assoc_args['page'] ) ? max( 1, absint( $assoc_args['page'] ) ) : 1;
		$params   = array(
			'year'     => $year,
			'month'    => $month,
			'type'     => $type,
			'per_page' => $per_page,
			'page'     => $page,
		);
		$result   = DB::get_transactions( $user_id, $params );
		$format   = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$fields   = isset( $assoc_args['fields'] ) ? $assoc_args['fields'] : 'id,user_id,date,time,description,category_id,amount,type';
		\WP_CLI\Utils\format_items( $format, $result['items'], explode( ',', $fields ) );
	}

	/**
	 * Category operations.
	 *
	 * ## OPTIONS
	 *
	 * <subcommand>
	 * : Subcommand: list
	 *
	 * [--user_id=<id>]
	 * : Filter by user ID.
	 *
	 * [--format=<format>]
	 * : table, json, csv.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function category( $args, $assoc_args ) {
		$sub = isset( $args[0] ) ? $args[0] : '';
		if ( 'list' === $sub ) {
			$this->category_list( $assoc_args );
			return;
		}
		\WP_CLI::error( 'Usage: wp beruang category list [--user_id=] [--format=] [--fields=]' );
	}

	/**
	 * List categories for a user.
	 *
	 * @param array $assoc_args Associative args from CLI.
	 */
	private function category_list( $assoc_args ) {
		$user_id = isset( $assoc_args['user_id'] ) ? absint( $assoc_args['user_id'] ) : 0;
		if ( ! $user_id ) {
			\WP_CLI::error( '--user_id is required.' );
		}
		$items  = DB::get_categories_flat( $user_id, true );
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$fields = isset( $assoc_args['fields'] ) ? $assoc_args['fields'] : 'id,user_id,name,parent_id,sort_order,depth';
		\WP_CLI\Utils\format_items( $format, $items, explode( ',', $fields ) );
	}

	/**
	 * Budget operations.
	 *
	 * ## OPTIONS
	 *
	 * <subcommand>
	 * : Subcommand: list
	 *
	 * [--user_id=<id>]
	 * : Filter by user ID.
	 *
	 * [--format=<format>]
	 * : table, json, csv.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function budget( $args, $assoc_args ) {
		$sub = isset( $args[0] ) ? $args[0] : '';
		if ( 'list' === $sub ) {
			$this->budget_list( $assoc_args );
			return;
		}
		\WP_CLI::error( 'Usage: wp beruang budget list [--user_id=] [--format=] [--fields=]' );
	}

	/**
	 * List budgets for a user.
	 *
	 * @param array $assoc_args Associative args from CLI.
	 */
	private function budget_list( $assoc_args ) {
		$user_id = isset( $assoc_args['user_id'] ) ? absint( $assoc_args['user_id'] ) : 0;
		if ( ! $user_id ) {
			\WP_CLI::error( '--user_id is required.' );
		}
		$items  = DB::get_budgets( $user_id );
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$fields = isset( $assoc_args['fields'] ) ? $assoc_args['fields'] : 'id,user_id,name,target_amount,type,category_ids';
		\WP_CLI\Utils\format_items( $format, $items, explode( ',', $fields ) );
	}

	/**
	 * Budget-category relation operations.
	 *
	 * ## OPTIONS
	 *
	 * <subcommand>
	 * : Subcommand: list
	 *
	 * [--budget_id=<id>]
	 * : Filter by budget ID.
	 *
	 * [--category_id=<id>]
	 * : Filter by category ID.
	 *
	 * [--format=<format>]
	 * : table, json, csv.
	 *
	 * [--fields=<fields>]
	 * : budget_id, category_id.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function budget_category( $args, $assoc_args ) {
		$sub = isset( $args[0] ) ? $args[0] : '';
		if ( 'list' === $sub ) {
			$this->budget_category_list( $assoc_args );
			return;
		}
		\WP_CLI::error( 'Usage: wp beruang budget-category list [--budget_id=] [--category_id=] [--format=] [--fields=]' );
	}

	/**
	 * List budget-category links.
	 *
	 * @param array $assoc_args Associative args from CLI.
	 */
	private function budget_category_list( $assoc_args ) {
		global $wpdb;
		$table  = DB::table_budget_category();
		$where  = array( '1=1' );
		$values = array();
		if ( ! empty( $assoc_args['budget_id'] ) ) {
			$where[]  = 'budget_id = %d';
			$values[] = absint( $assoc_args['budget_id'] );
		}
		if ( ! empty( $assoc_args['category_id'] ) ) {
			$where[]  = 'category_id = %d';
			$values[] = absint( $assoc_args['category_id'] );
		}
		$sql = "SELECT budget_id, category_id FROM $table WHERE " . implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table from API, no user input in SQL.
		$items  = $values ? $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		$items  = is_array( $items ) ? $items : array();
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$fields = isset( $assoc_args['fields'] ) ? $assoc_args['fields'] : 'budget_id,category_id';
		\WP_CLI\Utils\format_items( $format, $items, explode( ',', $fields ) );
	}
}
