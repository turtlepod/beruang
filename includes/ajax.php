<?php
/**
 * AJAX handlers for Beruang (frontend shortcodes).
 *
 * All handlers call ajax_auth() first, which verifies nonce via check_ajax_referer().
 *
 * @package Beruang
 */

namespace Beruang;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified in ajax_auth().

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register AJAX action hooks. Hooked to init.
 */
function ajax_setup() {
	add_action( 'wp_ajax_beruang_save_transaction', __NAMESPACE__ . '\ajax_save_transaction' );
	add_action( 'wp_ajax_beruang_get_transaction', __NAMESPACE__ . '\ajax_get_transaction' );
	add_action( 'wp_ajax_beruang_update_transaction', __NAMESPACE__ . '\ajax_update_transaction' );
	add_action( 'wp_ajax_beruang_get_transactions', __NAMESPACE__ . '\ajax_get_transactions' );
	add_action( 'wp_ajax_beruang_get_categories', __NAMESPACE__ . '\ajax_get_categories' );
	add_action( 'wp_ajax_beruang_save_category', __NAMESPACE__ . '\ajax_save_category' );
	add_action( 'wp_ajax_beruang_delete_category', __NAMESPACE__ . '\ajax_delete_category' );
	add_action( 'wp_ajax_beruang_get_budgets', __NAMESPACE__ . '\ajax_get_budgets' );
	add_action( 'wp_ajax_beruang_get_budget', __NAMESPACE__ . '\ajax_get_budget' );
	add_action( 'wp_ajax_beruang_save_budget', __NAMESPACE__ . '\ajax_save_budget' );
	add_action( 'wp_ajax_beruang_delete_budget', __NAMESPACE__ . '\ajax_delete_budget' );
	add_action( 'wp_ajax_beruang_get_graph_data', __NAMESPACE__ . '\ajax_get_graph_data' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\ajax_setup' );

/**
 * Verify nonce and return current user id or send JSON error and exit.
 *
 * @return int User ID.
 */
function ajax_auth() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Not logged in.', 'beruang' ) ) );
	}
	if ( ! check_ajax_referer( 'beruang_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'beruang' ) ) );
	}
	return get_current_user_id();
}

/**
 * Save transaction (form submit).
 */
function ajax_save_transaction() {
	$user_id     = ajax_auth();
	$date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	$time        = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : null;
	$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
	$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
	$amount      = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
	$type        = isset( $_POST['type'] ) && 'income' === $_POST['type'] ? 'income' : 'expense';

	$id = DB::insert_transaction(
		$user_id,
		array(
			'date'        => $date,
			'time'        => $time,
			'description' => $description,
			'category_id' => $category_id,
			'amount'      => $amount,
			'type'        => $type,
		)
	);
	if ( $id ) {
		wp_send_json_success( array( 'id' => $id ) );
	}
	wp_send_json_error( array( 'message' => __( 'Failed to save.', 'beruang' ) ) );
}

/**
 * Get transactions (list/filter).
 */
function ajax_get_transactions() {
	$user_id     = ajax_auth();
	$month       = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : (int) current_time( 'n' );
	$year        = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
	$search      = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
	$category_id = isset( $_GET['category_id'] ) ? sanitize_text_field( wp_unslash( $_GET['category_id'] ) ) : '';
	if ( '' !== $category_id && null !== $category_id ) {
		$category_id = absint( $category_id );
	}
	$page   = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
	$result = DB::get_transactions(
		$user_id,
		array(
			'month'       => $month,
			'year'        => $year,
			'search'      => $search,
			'category_id' => $category_id,
			'page'        => $page,
			'per_page'    => 50,
		)
	);
	wp_send_json_success( $result );
}

/**
 * Get a single transaction for editing (must belong to current user).
 */
function ajax_get_transaction() {
	$user_id = ajax_auth();
	$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'beruang' ) ) );
	}
	$row = DB::get_transaction_for_user( $user_id, $id );
	if ( ! $row ) {
		wp_send_json_error( array( 'message' => __( 'Transaction not found.', 'beruang' ) ) );
	}
	wp_send_json_success( array( 'transaction' => $row ) );
}

/**
 * Update a transaction (must belong to current user).
 */
function ajax_update_transaction() {
	$user_id = ajax_auth();
	$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'beruang' ) ) );
	}
	$existing = DB::get_transaction_for_user( $user_id, $id );
	if ( ! $existing ) {
		wp_send_json_error( array( 'message' => __( 'Transaction not found.', 'beruang' ) ) );
	}
	$data = array(
		'date'        => isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : $existing['date'],
		'time'        => isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : null,
		'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
		'category_id' => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
		'amount'      => isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0,
		'type'        => isset( $_POST['type'] ) && 'income' === $_POST['type'] ? 'income' : 'expense',
	);
	if ( '' === $data['time'] ) {
		$data['time'] = null;
	}
	$ok = DB::update_transaction( $id, $data );
	if ( $ok ) {
		wp_send_json_success( array( 'id' => $id ) );
	}
	wp_send_json_error( array( 'message' => __( 'Failed to update.', 'beruang' ) ) );
}

/**
 * Get categories (hierarchical for dropdown).
 */
function ajax_get_categories() {
	$user_id = ajax_auth();
	$flat    = DB::get_categories_flat( $user_id, true );
	wp_send_json_success( array( 'categories' => $flat ) );
}

/**
 * Save category (add or update).
 */
function ajax_save_category() {
	$user_id   = ajax_auth();
	$id        = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'Name required.', 'beruang' ) ) );
	}
	$saved = DB::save_category(
		$user_id,
		array(
			'name'      => $name,
			'parent_id' => $parent_id,
		),
		$id
	);
	if ( $saved ) {
		wp_send_json_success( array( 'id' => $saved ) );
	}
	wp_send_json_error( array( 'message' => __( 'Failed to save category.', 'beruang' ) ) );
}

/**
 * Delete category.
 */
function ajax_delete_category() {
	$user_id = ajax_auth();
	$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'beruang' ) ) );
	}
	$ok = DB::delete_category( $user_id, $id );
	wp_send_json_success( array( 'deleted' => $ok ) );
}

/**
 * Get budgets with progress (spent in period).
 */
function ajax_get_budgets() {
	$user_id   = ajax_auth();
	$budgets   = DB::get_budgets( $user_id );
	$year      = (int) current_time( 'Y' );
	$month     = (int) current_time( 'n' );
	$date_from = sprintf( '%04d-%02d-01', $year, $month );
	$date_to   = gmdate( 'Y-m-t', strtotime( $date_from ) );
	foreach ( $budgets as &$b ) {
		$cat_ids = ! empty( $b['category_ids'] ) ? $b['category_ids'] : array();
		if ( 'yearly' === $b['type'] ) {
			$date_from = $year . '-01-01';
			$date_to   = $year . '-12-31';
		}
		$b['spent']    = DB::sum_expenses( $user_id, $date_from, $date_to, $cat_ids );
		$b['progress'] = (float) $b['target_amount'] > 0 ? min( 100, ( $b['spent'] / (float) $b['target_amount'] ) * 100 ) : 0; // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
	}
	wp_send_json_success( array( 'budgets' => $budgets ) );
}

/**
 * Get a single budget for editing (must belong to current user).
 */
function ajax_get_budget() {
	$user_id = ajax_auth();
	$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'beruang' ) ) );
	}
	$row = DB::get_budget_for_user( $user_id, $id );
	if ( ! $row ) {
		wp_send_json_error( array( 'message' => __( 'Budget not found.', 'beruang' ) ) );
	}
	wp_send_json_success( array( 'budget' => $row ) );
}

/**
 * Save budget.
 */
function ajax_save_budget() {
	$user_id       = ajax_auth();
	$id            = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$target_amount = isset( $_POST['target_amount'] ) ? floatval( $_POST['target_amount'] ) : 0;
	$type          = isset( $_POST['type'] ) && 'yearly' === $_POST['type'] ? 'yearly' : 'monthly';
	$category_ids  = array();
	if ( ! empty( $_POST['category_ids'] ) && is_array( $_POST['category_ids'] ) ) {
		$category_ids = array_map( 'absint', $_POST['category_ids'] );
	}
	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'Name required.', 'beruang' ) ) );
	}
	$saved = DB::save_budget(
		$user_id,
		array(
			'name'          => $name,
			'target_amount' => $target_amount,
			'type'          => $type,
			'category_ids'  => $category_ids,
		),
		$id
	);
	if ( $saved ) {
		wp_send_json_success( array( 'id' => $saved ) );
	}
	wp_send_json_error( array( 'message' => __( 'Failed to save budget.', 'beruang' ) ) );
}

/**
 * Delete budget.
 */
function ajax_delete_budget() {
	$user_id = ajax_auth();
	$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'beruang' ) ) );
	}
	$ok = DB::delete_budget( $user_id, $id );
	wp_send_json_success( array( 'deleted' => $ok ) );
}

/**
 * Get graph data (by month or by category).
 */
function ajax_get_graph_data() {
	$user_id  = ajax_auth();
	$group_by = isset( $_GET['group_by'] ) && 'category' === $_GET['group_by'] ? 'category' : 'month';
	$year     = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
	$month    = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : 0;
	$data     = DB::get_graph_data( $user_id, $group_by, $year, $month );
	wp_send_json_success(
		array(
			'data'     => $data,
			'group_by' => $group_by,
		)
	);
}
