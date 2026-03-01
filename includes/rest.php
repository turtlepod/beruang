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
				'page'        => array(
					'default' => 1,
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
								return is_numeric( $v ) && $v > 0; },
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
													return is_numeric( $v ) && $v > 0; },
				),
				'date'        => array( 'type' => 'string' ),
				'time'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
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
								return is_numeric( $v ) && $v > 0; },
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
								return is_numeric( $v ) && $v > 0; },
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
								return is_numeric( $v ) && $v > 0; },
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
								return is_numeric( $v ) && $v > 0; },
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
	$category_id = isset( $body['category_id'] ) ? absint( $body['category_id'] ) : 0;
	$amount      = isset( $body['amount'] ) ? floatval( $body['amount'] ) : 0;
	$type        = isset( $body['type'] ) && 'income' === $body['type'] ? 'income' : 'expense';

	if ( '' === $time ) {
		$time = null;
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
	$page        = max( 1, absint( $request->get_param( 'page' ) ) );

	if ( $category_id > 0 && ! DB::get_category_for_user( $user_id, $category_id ) ) {
		$category_id = '';
	}

	$args   = array(
		'year'        => $year,
		'search'      => $search,
		'category_id' => $category_id,
		'page'        => $page,
		'per_page'    => 9999,
	);
	$result = DB::get_transactions( $user_id, $args );
	return rest_ensure_response(
		array(
			'success' => true,
			'data'    => $result,
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

	$data = array(
		'date'        => isset( $body['date'] ) ? sanitize_text_field( $body['date'] ) : $existing['date'],
		'time'        => isset( $body['time'] ) ? sanitize_text_field( $body['time'] ) : null,
		'description' => isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : $existing['description'],
		'category_id' => isset( $body['category_id'] ) ? absint( $body['category_id'] ) : (int) ( $existing['category_id'] ?? 0 ),
		'amount'      => isset( $body['amount'] ) ? floatval( $body['amount'] ) : (float) ( $existing['amount'] ?? 0 ),
		'type'        => isset( $body['type'] ) && 'income' === $body['type'] ? 'income' : 'expense',
	);
	if ( '' === $data['time'] ) {
		$data['time'] = null;
	}

	$cat_id = (int) $data['category_id'];
	if ( $cat_id > 0 && ! DB::get_category_for_user( $user_id, $cat_id ) ) {
		return rest_json_error( new \WP_REST_Response(), __( 'Invalid category.', 'beruang' ), 400 );
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

	foreach ( $budgets as &$b ) {
		$cat_ids = ! empty( $b['category_ids'] ) ? $b['category_ids'] : array();
		if ( 'yearly' === $b['type'] ) {
			$date_from = sprintf( '%04d-01-01', $year );
			$date_to   = sprintf( '%04d-12-31', $year );
		} else {
			$date_from = sprintf( '%04d-%02d-01', $year, $month );
			$date_to   = gmdate( 'Y-m-t', strtotime( $date_from ) );
		}
		$b['spent']    = DB::sum_expenses( $user_id, $date_from, $date_to, $cat_ids );
		$b['progress'] = (float) $b['target_amount'] > 0 ? min( 100, ( $b['spent'] / (float) $b['target_amount'] ) * 100 ) : 0; // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
	}
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
