<?php
/**
 * Import handler for Beruang plugin.
 *
 * Separates import business logic from the WP admin layer so it can be
 * unit-tested independently of the HTTP/admin context.
 *
 * @package Beruang
 */

namespace BeruangBudget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles data import logic for Beruang.
 *
 * All pure-PHP methods (validate, remap_*) are side-effect-free and easy to test.
 * DB-calling methods (import_categories, import_wallets, import_transactions,
 * import_budgets) delegate to the DB class and can be tested with a wpdb mock.
 */
class ImportHandler {

	/**
	 * Validate an import data array.
	 *
	 * Returns null when the data is valid (at least one importable section is
	 * non-empty). Returns a translated error message string on failure.
	 *
	 * @param mixed $data Decoded JSON data from the upload.
	 * @return string|null Null on success, error message on failure.
	 */
	public static function validate( $data ): ?string {
		if ( ! is_array( $data ) ) {
			return __( 'Invalid or empty import file.', 'beruang-budget' );
		}
		if (
			empty( $data['categories'] ) &&
			empty( $data['wallets'] ) &&
			empty( $data['transactions'] ) &&
			empty( $data['budgets'] )
		) {
			return __( 'Invalid or empty import file.', 'beruang-budget' );
		}
		return null;
	}

	/**
	 * Remap category_id and wallet_id in a transaction using ID maps.
	 *
	 * If the ID is 0 (none) or is not present in the corresponding map, it is
	 * left as-is. This preserves the original value when the related record was
	 * not included in the import file.
	 *
	 * @param array $tx         Transaction row from the export file.
	 * @param array $map_cat    Map of old category ID => new category ID.
	 * @param array $map_wallet Map of old wallet ID => new wallet ID.
	 * @return array Transaction with remapped IDs.
	 */
	public static function remap_transaction( array $tx, array $map_cat, array $map_wallet ): array {
		$cat_id    = isset( $tx['category_id'] ) ? (int) $tx['category_id'] : 0;
		$wallet_id = isset( $tx['wallet_id'] ) ? (int) $tx['wallet_id'] : 0;

		if ( $cat_id && isset( $map_cat[ $cat_id ] ) ) {
			$cat_id = $map_cat[ $cat_id ];
		}
		if ( $wallet_id && isset( $map_wallet[ $wallet_id ] ) ) {
			$wallet_id = $map_wallet[ $wallet_id ];
		}

		$tx['category_id'] = $cat_id;
		$tx['wallet_id']   = $wallet_id;

		return $tx;
	}

	/**
	 * Remap a budget's category_ids list using the category ID map.
	 *
	 * Category IDs that are not present in the map are silently dropped (they
	 * belong to categories that were not imported).
	 *
	 * @param array $category_ids Old category IDs from the export file.
	 * @param array $map_cat      Map of old category ID => new category ID.
	 * @return array New category IDs (only those present in the map).
	 */
	public static function remap_budget_categories( array $category_ids, array $map_cat ): array {
		$out = array();
		foreach ( $category_ids as $cid ) {
			$cid = (int) $cid;
			if ( $cid && isset( $map_cat[ $cid ] ) ) {
				$out[] = $map_cat[ $cid ];
			}
		}
		return $out;
	}

	/**
	 * Import categories and build old-to-new ID map.
	 *
	 * Categories are expected to be ordered parents-before-children (which the
	 * export guarantees via ORDER BY parent_id ASC). Parent IDs are remapped
	 * via the accumulating map so child categories link to the newly-created
	 * parent IDs.
	 *
	 * @param int   $user_id    Target user ID.
	 * @param array $categories Category rows from the export file.
	 * @return array Map of old category ID => new category ID.
	 */
	public static function import_categories( int $user_id, array $categories ): array {
		$map = array();
		foreach ( $categories as $cat ) {
			$old_id    = isset( $cat['id'] ) ? (int) $cat['id'] : 0;
			$parent_id = isset( $cat['parent_id'] ) ? (int) $cat['parent_id'] : 0;

			// Remap parent to its new ID if it was already imported.
			if ( $parent_id && isset( $map[ $parent_id ] ) ) {
				$parent_id = $map[ $parent_id ];
			}

			$new_id = DB::save_category(
				$user_id,
				array(
					'name'       => $cat['name'] ?? '',
					'parent_id'  => $parent_id,
					'sort_order' => $cat['sort_order'] ?? 0,
				),
				0
			);

			if ( $new_id && $old_id ) {
				$map[ $old_id ] = $new_id;
			}
		}
		return $map;
	}

	/**
	 * Import wallets and build old-to-new ID map.
	 *
	 * Each wallet is re-created with its original name, initial_amount, and
	 * initial_date so that the current-balance calculation remains correct after
	 * import.
	 *
	 * @param int   $user_id User ID.
	 * @param array $wallets Wallet rows from the export file.
	 * @return array Map of old wallet ID => new wallet ID.
	 */
	public static function import_wallets( int $user_id, array $wallets ): array {
		$map = array();
		foreach ( $wallets as $wallet ) {
			$old_id = isset( $wallet['id'] ) ? (int) $wallet['id'] : 0;
			$name   = isset( $wallet['name'] ) ? sanitize_text_field( $wallet['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}
			$new_id = DB::save_wallet(
				$user_id,
				array(
					'name'           => $name,
					'initial_amount' => isset( $wallet['initial_amount'] ) ? (float) $wallet['initial_amount'] : 0.0,
					'initial_date'   => isset( $wallet['initial_date'] ) ? sanitize_text_field( $wallet['initial_date'] ) : current_time( 'Y-m-d' ),
				),
				0
			);
			if ( $new_id && $old_id ) {
				$map[ $old_id ] = $new_id;
			}
		}
		return $map;
	}

	/**
	 * Import transactions, remapping category and wallet IDs.
	 *
	 * @param int   $user_id      Target user ID.
	 * @param array $transactions Transaction rows from the export file.
	 * @param array $map_cat      Map of old category ID => new category ID.
	 * @param array $map_wallet   Map of old wallet ID => new wallet ID.
	 * @return int Number of transactions successfully imported.
	 */
	public static function import_transactions( int $user_id, array $transactions, array $map_cat, array $map_wallet ): int {
		$count = 0;
		foreach ( $transactions as $tx ) {
			$tx = self::remap_transaction( $tx, $map_cat, $map_wallet );
			$id = DB::insert_transaction(
				$user_id,
				array(
					'date'        => $tx['date'] ?? '',
					'time'        => $tx['time'] ?? null,
					'description' => $tx['description'] ?? '',
					'note'        => $tx['note'] ?? '',
					'wallet_id'   => isset( $tx['wallet_id'] ) ? (int) $tx['wallet_id'] : 0,
					'category_id' => isset( $tx['category_id'] ) ? (int) $tx['category_id'] : 0,
					'amount'      => $tx['amount'] ?? 0,
					'type'        => 'income' === ( $tx['type'] ?? 'expense' ) ? 'income' : 'expense',
				)
			);
			if ( $id ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Import budgets, remapping category IDs.
	 *
	 * Budget category links that reference categories not present in the map
	 * (i.e. categories that were not included in the export) are silently dropped.
	 *
	 * @param int   $user_id User ID.
	 * @param array $budgets Budget rows from the export file.
	 * @param array $map_cat Map of old category ID => new category ID.
	 * @return int Number of budgets successfully imported.
	 */
	public static function import_budgets( int $user_id, array $budgets, array $map_cat ): int {
		$count = 0;
		foreach ( $budgets as $budget ) {
			$cat_ids = self::remap_budget_categories(
				! empty( $budget['category_ids'] ) ? (array) $budget['category_ids'] : array(),
				$map_cat
			);
			$id      = DB::save_budget(
				$user_id,
				array(
					'name'          => $budget['name'] ?? '',
					'target_amount' => $budget['target_amount'] ?? 0,
					'type'          => 'yearly' === ( $budget['type'] ?? 'monthly' ) ? 'yearly' : 'monthly',
					'category_ids'  => $cat_ids,
				),
				0
			);
			if ( $id ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Run a full import for a user.
	 *
	 * Processes all sections in dependency order:
	 * categories -> wallets -> transactions -> budgets.
	 *
	 * @param int   $user_id User ID to import data for.
	 * @param array $data    Decoded export data (already validated).
	 * @return array{categories: int, wallets: int, transactions: int, budgets: int}
	 *              Counts of records imported per section.
	 */
	public static function run( int $user_id, array $data ): array {
		$map_cat    = self::import_categories( $user_id, $data['categories'] ?? array() );
		$map_wallet = self::import_wallets( $user_id, $data['wallets'] ?? array() );
		$tx_count   = self::import_transactions( $user_id, $data['transactions'] ?? array(), $map_cat, $map_wallet );
		$bg_count   = self::import_budgets( $user_id, $data['budgets'] ?? array(), $map_cat );

		return array(
			'categories'   => count( $map_cat ),
			'wallets'      => count( $map_wallet ),
			'transactions' => $tx_count,
			'budgets'      => $bg_count,
		);
	}
}
