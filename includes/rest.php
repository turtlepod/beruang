<?php
/**
 * REST API endpoints for Beruang (frontend shortcodes).
 *
 * Replaces admin-ajax.php. All endpoints require a logged-in user.
 *
 * @package Beruang
 */

namespace Beruang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', __NAMESPACE__ . '\rest_register_routes' );

/**
 * Permission callback: require logged-in user.
 *
 * @param \WP_REST_Request $request Request.
 * @return true|\WP_Error
 */
function rest_permission_logged_in( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! is_user_logged_in() ) {
		return new \WP_Error( 'rest_not_logged_in', __( 'Not logged in.', 'beruang' ), array( 'status' => 401 ) );
	}
	return true;
}

/**
 * Send JSON error response.
 *
 * @param \WP_REST_Response $response Response object.
 * @param string            $message  Error message.
 * @param int               $status   HTTP status.
 * @return \WP_REST_Response
 */
function rest_json_error( $response, $message, $status = 400 ) {
	$response->set_data(
		array(
			'success' => false,
			'data'    => array( 'message' => $message ),
		)
	);
	$response->set_status( $status );
	return $response;
}

/**
 * Normalize wallet ID input. Empty values are treated as "No Wallet".
 *
 * @param mixed $raw Raw wallet_id.
 * @return int|null
 */
function rest_parse_wallet_id( $raw ) {
	if ( null === $raw || '' === $raw ) {
		return null;
	}
	$id = absint( $raw );
	return $id > 0 ? $id : null;
}

/**
 * Register REST routes.
 */
function rest_register_routes() {
	$ns   = 'beruang/v1';
	$perm = __NAMESPACE__ . '\rest_permission_logged_in';

	// Transactions
	register_rest_route(
		$ns,
		'/transactions',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_save_transaction',
			'args'                => array(
				'date'        => array(
					'required' => true,
					'type'     => 'string',
				),
				'time'        => array( 'type' => 'string' ),
				'description' => array(
					'required' => true,
					'type'     => 'string',
				),
				'note'        => array( 'type' => 'string' ),
				'wallet_id'   => array( 'type' => array( 'string', 'null' ) ),
				'category_id' => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'amount'      => array(
					'required' => true,
					'type'     => 'number',
				),
				'type'        => array(
					'default' => 'expense',
					'enum'    => array( 'expense', 'income' ),
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/transactions',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_transactions',
			'args'                => array(
				'year'        => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'search'      => array(
					'default' => '',
					'type'    => 'string',
				),
				'category_id' => array(
					'default' => '',
					'type'    => 'string',
				),
				'budget_id'   => array(
					'default' => '',
					'type'    => 'string',
				),
				'wallet_id'   => array(
					'default' => '',
					'type'    => 'string',
				),
				'page'        => array(
					'default' => 1,
					'type'    => 'integer',
				),
				'per_page'    => array(
					'default' => 100,
					'type'    => 'integer',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/transactions/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_transaction',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/transactions/(?P<id>\d+)',
		array(
			'methods'             => 'PUT',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_update_transaction',
			'args'                => array(
				'id'          => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
				'date'        => array( 'type' => 'string' ),
				'time'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'note'        => array( 'type' => 'string' ),
				'wallet_id'   => array( 'type' => array( 'string', 'null' ) ),
				'category_id' => array( 'type' => 'integer' ),
				'amount'      => array( 'type' => 'number' ),
				'type'        => array( 'enum' => array( 'expense', 'income' ) ),
			),
		)
	);
	register_rest_route(
		$ns,
		'/transactions/(?P<id>\d+)',
		array(
			'methods'             => 'DELETE',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_delete_transaction',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);

	// Description suggestions (autocomplete)
	register_rest_route(
		$ns,
		'/descriptions',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_descriptions',
			'args'                => array(
				'search' => array(
					'default' => '',
					'type'    => 'string',
				),
			),
		)
	);

	// Categories
	register_rest_route(
		$ns,
		'/categories',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_categories',
		)
	);

	// Wallets
	register_rest_route(
		$ns,
		'/wallets',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_wallets',
		)
	);
	register_rest_route(
		$ns,
		'/wallets',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_save_wallet',
			'args'                => array(
				'id'             => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'name'           => array(
					'required' => true,
					'type'     => 'string',
				),
				'initial_amount' => array(
					'type' => 'number',
				),
				'initial_date'   => array(
					'type' => 'string',
				),
				'set_as_default' => array(
					'type' => 'boolean',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/wallets/transfer',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_transfer_wallet',
			'args'                => array(
				'from_wallet_id' => array(
					'required' => true,
					'type'     => 'integer',
				),
				'to_wallet_id'   => array(
					'required' => true,
					'type'     => 'integer',
				),
				'amount'         => array(
					'required' => true,
					'type'     => 'number',
				),
				'category_id'    => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'note'           => array( 'type' => 'string' ),
				'date'           => array(
					'required' => true,
					'type'     => 'string',
				),
				'time'           => array( 'type' => 'string' ),
			),
		)
	);
	register_rest_route(
		$ns,
		'/wallets/default',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_set_default_wallet',
			'args'                => array(
				'wallet_id' => array( 'type' => 'string' ),
			),
		)
	);
	register_rest_route(
		$ns,
		'/wallets/(?P<id>\d+)',
		array(
			'methods'             => 'DELETE',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_delete_wallet',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/categories',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_save_category',
			'args'                => array(
				'id'        => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'name'      => array(
					'required' => true,
					'type'     => 'string',
				),
				'parent_id' => array(
					'default' => 0,
					'type'    => 'integer',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/categories/(?P<id>\d+)',
		array(
			'methods'             => 'DELETE',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_delete_category',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);

	// Budgets
	register_rest_route(
		$ns,
		'/budgets',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_budgets',
			'args'                => array(
				'year'  => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'month' => array(
					'default' => 0,
					'type'    => 'integer',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/budgets/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_budget',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/budgets',
		array(
			'methods'             => 'POST',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_save_budget',
			'args'                => array(
				'id'            => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'name'          => array(
					'required' => true,
					'type'     => 'string',
				),
				'target_amount' => array(
					'default' => 0,
					'type'    => 'number',
				),
				'type'          => array(
					'default' => 'monthly',
					'enum'    => array( 'monthly', 'yearly' ),
				),
				'category_ids'  => array(
					'default' => array(),
					'type'    => 'array',
				),
			),
		)
	);
	register_rest_route(
		$ns,
		'/budgets/(?P<id>\d+)',
		array(
			'methods'             => 'DELETE',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_delete_budget',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $v ) {
						return is_numeric( $v ) && $v > 0;
					},
				),
			),
		)
	);

	// Graph
	register_rest_route(
		$ns,
		'/graph',
		array(
			'methods'             => 'GET',
			'permission_callback' => $perm,
			'callback'            => __NAMESPACE__ . '\rest_get_graph_data',
			'args'                => array(
				'year'     => array(
					'default' => 0,
					'type'    => 'integer',
				),
				'group_by' => array(
					'default' => 'month',
					'enum'    => array( 'month', 'category' ),
				),
				'month'    => array(
					'default' => 0,
					'type'    => 'integer',
				),
			),
		)
	);
}

/**
 * REST: Save transaction.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_save_transaction( $request ) {
	$user_id     = get_current_user_id();
	$body        = $request->get_json_params();
	$body        = is_array( $body ) ? $body : array();
	$date        = isset( $body['date'] ) ? sanitize_text_field( $body['date'] ) : '';
	$time        = isset( $body['time'] ) ? sanitize_text_field( $body['time'] ) : null;
	$description = isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : '';
	$note        = isset( $body['note'] ) ? sanitize_textarea_field( $body['note'] ) : '';
	$wallet_id   = array_key_exists( 'wallet_id', $body ) ? rest_parse_wallet_id( $body['wallet_id'] ) : null;
	$category_id = isset( $body['category_id'] ) ? absint( $body['category_id'] ) : 0;
	$amount      = isset( $body['amount'] ) ? floatval( $body['amount'] ) : 0;
	$type        = isset( $body['type'] ) && 'income' === $body['type'] ? 'income' : 'expense';

	if ( '' === $time ) {
		$time = null;
	}

	if ( null !== $wallet_id && ! DB::get_wallet_for_user( $user_id, $wallet_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid wallet.', 'beruang' ), 400 );
	}

	if ( $category_id > 0 && ! DB::get_category_for_user( $user_id, $category_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid category.', 'beruang' ), 400 );
	}

	$id = DB::insert_transaction(
		$user_id,
		array(
			'date'        => $date,
			'time'        => $time,
			'description' => $description,
			'note'        => $note,
			'wallet_id'   => $wallet_id,
			'category_id' => $category_id,
			'amount'      => $amount,
			'type'        => $type,
		)
	);
	if ( $id ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'id' => $id ),
			)
		);
	}
	return rest_json_error( new \WP_REST_Response(), __( 'Failed to save.', 'beruang' ), 400 );
}

/**
 * REST: Get transactions.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_transactions( $request ) {
	$user_id     = get_current_user_id();
	$year        = $request->get_param( 'year' ) ? absint( $request->get_param( 'year' ) ) : (int) current_time( 'Y' );
	$search      = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
	$category_id = $request->get_param( 'category_id' );
	$category_id = '' !== $category_id && null !== $category_id ? absint( $category_id ) : '';
	$budget_id   = $request->get_param( 'budget_id' );
	$budget_id   = '' !== $budget_id && null !== $budget_id ? absint( $budget_id ) : '';
	$wallet_id   = $request->get_param( 'wallet_id' );
	$wallet_id   = '' !== $wallet_id && null !== $wallet_id ? absint( $wallet_id ) : '';
	$page        = max( 1, absint( $request->get_param( 'page' ) ) );

	if ( $category_id > 0 && ! DB::get_category_for_user( $user_id, $category_id ) ) {
		$category_id = '';
	}

	$category_ids = array();
	if ( $budget_id > 0 ) {
		$budget = DB::get_budget_for_user( $user_id, $budget_id );
		if ( ! $budget ) {
			$budget_id = '';
		} else {
			$category_ids = ! empty( $budget['category_ids'] ) && is_array( $budget['category_ids'] )
				? array_map( 'absint', $budget['category_ids'] )
				: array();
		}
	}

	if ( $wallet_id > 0 && ! DB::get_wallet_for_user( $user_id, $wallet_id ) ) {
		$wallet_id = '';
	}

	$per_page = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 100;
	$per_page = min( max( 1, $per_page ), 500 );

	$args   = array(
		'year'         => $year,
		'search'       => $search,
		'category_id'  => $category_id,
		'category_ids' => $category_ids,
		'wallet_id'    => $wallet_id,
		'page'         => $page,
		'per_page'     => $per_page,
	);
	$result = DB::get_transactions( $user_id, $args );
	$total  = (int) ( $result['total'] ?? 0 );
	$pages  = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array_merge(
				$result,
				array(
					'pages'    => $pages,
					'page'     => $page,
					'per_page' => $per_page,
				)
			),
		)
	);
}

/**
 * REST: Get single transaction.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_transaction( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$row = DB::get_transaction_for_user( $user_id, $id );
	if ( ! $row ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Transaction not found.', 'beruang' ), 404 );
	}
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'transaction' => $row ),
		)
	);
}

/**
 * REST: Update transaction.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_update_transaction( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	$body    = $request->get_json_params();
	$body    = is_array( $body ) ? $body : array();

	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$existing = DB::get_transaction_for_user( $user_id, $id );
	if ( ! $existing ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Transaction not found.', 'beruang' ), 404 );
	}

	$existing_wallet_id = isset( $existing['wallet_id'] ) && '' !== (string) $existing['wallet_id']
		? absint( $existing['wallet_id'] )
		: null;

	$data = array(
		'date'        => isset( $body['date'] ) ? sanitize_text_field( $body['date'] ) : $existing['date'],
		'time'        => isset( $body['time'] ) ? sanitize_text_field( $body['time'] ) : null,
		'description' => isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : $existing['description'],
		'note'        => isset( $body['note'] ) ? sanitize_textarea_field( $body['note'] ) : (string) ( $existing['note'] ?? '' ),
		'wallet_id'   => array_key_exists( 'wallet_id', $body ) ? rest_parse_wallet_id( $body['wallet_id'] ) : $existing_wallet_id,
		'category_id' => isset( $body['category_id'] ) ? absint( $body['category_id'] ) : (int) ( $existing['category_id'] ?? 0 ),
		'amount'      => isset( $body['amount'] ) ? floatval( $body['amount'] ) : (float) ( $existing['amount'] ?? 0 ),
		'type'        => isset( $body['type'] ) && 'income' === $body['type'] ? 'income' : 'expense',
	);
	if ( '' === $data['time'] ) {
		$data['time'] = null;
	}

	$wallet_id = $data['wallet_id'];
	if ( null !== $wallet_id && ! DB::get_wallet_for_user( $user_id, $wallet_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid wallet.', 'beruang' ), 400 );
	}

	$cat_id = (int) $data['category_id'];
	if ( $cat_id > 0 && ! DB::get_category_for_user( $user_id, $cat_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid category.', 'beruang' ), 400 );
	}

	$existing_time = isset( $existing['time'] ) && '' !== trim( (string) $existing['time'] ) ? $existing['time'] : null;
	// Normalize time to HH:MM so MySQL's HH:MM:SS and input's HH:MM compare equal.
	$normalize_time      = function ( $t ) {
		if ( null === $t ) {
			return null;
		}
		$parts = explode( ':', (string) $t );
		if ( count( $parts ) >= 2 ) {
			return sprintf( '%02d:%02d', (int) $parts[0], (int) $parts[1] );
		}
		return $t;
	};
	$existing_normalized = array(
		'date'        => (string) ( $existing['date'] ?? '' ),
		'time'        => $normalize_time( $existing_time ),
		'description' => (string) ( $existing['description'] ?? '' ),
		'note'        => (string) ( $existing['note'] ?? '' ),
		'wallet_id'   => $existing_wallet_id,
		'category_id' => (int) ( $existing['category_id'] ?? 0 ),
		'amount'      => (float) ( $existing['amount'] ?? 0 ),
		'type'        => ( 'income' === ( $existing['type'] ?? '' ) ) ? 'income' : 'expense',
	);
	$data_normalized     = array(
		'date'        => $data['date'],
		'time'        => $normalize_time( $data['time'] ),
		'description' => $data['description'],
		'note'        => $data['note'],
		'wallet_id'   => $data['wallet_id'],
		'category_id' => $data['category_id'],
		'amount'      => $data['amount'],
		'type'        => $data['type'],
	);
	if ( $existing_normalized === $data_normalized ) {
		return rest_json_error( new \WP_REST_Response(), __( 'No changes were made.', 'beruang' ), 400 );
	}

	$ok = DB::update_transaction( $id, $data );
	if ( $ok ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'id' => $id ),
			)
		);
	}
	return rest_json_error( new \WP_REST_Response(), __( 'Failed to update.', 'beruang' ), 400 );
}

/**
 * REST: Delete transaction.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_delete_transaction( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$ok = DB::delete_transaction( $user_id, $id );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'deleted' => $ok ),
		)
	);
}

/**
 * REST: Get description suggestions for autocomplete.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_descriptions( $request ) {
	$user_id = get_current_user_id();
	$search  = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
	if ( '' === $search ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'descriptions' => array() ),
			)
		);
	}
	$items = DB::get_description_suggestions( $user_id, $search );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'descriptions' => $items ),
		)
	);
}

/**
 * REST: Get categories.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_categories( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$user_id = get_current_user_id();
	$flat    = DB::get_categories_flat( $user_id, true );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'categories' => $flat ),
		)
	);
}

/**
 * REST: Save category.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_save_category( $request ) {
	$user_id   = get_current_user_id();
	$body      = $request->get_json_params();
	$body      = is_array( $body ) ? $body : array();
	$id        = isset( $body['id'] ) ? absint( $body['id'] ) : 0;
	$name      = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
	$parent_id = isset( $body['parent_id'] ) ? absint( $body['parent_id'] ) : 0;

	if ( '' === $name ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Name required.', 'beruang' ), 400 );
	}
	if ( $id > 0 && ! DB::get_category_for_user( $user_id, $id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Category not found.', 'beruang' ), 404 );
	}
	if ( $parent_id > 0 && ! DB::get_category_for_user( $user_id, $parent_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid parent category.', 'beruang' ), 400 );
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
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'id' => $saved ),
			)
		);
	}
	return rest_json_error( new \WP_REST_Response(), __( 'Failed to save category.', 'beruang' ), 400 );
}

/**
 * REST: Delete category.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_delete_category( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$ok = DB::delete_category( $user_id, $id );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'deleted' => $ok ),
		)
	);
}

/**
 * REST: Get wallets.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_wallets( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$user_id = get_current_user_id();
	$wallets = DB::get_wallets( $user_id );

	foreach ( $wallets as &$wallet ) {
		$wallet['initial_amount'] = isset( $wallet['initial_amount'] ) ? (float) $wallet['initial_amount'] : 0.0;
		$wallet['current_amount'] = DB::get_wallet_current_amount( $user_id, absint( $wallet['id'] ) );
	}
	unset( $wallet );

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'wallets'           => $wallets,
				'default_wallet_id' => DB::get_default_wallet_id( $user_id ),
			),
		)
	);
}

/**
 * REST: Save wallet.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_save_wallet( $request ) {
	$user_id        = get_current_user_id();
	$body           = $request->get_json_params();
	$body           = is_array( $body ) ? $body : array();
	$id             = isset( $body['id'] ) ? absint( $body['id'] ) : 0;
	$name           = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
	$initial_amount = isset( $body['initial_amount'] ) ? (float) $body['initial_amount'] : 0.0;
	$initial_date   = isset( $body['initial_date'] ) ? sanitize_text_field( $body['initial_date'] ) : current_time( 'Y-m-d' );
	$set_as_default = ! empty( $body['set_as_default'] );

	if ( '' === $name ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Name required.', 'beruang' ), 400 );
	}

	if ( $id > 0 ) {
		$wallet = DB::get_wallet_for_user( $user_id, $id );
		if ( ! $wallet ) {
			return rest_json_error( new \WP_REST_Response(), __( 'Wallet not found.', 'beruang' ), 404 );
		}
	}

	$saved = DB::save_wallet(
		$user_id,
		array(
			'name'           => $name,
			'initial_amount' => $initial_amount,
			'initial_date'   => $initial_date,
		),
		$id
	);

	if ( $saved && $set_as_default ) {
		DB::set_default_wallet_id( $user_id, $saved );
	}

	if ( $saved ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'id'                => $saved,
					'default_wallet_id' => DB::get_default_wallet_id( $user_id ),
				),
			)
		);
	}

	return rest_json_error( new \WP_REST_Response(), __( 'Failed to save wallet.', 'beruang' ), 400 );
}

/**
 * REST: Delete wallet.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_delete_wallet( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];

	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}

	$wallet = DB::get_wallet_for_user( $user_id, $id );
	if ( ! $wallet ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Wallet not found.', 'beruang' ), 404 );
	}

	$ok = DB::delete_wallet( $user_id, $id );

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'deleted' => $ok ),
		)
	);
}

/**
 * REST: Set default wallet.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_set_default_wallet( $request ) {
	$user_id   = get_current_user_id();
	$body      = $request->get_json_params();
	$body      = is_array( $body ) ? $body : array();
	$wallet_id = array_key_exists( 'wallet_id', $body ) ? rest_parse_wallet_id( $body['wallet_id'] ) : null;

	if ( null !== $wallet_id && ! DB::get_wallet_for_user( $user_id, $wallet_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Wallet not found.', 'beruang' ), 404 );
	}

	if ( ! DB::set_default_wallet_id( $user_id, $wallet_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Failed to set default wallet.', 'beruang' ), 400 );
	}

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'default_wallet_id' => DB::get_default_wallet_id( $user_id ),
			),
		)
	);
}

/**
 * REST: Transfer between wallets — creates one expense and one income transaction.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_transfer_wallet( $request ) {
	$user_id = get_current_user_id();
	$body    = $request->get_json_params();
	$body    = is_array( $body ) ? $body : array();

	$from_id     = absint( $body['from_wallet_id'] ?? 0 );
	$to_id       = absint( $body['to_wallet_id'] ?? 0 );
	$amount      = floatval( $body['amount'] ?? 0 );
	$category_id = absint( $body['category_id'] ?? 0 );
	$note        = sanitize_textarea_field( $body['note'] ?? '' );
	$date        = sanitize_text_field( $body['date'] ?? '' );
	$time        = isset( $body['time'] ) && '' !== (string) $body['time'] ? sanitize_text_field( $body['time'] ) : null;

	if ( ! $from_id || ! $to_id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Both wallets are required.', 'beruang' ), 400 );
	}
	if ( $from_id === $to_id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Source and target wallets must be different.', 'beruang' ), 400 );
	}
	if ( $amount <= 0 ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Amount must be greater than zero.', 'beruang' ), 400 );
	}

	$from_wallet = DB::get_wallet_for_user( $user_id, $from_id );
	$to_wallet   = DB::get_wallet_for_user( $user_id, $to_id );

	if ( ! $from_wallet ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Source wallet not found.', 'beruang' ), 404 );
	}
	if ( ! $to_wallet ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Target wallet not found.', 'beruang' ), 404 );
	}
	if ( $category_id > 0 && ! DB::get_category_for_user( $user_id, $category_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid category.', 'beruang' ), 400 );
	}

	$shared = array(
		'date'        => $date,
		'time'        => $time,
		'note'        => $note,
		'category_id' => $category_id,
		'amount'      => $amount,
	);

	// Expense from source wallet.
	$expense_id = DB::insert_transaction(
		$user_id,
		array_merge(
			$shared,
			array(
				'wallet_id'   => $from_id,
				/* translators: %s: target wallet name */
				'description' => sprintf( __( 'Transfer to %s', 'beruang' ), $to_wallet['name'] ),
				'type'        => 'expense',
			)
		)
	);

	if ( ! $expense_id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Transfer failed.', 'beruang' ), 500 );
	}

	// Income to target wallet.
	$income_id = DB::insert_transaction(
		$user_id,
		array_merge(
			$shared,
			array(
				'wallet_id'   => $to_id,
				/* translators: %s: source wallet name */
				'description' => sprintf( __( 'Transfer from %s', 'beruang' ), $from_wallet['name'] ),
				'type'        => 'income',
			)
		)
	);

	if ( ! $income_id ) {
		// Compensate: roll back the expense already inserted.
		DB::delete_transaction( $user_id, $expense_id );
		return rest_json_error( new \WP_REST_Response(), __( 'Transfer failed.', 'beruang' ), 500 );
	}

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'expense_id' => $expense_id,
				'income_id'  => $income_id,
			),
		)
	);
}

/**
 * REST: Get budgets.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_budgets( $request ) {
	$user_id = get_current_user_id();
	$budgets = DB::get_budgets( $user_id );
	$year    = $request->get_param( 'year' ) ? absint( $request->get_param( 'year' ) ) : (int) current_time( 'Y' );
	$month   = $request->get_param( 'month' ) ? absint( $request->get_param( 'month' ) ) : (int) current_time( 'n' );
	$year    = $year > 0 ? $year : (int) current_time( 'Y' );
	$month   = $month >= 1 && $month <= 12 ? $month : (int) current_time( 'n' );

	$yearly_from  = sprintf( '%04d-01-01', $year );
	$yearly_to    = sprintf( '%04d-12-31', $year );
	$monthly_from = sprintf( '%04d-%02d-01', $year, $month );
	$monthly_to   = gmdate( 'Y-m-t', strtotime( $monthly_from ) );

	$groups      = array();
	$group_spent = array();
	foreach ( $budgets as &$b ) {
		$cat_ids = ! empty( $b['category_ids'] ) && is_array( $b['category_ids'] ) ? $b['category_ids'] : array();
		sort( $cat_ids );
		$type     = isset( $b['type'] ) ? $b['type'] : 'monthly';
		$group_id = $type . '|' . implode( ',', $cat_ids );
		if ( ! isset( $groups[ $group_id ] ) ) {
			$groups[ $group_id ] = array(
				'type'    => $type,
				'cat_ids' => $cat_ids,
			);
		}
	}
	unset( $b );

	foreach ( $groups as $group_id => $group ) {
		$date_from                = 'yearly' === $group['type'] ? $yearly_from : $monthly_from;
		$date_to                  = 'yearly' === $group['type'] ? $yearly_to : $monthly_to;
		$group_spent[ $group_id ] = DB::sum_net_amount( $user_id, $date_from, $date_to, $group['cat_ids'] );
	}

	foreach ( $budgets as &$b ) {
		$cat_ids = isset( $b['category_ids'] ) && is_array( $b['category_ids'] ) ? $b['category_ids'] : array();
		sort( $cat_ids );
		$type          = isset( $b['type'] ) ? $b['type'] : 'monthly';
		$group_id      = $type . '|' . implode( ',', $cat_ids );
		$spent         = isset( $group_spent[ $group_id ] ) ? $group_spent[ $group_id ] : 0;
		$b['spent']    = $spent;
		$b['progress'] = (float) $b['target_amount'] > 0 ? min( 100, ( max( 0, $spent ) / (float) $b['target_amount'] ) * 100 ) : 0; // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
	}
	unset( $b );

	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'budgets' => $budgets ),
		)
	);
}

/**
 * REST: Get single budget.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_budget( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$row = DB::get_budget_for_user( $user_id, $id );
	if ( ! $row ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Budget not found.', 'beruang' ), 404 );
	}
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'budget' => $row ),
		)
	);
}

/**
 * REST: Save budget.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_save_budget( $request ) {
	$user_id       = get_current_user_id();
	$body          = $request->get_json_params();
	$body          = is_array( $body ) ? $body : array();
	$id            = isset( $body['id'] ) ? absint( $body['id'] ) : 0;
	$name          = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
	$target_amount = isset( $body['target_amount'] ) ? floatval( $body['target_amount'] ) : 0;
	$type          = isset( $body['type'] ) && 'yearly' === $body['type'] ? 'yearly' : 'monthly';
	$category_ids  = isset( $body['category_ids'] ) && is_array( $body['category_ids'] ) ? array_map( 'absint', $body['category_ids'] ) : array();

	if ( '' === $name ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Name required.', 'beruang' ), 400 );
	}
	if ( $id > 0 && ! DB::get_budget_for_user( $user_id, $id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Budget not found.', 'beruang' ), 404 );
	}
	foreach ( $category_ids as $cid ) {
		if ( $cid > 0 && ! DB::get_category_for_user( $user_id, $cid ) ) {
			return rest_json_error( new \WP_REST_Response(), __( 'Invalid category.', 'beruang' ), 400 );
		}
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
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array( 'id' => $saved ),
			)
		);
	}
	return rest_json_error( new \WP_REST_Response(), __( 'Failed to save budget.', 'beruang' ), 400 );
}

/**
 * REST: Delete budget.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_delete_budget( $request ) {
	$user_id = get_current_user_id();
	$id      = (int) $request['id'];
	if ( ! $id ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid ID.', 'beruang' ), 400 );
	}
	$ok = DB::delete_budget( $user_id, $id );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array( 'deleted' => $ok ),
		)
	);
}

/**
 * REST: Get graph data.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_get_graph_data( $request ) {
	$user_id  = get_current_user_id();
	$group_by = 'category' === $request->get_param( 'group_by' ) ? 'category' : 'month';
	$year     = $request->get_param( 'year' ) ? absint( $request->get_param( 'year' ) ) : (int) current_time( 'Y' );
	$month    = $request->get_param( 'month' ) ? absint( $request->get_param( 'month' ) ) : 0;
	$data     = DB::get_graph_data( $user_id, $group_by, $year, $month );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => array(
				'data'     => $data,
				'group_by' => $group_by,
			),
		)
	);
}
