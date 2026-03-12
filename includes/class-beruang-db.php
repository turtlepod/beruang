<?php
/**
 * Database schema and CRUD for Beruang.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DB
 */
class DB {

	const DB_VERSION        = 2;
	const OPTION_DB_VERSION = 'beruang_db_version';

	/**
	 * WordPress database abstraction.
	 *
	 * @var \wpdb
	 */
	private static $wpdb;

	/**
	 * Category table name.
	 *
	 * @return string
	 */
	public static function table_category() {
		return self::wpdb()->prefix . 'beruang_category';
	}

	/**
	 * Wallet table name.
	 *
	 * @return string
	 */
	public static function table_wallet() {
		return self::wpdb()->prefix . 'beruang_wallet';
	}

	/**
	 * Transaction table name.
	 *
	 * @return string
	 */
	public static function table_transaction() {
		return self::wpdb()->prefix . 'beruang_transaction';
	}

	/**
	 * Budget table name.
	 *
	 * @return string
	 */
	public static function table_budget() {
		return self::wpdb()->prefix . 'beruang_budget';
	}

	/**
	 * Budget-category junction table name.
	 *
	 * @return string
	 */
	public static function table_budget_category() {
		return self::wpdb()->prefix . 'beruang_budget_category';
	}

	/**
	 * wpdb instance.
	 *
	 * @return \wpdb
	 */
	private static function wpdb() {
		if ( null === self::$wpdb ) {
			global $wpdb;
			self::$wpdb = $wpdb;
		}
		return self::$wpdb;
	}

	/**
	 * Create or update tables.
	 */
	public static function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = self::wpdb()->get_charset_collate();
		$cat             = self::table_category();
		$wallet          = self::table_wallet();
		$tx              = self::table_transaction();
		$budget          = self::table_budget();
		$bc              = self::table_budget_category();

		$sql_cat = "CREATE TABLE $cat (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL DEFAULT '',
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY parent_id (parent_id)
		) $charset_collate;";

		$sql_wallet = "CREATE TABLE $wallet (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY user_id (user_id)
		) $charset_collate;";

		$sql_tx = "CREATE TABLE $tx (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			date date NOT NULL,
			time time DEFAULT NULL,
			description text,
			note longtext,
			wallet_id bigint(20) unsigned NOT NULL DEFAULT 0,
			category_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount decimal(14,2) NOT NULL DEFAULT 0.00,
			type varchar(20) NOT NULL DEFAULT 'expense',
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY date (date),
			KEY wallet_id (wallet_id),
			KEY category_id (category_id),
			KEY type (type)
		) $charset_collate;";

		$sql_budget = "CREATE TABLE $budget (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL DEFAULT '',
			target_amount decimal(14,2) NOT NULL DEFAULT 0.00,
			type varchar(20) NOT NULL DEFAULT 'monthly',
			PRIMARY KEY (id),
			KEY user_id (user_id)
		) $charset_collate;";

		$sql_bc = "CREATE TABLE $bc (
			budget_id bigint(20) unsigned NOT NULL,
			category_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY (budget_id,category_id)
		) $charset_collate;";

		dbDelta( $sql_cat );
		dbDelta( $sql_wallet );
		dbDelta( $sql_tx );
		dbDelta( $sql_budget );
		dbDelta( $sql_bc );
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}

	/**
	 * Get all wallets for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_wallets( $user_id ) {
		$table   = self::table_wallet();
		$user_id = absint( $user_id );

		$rows = self::wpdb()->get_results(
			self::wpdb()->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY name ASC",
				$user_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get a single wallet for a user.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Wallet ID.
	 * @return array|null
	 */
	public static function get_wallet_for_user( $user_id, $id ) {
		$table = self::table_wallet();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare(
				"SELECT * FROM $table WHERE id = %d AND user_id = %d",
				absint( $id ),
				absint( $user_id )
			),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Get a single wallet by ID (any user). For admin use.
	 *
	 * @param int $id Wallet ID.
	 * @return array|null
	 */
	public static function get_wallet_by_id( $id ) {
		$table = self::table_wallet();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Save a wallet (insert or update).
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Wallet fields.
	 * @param int   $id      Update existing if > 0.
	 * @return int|false Wallet ID or false.
	 */
	public static function save_wallet( $user_id, $data, $id = 0 ) {
		$table   = self::table_wallet();
		$user_id = absint( $user_id );
		$name    = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';

		if ( '' === $name ) {
			return false;
		}

		if ( $id > 0 ) {
			$wallet = self::get_wallet_for_user( $user_id, $id );
			if ( ! $wallet ) {
				return false;
			}
			self::wpdb()->update(
				$table,
				array( 'name' => $name ),
				array(
					'id'      => absint( $id ),
					'user_id' => $user_id,
				),
				array( '%s' ),
				array( '%d', '%d' )
			);
			return absint( $id );
		}

		self::wpdb()->insert(
			$table,
			array(
				'user_id' => $user_id,
				'name'    => $name,
			),
			array( '%d', '%s' )
		);

		$insert_id = self::wpdb()->insert_id;
		return $insert_id ? (int) $insert_id : false;
	}

	/**
	 * Delete wallet.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Wallet ID.
	 * @return bool
	 */
	public static function delete_wallet( $user_id, $id ) {
		$table  = self::table_wallet();
		$wallet = self::get_wallet_for_user( $user_id, $id );
		if ( ! $wallet ) {
			return false;
		}
		return (bool) self::wpdb()->delete(
			$table,
			array(
				'id'      => absint( $id ),
				'user_id' => absint( $user_id ),
			),
			array( '%d', '%d' )
		);
	}

	// --- Categories ---

	/**
	 * Get child categories for a parent.
	 *
	 * @param int $user_id   User ID.
	 * @param int $parent_id Parent category ID.
	 * @return array
	 */
	public static function get_categories( $user_id, $parent_id = 0 ) {
		$table     = self::table_category();
		$user_id   = absint( $user_id );
		$parent_id = absint( $parent_id );
		$results   = self::wpdb()->get_results(
			self::wpdb()->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND parent_id = %d ORDER BY sort_order ASC, name ASC",
				$user_id,
				$parent_id
			),
			ARRAY_A
		);
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get all categories for a user (flat or tree). For dropdown we need flat with depth/name.
	 *
	 * @param int  $user_id        User ID.
	 * @param bool $flat_with_depth If true, returns flat list with depth key (0 = root).
	 * @return array
	 */
	public static function get_categories_flat( $user_id, $flat_with_depth = true ) {
		$table   = self::table_category();
		$user_id = absint( $user_id );
		$all     = self::wpdb()->get_results(
			self::wpdb()->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY parent_id ASC, sort_order ASC, name ASC",
				$user_id
			),
			ARRAY_A
		);
		if ( ! $flat_with_depth || empty( $all ) ) {
			return is_array( $all ) ? $all : array();
		}
		$by_parent = array();
		foreach ( $all as $row ) {
			$by_parent[ (int) $row['parent_id'] ][] = $row;
		}
		foreach ( $by_parent as $pid => $children ) {
			usort(
				$by_parent[ $pid ],
				function ( $a, $b ) {
					return strcasecmp( $a['name'] ?? '', $b['name'] ?? '' );
				}
			);
		}
		$out = array();
		self::flatten_categories( 0, 0, $by_parent, $out );
		return $out;
	}

	/**
	 * Recursively flatten category tree.
	 *
	 * @param int   $parent_id  Parent category ID.
	 * @param int   $depth      Current depth.
	 * @param array $by_parent  Categories indexed by parent_id.
	 * @param array $out       Output array (by reference).
	 */
	private static function flatten_categories( $parent_id, $depth, $by_parent, &$out ) {
		if ( ! isset( $by_parent[ $parent_id ] ) ) {
			return;
		}
		foreach ( $by_parent[ $parent_id ] as $row ) {
			$row['depth'] = $depth;
			$out[]        = $row;
			self::flatten_categories( (int) $row['id'], $depth + 1, $by_parent, $out );
		}
	}

	/**
	 * Save a category (insert or update).
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Keys: name, parent_id, sort_order.
	 * @param int   $id      Update existing if > 0.
	 * @return int|false Insert id or false.
	 */
	public static function save_category( $user_id, $data, $id = 0 ) {
		$table      = self::table_category();
		$user_id    = absint( $user_id );
		$name       = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$parent_id  = isset( $data['parent_id'] ) ? absint( $data['parent_id'] ) : 0;
		$sort_order = isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0;
		if ( '' === $name ) {
			return false;
		}
		if ( $id ) {
			self::wpdb()->update(
				$table,
				array(
					'name'       => $name,
					'parent_id'  => $parent_id,
					'sort_order' => $sort_order,
				),
				array(
					'id'      => $id,
					'user_id' => $user_id,
				),
				array( '%s', '%d', '%d' ),
				array( '%d', '%d' )
			);
			return $id;
		}
		self::wpdb()->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'name'       => $name,
				'parent_id'  => $parent_id,
				'sort_order' => $sort_order,
			),
			array( '%d', '%s', '%d', '%d' )
		);
		$insert_id = self::wpdb()->insert_id;
		return $insert_id ? (int) $insert_id : false;
	}

	/**
	 * Delete a category.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Category ID.
	 * @return bool
	 */
	public static function delete_category( $user_id, $id ) {
		$table = self::table_category();
		return (bool) self::wpdb()->delete(
			$table,
			array(
				'id'      => absint( $id ),
				'user_id' => absint( $user_id ),
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Get a single category by ID (any user). For admin use.
	 *
	 * @param int $id Category ID.
	 * @return array|null
	 */
	public static function get_category_by_id( $id ) {
		$table = self::table_category();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Get a single category by ID only if it belongs to the user.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Category ID.
	 * @return array|null
	 */
	public static function get_category_for_user( $user_id, $id ) {
		$table = self::table_category();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d AND user_id = %d", absint( $id ), absint( $user_id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	// --- Transactions ---

	/**
	 * Get transactions with optional filters.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Keys: month, year, search, category_id, category_ids, wallet_id, type, page, per_page.
	 * @return array Keys: items, total.
	 */
	public static function get_transactions( $user_id, $args = array() ) {
		$table   = self::table_transaction();
		$user_id = absint( $user_id );
		$where   = array( 'user_id = %d' );
		$values  = array( $user_id );

		if ( ! empty( $args['year'] ) ) {
			$where[]  = 'YEAR(date) = %d';
			$values[] = absint( $args['year'] );
		}
		if ( ! empty( $args['month'] ) ) {
			$where[]  = 'MONTH(date) = %d';
			$values[] = absint( $args['month'] );
		}
		if ( isset( $args['category_id'] ) && '' !== $args['category_id'] && null !== $args['category_id'] ) {
			$where[]  = 'category_id = %d';
			$values[] = absint( $args['category_id'] );
		}
		if ( ! empty( $args['category_ids'] ) && is_array( $args['category_ids'] ) ) {
			$ids = array_values(
				array_unique(
					array_map( 'absint', $args['category_ids'] )
				)
			);
			if ( ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$where[]      = "category_id IN ($placeholders)";
				$values       = array_merge( $values, $ids );
			}
		}
		if ( isset( $args['wallet_id'] ) && '' !== $args['wallet_id'] && null !== $args['wallet_id'] ) {
			$where[]  = 'wallet_id = %d';
			$values[] = absint( $args['wallet_id'] );
		}
		if ( ! empty( $args['type'] ) && in_array( $args['type'], array( 'expense', 'income' ), true ) ) {
			$where[]  = 'type = %s';
			$values[] = $args['type'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'description LIKE %s';
			$values[] = '%' . self::wpdb()->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
		}

		$where_sql = implode( ' AND ', $where );
		$total     = (int) self::wpdb()->get_var(
			self::wpdb()->prepare(
				"SELECT COUNT(*) FROM $table WHERE $where_sql",
				$values
			)
		);

		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 50;
		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		$order = 'ORDER BY date DESC, time DESC, id DESC';
		$limit = self::wpdb()->prepare( 'LIMIT %d, %d', $offset, $per_page );
		$sql   = "SELECT * FROM $table WHERE $where_sql $order $limit";
		$items = self::wpdb()->get_results(
			self::wpdb()->prepare( $sql, $values ),
			ARRAY_A
		);

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Get user IDs that have at least one transaction. For admin filter dropdown.
	 *
	 * @return int[]
	 */
	public static function get_transaction_user_ids() {
		$table = self::table_transaction();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from API.
		$ids = self::wpdb()->get_col( "SELECT DISTINCT user_id FROM $table ORDER BY user_id ASC" );
		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Get user IDs that have at least one category. For admin filter dropdown.
	 *
	 * @return int[]
	 */
	public static function get_category_user_ids() {
		$table = self::table_category();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from API.
		$ids = self::wpdb()->get_col( "SELECT DISTINCT user_id FROM $table ORDER BY user_id ASC" );
		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Get user IDs that have at least one budget. For admin filter dropdown.
	 *
	 * @return int[]
	 */
	public static function get_budget_user_ids() {
		$table = self::table_budget();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from API.
		$ids = self::wpdb()->get_col( "SELECT DISTINCT user_id FROM $table ORDER BY user_id ASC" );
		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Get user IDs that have at least one wallet. For admin filter dropdown.
	 *
	 * @return int[]
	 */
	public static function get_wallet_user_ids() {
		$table = self::table_wallet();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from API.
		$ids = self::wpdb()->get_col( "SELECT DISTINCT user_id FROM $table ORDER BY user_id ASC" );
		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Get a single transaction by ID (any user). For admin use.
	 *
	 * @param int $id Transaction ID.
	 * @return array|null Row or null.
	 */
	public static function get_transaction_by_id( $id ) {
		$table = self::table_transaction();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Get a single transaction by ID only if it belongs to the user. For frontend use.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Transaction ID.
	 * @return array|null Row or null.
	 */
	public static function get_transaction_for_user( $user_id, $id ) {
		$table = self::table_transaction();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d AND user_id = %d", absint( $id ), absint( $user_id ) ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Get distinct description suggestions matching a search string.
	 *
	 * @param int    $user_id User ID.
	 * @param string $search  Search term (partial match).
	 * @param int    $limit   Max number of results (capped at 20).
	 * @return string[]
	 */
	public static function get_description_suggestions( $user_id, $search, $limit = 3 ) {
		$table   = self::table_transaction();
		$user_id = absint( $user_id );
		$limit   = min( absint( $limit ), 20 );
		$results = self::wpdb()->get_col(
			self::wpdb()->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from API.
				"SELECT DISTINCT description FROM $table WHERE user_id = %d AND description LIKE %s AND description != '' ORDER BY description ASC LIMIT %d",
				$user_id,
				'%' . self::wpdb()->esc_like( $search ) . '%',
				$limit
			)
		);
		return is_array( $results ) ? array_values( $results ) : array();
	}

	/**
	 * Update an existing transaction.
	 *
	 * @param int   $id   Transaction ID.
	 * @param array $data date, time, description, note, wallet_id, category_id, amount, type.
	 * @return bool
	 */
	public static function update_transaction( $id, $data ) {
		$table    = self::table_transaction();
		$id       = absint( $id );
		$existing = self::get_transaction_by_id( $id );
		$date     = isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = current_time( 'Y-m-d' );
		}
		$time = isset( $data['time'] ) ? sanitize_text_field( $data['time'] ) : null;
		if ( null !== $time && '' !== $time && ! preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $time ) ) {
			$time = null;
		}
		$description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$note        = array_key_exists( 'note', $data ) ? sanitize_textarea_field( $data['note'] ) : (string) ( $existing['note'] ?? '' );
		$wallet_id   = isset( $data['wallet_id'] ) ? absint( $data['wallet_id'] ) : 0;
		$category_id = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
		$amount      = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
		$type        = isset( $data['type'] ) && 'income' === $data['type'] ? 'income' : 'expense';
		if ( $wallet_id < 1 ) {
			$wallet_id = $existing ? (int) $existing['wallet_id'] : 0;
		}

		return (bool) self::wpdb()->update(
			$table,
			array(
				'date'        => $date,
				'time'        => $time,
				'description' => $description,
				'note'        => $note,
				'wallet_id'   => $wallet_id,
				'category_id' => $category_id,
				'amount'      => $amount,
				'type'        => $type,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a transaction (must belong to user).
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Transaction ID.
	 * @return bool
	 */
	public static function delete_transaction( $user_id, $id ) {
		$table = self::table_transaction();
		return (bool) self::wpdb()->delete(
			$table,
			array(
				'id'      => absint( $id ),
				'user_id' => absint( $user_id ),
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Insert a new transaction.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Keys: date, time, description, note, wallet_id, category_id, amount, type.
	 * @return int|false Insert ID or false.
	 */
	public static function insert_transaction( $user_id, $data ) {
		$table   = self::table_transaction();
		$user_id = absint( $user_id );
		$date    = isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = current_time( 'Y-m-d' );
		}
		$time = isset( $data['time'] ) ? sanitize_text_field( $data['time'] ) : null;
		if ( null !== $time && '' !== $time && ! preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $time ) ) {
			$time = null;
		}
		$description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$note        = isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '';
		$wallet_id   = isset( $data['wallet_id'] ) ? absint( $data['wallet_id'] ) : 0;
		$category_id = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
		$amount      = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
		$type        = isset( $data['type'] ) && 'income' === $data['type'] ? 'income' : 'expense';

		self::wpdb()->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'date'        => $date,
				'time'        => $time,
				'description' => $description,
				'note'        => $note,
				'wallet_id'   => $wallet_id,
				'category_id' => $category_id,
				'amount'      => $amount,
				'type'        => $type,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s' )
		);
		$insert_id = self::wpdb()->insert_id;
		return $insert_id ? (int) $insert_id : false;
	}

	/**
	 * Sum expense amount for given user, date range, and optional category ids.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $date_from    Start date Y-m-d.
	 * @param string $date_to      End date Y-m-d.
	 * @param int[]  $category_ids Category IDs to include, empty for all.
	 * @return float
	 */
	public static function sum_expenses( $user_id, $date_from, $date_to, $category_ids = array() ) {
		$table   = self::table_transaction();
		$user_id = absint( $user_id );
		$where   = "user_id = %d AND type = 'expense' AND date >= %s AND date <= %s";
		$values  = array( $user_id, $date_from, $date_to );
		if ( ! empty( $category_ids ) ) {
			$ids          = array_map( 'absint', $category_ids );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$where       .= " AND category_id IN ($placeholders)";
			$values       = array_merge( $values, $ids );
		}
		$sum = self::wpdb()->get_var( self::wpdb()->prepare( "SELECT COALESCE(SUM(amount),0) FROM $table WHERE $where", $values ) );
		return (float) $sum;
	}

	/**
	 * Delete all Beruang data for a user: transactions, budget-category links, budgets, wallets, categories.
	 *
	 * @param int $user_id User ID.
	 * @return array{ transactions: int, budgets: int, wallets: int, categories: int } Counts of deleted rows.
	 */
	public static function reset_user_data( $user_id ) {
		$table   = self::table_transaction();
		$user_id = absint( $user_id );
		$tx      = self::wpdb()->query( self::wpdb()->prepare( "DELETE FROM $table WHERE user_id = %d", $user_id ) );
		$budget  = self::table_budget();
		$bc      = self::table_budget_category();
		$ids     = self::wpdb()->get_col( self::wpdb()->prepare( "SELECT id FROM $budget WHERE user_id = %d", $user_id ) );
		$bc_del  = 0;
		if ( ! empty( $ids ) ) {
			$placeholders = implode( ',', array_map( 'absint', $ids ) );
			$bc_del       = self::wpdb()->query( "DELETE FROM $bc WHERE budget_id IN ($placeholders)" );
		}
		$bud_del  = self::wpdb()->query( self::wpdb()->prepare( "DELETE FROM $budget WHERE user_id = %d", $user_id ) );
		$wallet   = self::table_wallet();
		$wall_del = self::wpdb()->query( self::wpdb()->prepare( "DELETE FROM $wallet WHERE user_id = %d", $user_id ) );
		$cat      = self::table_category();
		$cat_del  = self::wpdb()->query( self::wpdb()->prepare( "DELETE FROM $cat WHERE user_id = %d", $user_id ) );
		return array(
			'transactions' => (int) $tx,
			'budgets'      => (int) $bud_del,
			'wallets'      => (int) $wall_del,
			'categories'   => (int) $cat_del,
		);
	}

	// --- Budgets ---

	/**
	 * Get all budgets for a user with category_ids.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_budgets( $user_id ) {
		$table   = self::table_budget();
		$user_id = absint( $user_id );
		$rows    = self::wpdb()->get_results(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE user_id = %d ORDER BY name ASC", $user_id ),
			ARRAY_A
		);
		if ( empty( $rows ) ) {
			return array();
		}
		$bc_table = self::table_budget_category();
		foreach ( $rows as &$row ) {
			$row['category_ids'] = self::wpdb()->get_col(
				self::wpdb()->prepare(
					"SELECT category_id FROM $bc_table WHERE budget_id = %d",
					(int) $row['id']
				)
			);
			$row['category_ids'] = array_map( 'intval', $row['category_ids'] );
		}
		return $rows;
	}

	/**
	 * Get a single budget by ID (any user). For admin use.
	 *
	 * @param int $id Budget ID.
	 * @return array|null With category_ids.
	 */
	public static function get_budget_by_id( $id ) {
		$table = self::table_budget();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d", absint( $id ) ),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$bc_table            = self::table_budget_category();
		$row['category_ids'] = self::wpdb()->get_col(
			self::wpdb()->prepare( "SELECT category_id FROM $bc_table WHERE budget_id = %d", (int) $row['id'] )
		);
		$row['category_ids'] = array_map( 'intval', $row['category_ids'] );
		return $row;
	}

	/**
	 * Get a single budget by ID only if it belongs to the user.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Budget ID.
	 * @return array|null With category_ids.
	 */
	public static function get_budget_for_user( $user_id, $id ) {
		$table = self::table_budget();
		$row   = self::wpdb()->get_row(
			self::wpdb()->prepare( "SELECT * FROM $table WHERE id = %d AND user_id = %d", absint( $id ), absint( $user_id ) ),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$bc_table            = self::table_budget_category();
		$row['category_ids'] = self::wpdb()->get_col(
			self::wpdb()->prepare( "SELECT category_id FROM $bc_table WHERE budget_id = %d", (int) $row['id'] )
		);
		$row['category_ids'] = array_map( 'intval', $row['category_ids'] );
		return $row;
	}

	/**
	 * Save a budget (create or update).
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Keys: name, target_amount, type, category_ids.
	 * @param int   $id      Update if > 0, create if 0.
	 * @return int|false Budget ID or false.
	 */
	public static function save_budget( $user_id, $data, $id = 0 ) {
		$table         = self::table_budget();
		$bc_table      = self::table_budget_category();
		$user_id       = absint( $user_id );
		$name          = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$target_amount = isset( $data['target_amount'] ) ? floatval( $data['target_amount'] ) : 0;
		$type          = isset( $data['type'] ) && 'yearly' === $data['type'] ? 'yearly' : 'monthly';
		$category_ids  = isset( $data['category_ids'] ) && is_array( $data['category_ids'] ) ? array_map( 'absint', $data['category_ids'] ) : array();

		if ( '' === $name ) {
			return false;
		}

		if ( $id ) {
			self::wpdb()->update(
				$table,
				array(
					'name'          => $name,
					'target_amount' => $target_amount,
					'type'          => $type,
				),
				array(
					'id'      => $id,
					'user_id' => $user_id,
				),
				array( '%s', '%f', '%s' ),
				array( '%d', '%d' )
			);
			self::wpdb()->delete( $bc_table, array( 'budget_id' => $id ), array( '%d' ) );
		} else {
			self::wpdb()->insert(
				$table,
				array(
					'user_id'       => $user_id,
					'name'          => $name,
					'target_amount' => $target_amount,
					'type'          => $type,
				),
				array( '%d', '%s', '%f', '%s' )
			);
			$id = self::wpdb()->insert_id ? (int) self::wpdb()->insert_id : 0;
			if ( ! $id ) {
				return false;
			}
		}

		foreach ( $category_ids as $cid ) {
			if ( $cid > 0 ) {
				self::wpdb()->insert(
					$bc_table,
					array(
						'budget_id'   => $id,
						'category_id' => $cid,
					),
					array( '%d', '%d' )
				);
			}
		}
		return $id;
	}

	/**
	 * Delete a budget and its category links.
	 *
	 * @param int $user_id User ID.
	 * @param int $id      Budget ID.
	 * @return bool
	 */
	public static function delete_budget( $user_id, $id ) {
		$table    = self::table_budget();
		$bc_table = self::table_budget_category();
		$id       = absint( $id );
		$user_id  = absint( $user_id );
		self::wpdb()->delete( $bc_table, array( 'budget_id' => $id ), array( '%d' ) );
		return (bool) self::wpdb()->delete(
			$table,
			array(
				'id'      => $id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Add a budget–category link.
	 *
	 * @param int $budget_id  Budget ID.
	 * @param int $category_id Category ID.
	 * @return bool
	 */
	public static function insert_budget_category( $budget_id, $category_id ) {
		$table       = self::table_budget_category();
		$budget_id   = absint( $budget_id );
		$category_id = absint( $category_id );
		if ( $budget_id < 1 || $category_id < 1 ) {
			return false;
		}
		self::wpdb()->insert(
			$table,
			array(
				'budget_id'   => $budget_id,
				'category_id' => $category_id,
			),
			array( '%d', '%d' )
		);
		return (bool) self::wpdb()->insert_id;
	}

	/**
	 * Remove a budget–category link.
	 *
	 * @param int $budget_id  Budget ID.
	 * @param int $category_id Category ID.
	 * @return bool
	 */
	public static function delete_budget_category( $budget_id, $category_id ) {
		$table = self::table_budget_category();
		return (bool) self::wpdb()->delete(
			$table,
			array(
				'budget_id'   => absint( $budget_id ),
				'category_id' => absint( $category_id ),
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Get aggregated data for graphs: by month and by category.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $group_by 'month' or 'category'.
	 * @param int    $year     Year to filter.
	 * @param int    $month    Month to filter (0 = all months).
	 * @return array
	 */
	public static function get_graph_data( $user_id, $group_by, $year, $month = 0 ) {
		$table     = self::table_transaction();
		$cat_table = self::table_category();
		$user_id   = absint( $user_id );
		$year      = absint( $year );
		$month     = absint( $month );

		$where  = 't.user_id = %d AND YEAR(t.date) = %d';
		$values = array( $user_id, $year );
		if ( $month > 0 ) {
			$where   .= ' AND MONTH(t.date) = %d';
			$values[] = $month;
		}

		if ( 'category' === $group_by ) {
			$sql = "SELECT t.category_id, COALESCE(c.name, 'Uncategorized') AS label, t.type, SUM(t.amount) AS total
				FROM $table t
				LEFT JOIN $cat_table c ON c.id = t.category_id AND c.user_id = t.user_id
				WHERE $where
				GROUP BY t.category_id, t.type
				ORDER BY total DESC";
		} else {
			$sql = "SELECT MONTH(t.date) AS month, t.type, SUM(t.amount) AS total
				FROM $table t
				WHERE $where
				GROUP BY MONTH(t.date), t.type
				ORDER BY month ASC";
		}
		$results = self::wpdb()->get_results( self::wpdb()->prepare( $sql, $values ), ARRAY_A );
		return is_array( $results ) ? $results : array();
	}
}
