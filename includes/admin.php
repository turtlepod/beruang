<?php
/**
 * Admin menu and list pages for Mowi.
 *
 * @package Mowi
 */

namespace Mowi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Admin slug and capability. */
const ADMIN_SLUG = 'mowi';
const ADMIN_CAPABILITY = 'manage_options';

/**
 * Register admin menu and settings hooks. Hooked to init.
 */
function admin_setup() {
	add_action( 'admin_menu', __NAMESPACE__ . '\admin_register_menu' );
	add_action( 'admin_init', __NAMESPACE__ . '\admin_register_settings' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\admin_setup' );

/**
 * Register top-level Mowi menu and submenus (Settings, Transactions, Categories, Budgets, Budget Categories).
 */
function admin_register_menu() {
	add_menu_page(
		__( 'Mowi', 'mowi' ),
		__( 'Mowi', 'mowi' ),
		ADMIN_CAPABILITY,
		ADMIN_SLUG,
		__NAMESPACE__ . '\admin_page_settings',
		'dashicons-money-alt',
		30
	);
	add_submenu_page( ADMIN_SLUG, __( 'Settings', 'mowi' ), __( 'Settings', 'mowi' ), ADMIN_CAPABILITY, ADMIN_SLUG, __NAMESPACE__ . '\admin_page_settings' );
	add_submenu_page( ADMIN_SLUG, __( 'Transactions', 'mowi' ), __( 'Transactions', 'mowi' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-transactions', __NAMESPACE__ . '\admin_page_transactions' );
	add_submenu_page( ADMIN_SLUG, __( 'Categories', 'mowi' ), __( 'Categories', 'mowi' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-categories', __NAMESPACE__ . '\admin_page_categories' );
	add_submenu_page( ADMIN_SLUG, __( 'Budgets', 'mowi' ), __( 'Budgets', 'mowi' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-budgets', __NAMESPACE__ . '\admin_page_budgets' );
	add_submenu_page( ADMIN_SLUG, __( 'Budget Categories', 'mowi' ), __( 'Budget Categories', 'mowi' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-budget-categories', __NAMESPACE__ . '\admin_page_budget_categories' );
}

/**
 * Register admin_post handlers and option settings (currency, decimal/thousands separators).
 */
function admin_register_settings() {
	add_action( 'admin_post_mowi_update_transaction', __NAMESPACE__ . '\admin_handle_update_transaction' );
	add_action( 'admin_post_mowi_update_category', __NAMESPACE__ . '\admin_handle_update_category' );
	add_action( 'admin_post_mowi_update_budget', __NAMESPACE__ . '\admin_handle_update_budget' );
	add_action( 'admin_post_mowi_add_budget_category', __NAMESPACE__ . '\admin_handle_add_budget_category' );
	add_action( 'admin_post_mowi_delete_budget_category', __NAMESPACE__ . '\admin_handle_delete_budget_category' );
	register_setting( 'mowi_settings', 'mowi_currency', array(
		'type' => 'string',
		'default' => 'IDR',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	register_setting( 'mowi_settings', 'mowi_decimal_sep', array(
		'type' => 'string',
		'default' => ',',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	register_setting( 'mowi_settings', 'mowi_thousands_sep', array(
		'type' => 'string',
		'default' => '.',
		'sanitize_callback' => 'sanitize_text_field',
	) );
}

/**
 * Render settings page: currency, number format, and export/import forms.
 */
function admin_page_settings() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	if ( isset( $_POST['mowi_export'] ) && check_admin_referer( 'mowi_export' ) ) {
		admin_handle_export();
		return;
	}
	if ( isset( $_POST['mowi_import'] ) && check_admin_referer( 'mowi_import' ) && ! empty( $_FILES['mowi_import_file']['tmp_name'] ) ) {
		admin_handle_import();
	}
	$currency = get_option( 'mowi_currency', 'IDR' );
	$decimal_sep = get_option( 'mowi_decimal_sep', ',' );
	$thousands_sep = get_option( 'mowi_thousands_sep', '.' );
	settings_errors( 'mowi_import' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mowi Settings', 'mowi' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mowi_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="mowi_currency"><?php esc_html_e( 'Currency', 'mowi' ); ?></label></th>
					<td><input type="text" id="mowi_currency" name="mowi_currency" value="<?php echo esc_attr( $currency ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mowi_decimal_sep"><?php esc_html_e( 'Decimal separator', 'mowi' ); ?></label></th>
					<td><input type="text" id="mowi_decimal_sep" name="mowi_decimal_sep" value="<?php echo esc_attr( $decimal_sep ); ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mowi_thousands_sep"><?php esc_html_e( 'Thousands separator', 'mowi' ); ?></label></th>
					<td><input type="text" id="mowi_thousands_sep" name="mowi_thousands_sep" value="<?php echo esc_attr( $thousands_sep ); ?>" maxlength="2" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr />
		<h2><?php esc_html_e( 'Export / Import', 'mowi' ); ?></h2>
		<p>
			<form method="post" style="display:inline;">
				<?php wp_nonce_field( 'mowi_export' ); ?>
				<button type="submit" name="mowi_export" class="button"><?php esc_html_e( 'Export my data (JSON)', 'mowi' ); ?></button>
			</form>
		</p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'mowi_import' ); ?>
			<p>
				<input type="file" name="mowi_import_file" accept=".json" />
				<button type="submit" name="mowi_import" class="button"><?php esc_html_e( 'Import from JSON', 'mowi' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Export current user's data as JSON and send download response.
 *
 * Exits after sending headers and output.
 */
function admin_handle_export() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	$categories = DB::get_categories_flat( $user_id, false );
	$transactions = DB::get_transactions( $user_id, array( 'per_page' => 99999 ) );
	$budgets = DB::get_budgets( $user_id );
	$data = array(
		'version' => 1,
		'exported' => current_time( 'c' ),
		'user_id' => $user_id,
		'categories' => $categories,
		'transactions' => $transactions['items'],
		'budgets' => $budgets,
	);
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="mowi-export-' . date( 'Y-m-d' ) . '.json"' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * Import data from uploaded JSON file for current user.
 *
 * Handles categories (with ID mapping), transactions, and budgets.
 * Sets success or error via add_settings_error().
 */
function admin_handle_import() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	$raw = file_get_contents( $_FILES['mowi_import_file']['tmp_name'] );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['categories'] ) && empty( $data['transactions'] ) && empty( $data['budgets'] ) ) {
		add_settings_error( 'mowi_import', 'mowi_import', __( 'Invalid or empty import file.', 'mowi' ), 'error' );
		return;
	}
	$map_cat = array();
	foreach ( $data['categories'] ?? array() as $cat ) {
		$old_id = isset( $cat['id'] ) ? (int) $cat['id'] : 0;
		$parent_id = isset( $cat['parent_id'] ) ? (int) $cat['parent_id'] : 0;
		if ( $parent_id && isset( $map_cat[ $parent_id ] ) ) {
			$parent_id = $map_cat[ $parent_id ];
		}
		$new_id = DB::save_category( $user_id, array( 'name' => $cat['name'] ?? '', 'parent_id' => $parent_id, 'sort_order' => $cat['sort_order'] ?? 0 ), 0 );
		if ( $new_id && $old_id ) {
			$map_cat[ $old_id ] = $new_id;
		}
	}
	foreach ( $data['transactions'] ?? array() as $tx ) {
		$cat_id = isset( $tx['category_id'] ) ? (int) $tx['category_id'] : 0;
		if ( $cat_id && isset( $map_cat[ $cat_id ] ) ) {
			$cat_id = $map_cat[ $cat_id ];
		}
		DB::insert_transaction( $user_id, array(
			'date' => $tx['date'] ?? '',
			'time' => $tx['time'] ?? null,
			'description' => $tx['description'] ?? '',
			'category_id' => $cat_id,
			'amount' => $tx['amount'] ?? 0,
			'type' => ( $tx['type'] ?? 'expense' ) === 'income' ? 'income' : 'expense',
		) );
	}
	foreach ( $data['budgets'] ?? array() as $budget ) {
		$cat_ids = array();
		if ( ! empty( $budget['category_ids'] ) ) {
			foreach ( $budget['category_ids'] as $cid ) {
				if ( isset( $map_cat[ (int) $cid ] ) ) {
					$cat_ids[] = $map_cat[ (int) $cid ];
				}
			}
		}
		DB::save_budget( $user_id, array( 'name' => $budget['name'] ?? '', 'target_amount' => $budget['target_amount'] ?? 0, 'type' => ( $budget['type'] ?? 'monthly' ) === 'yearly' ? 'yearly' : 'monthly', 'category_ids' => $cat_ids ), 0 );
	}
	add_settings_error( 'mowi_import', 'mowi_import', __( 'Import completed.', 'mowi' ), 'success' );
}

/**
 * Handle admin-post form submission to update a transaction.
 *
 * Expects POST: mowi_tx_id, mowi_date, mowi_time, mowi_description,
 * mowi_category_id, mowi_amount, mowi_type. Redirects on success or error.
 */
function admin_handle_update_transaction() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	check_admin_referer( 'mowi_edit_transaction' );
	$id = isset( $_POST['mowi_tx_id'] ) ? absint( $_POST['mowi_tx_id'] ) : 0;
	if ( ! $id ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-transactions', 'mowi_error' => 'invalid' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$existing = DB::get_transaction_by_id( $id );
	if ( ! $existing ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-transactions', 'mowi_error' => 'notfound' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$date = isset( $_POST['mowi_date'] ) ? sanitize_text_field( $_POST['mowi_date'] ) : $existing['date'];
	$time = isset( $_POST['mowi_time'] ) ? sanitize_text_field( $_POST['mowi_time'] ) : null;
	if ( $time === '' ) {
		$time = null;
	}
	$description = isset( $_POST['mowi_description'] ) ? sanitize_textarea_field( $_POST['mowi_description'] ) : '';
	$category_id = isset( $_POST['mowi_category_id'] ) ? absint( $_POST['mowi_category_id'] ) : 0;
	$amount = isset( $_POST['mowi_amount'] ) ? floatval( $_POST['mowi_amount'] ) : 0;
	$type = isset( $_POST['mowi_type'] ) && $_POST['mowi_type'] === 'income' ? 'income' : 'expense';
	DB::update_transaction( $id, array( 'date' => $date, 'time' => $time, 'description' => $description, 'category_id' => $category_id, 'amount' => $amount, 'type' => $type ) );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-transactions', 'mowi_updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle admin-post form submission to update a category.
 *
 * Expects POST: mowi_cat_id, mowi_cat_user_id, mowi_cat_name, mowi_cat_parent.
 * Redirects on success or error.
 */
function admin_handle_update_category() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	check_admin_referer( 'mowi_edit_category' );
	$id = isset( $_POST['mowi_cat_id'] ) ? absint( $_POST['mowi_cat_id'] ) : 0;
	$user_id = isset( $_POST['mowi_cat_user_id'] ) ? absint( $_POST['mowi_cat_user_id'] ) : 0;
	if ( ! $id || ! $user_id ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-categories', 'mowi_error' => 'invalid' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$existing = DB::get_category_by_id( $id );
	if ( ! $existing || (int) $existing['user_id'] !== $user_id ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_id, 'mowi_error' => 'notfound' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$name = isset( $_POST['mowi_cat_name'] ) ? sanitize_text_field( $_POST['mowi_cat_name'] ) : '';
	$parent_id = isset( $_POST['mowi_cat_parent'] ) ? absint( $_POST['mowi_cat_parent'] ) : 0;
	if ( $name === '' ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_id, 'edit' => $id, 'mowi_error' => 'name' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	DB::save_category( $user_id, array( 'name' => $name, 'parent_id' => $parent_id ), $id );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_id, 'mowi_updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle admin-post form submission to update a budget.
 *
 * Expects POST: mowi_budget_id, mowi_budget_user_id, mowi_budget_name,
 * mowi_budget_target, mowi_budget_type, mowi_budget_categories[].
 * Redirects on success or error.
 */
function admin_handle_update_budget() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	check_admin_referer( 'mowi_edit_budget' );
	$id = isset( $_POST['mowi_budget_id'] ) ? absint( $_POST['mowi_budget_id'] ) : 0;
	$user_id = isset( $_POST['mowi_budget_user_id'] ) ? absint( $_POST['mowi_budget_user_id'] ) : 0;
	if ( ! $id || ! $user_id ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budgets', 'mowi_error' => 'invalid' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$existing = DB::get_budget_by_id( $id );
	if ( ! $existing || (int) $existing['user_id'] !== $user_id ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budgets', 'user_id' => $user_id, 'mowi_error' => 'notfound' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$name = isset( $_POST['mowi_budget_name'] ) ? sanitize_text_field( $_POST['mowi_budget_name'] ) : '';
	$target_amount = isset( $_POST['mowi_budget_target'] ) ? floatval( $_POST['mowi_budget_target'] ) : 0;
	$type = isset( $_POST['mowi_budget_type'] ) && $_POST['mowi_budget_type'] === 'yearly' ? 'yearly' : 'monthly';
	$category_ids = ! empty( $_POST['mowi_budget_categories'] ) && is_array( $_POST['mowi_budget_categories'] ) ? array_map( 'absint', $_POST['mowi_budget_categories'] ) : array();
	if ( $name === '' ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budgets', 'user_id' => $user_id, 'edit' => $id, 'mowi_error' => 'name' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	DB::save_budget( $user_id, array( 'name' => $name, 'target_amount' => $target_amount, 'type' => $type, 'category_ids' => $category_ids ), $id );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budgets', 'user_id' => $user_id, 'mowi_updated' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle admin-post form to add a budget–category link.
 *
 * Expects POST: mowi_bc_budget_id, mowi_bc_category_id.
 * Redirects on success or error.
 */
function admin_handle_add_budget_category() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	check_admin_referer( 'mowi_add_budget_category' );
	$budget_id = isset( $_POST['mowi_bc_budget_id'] ) ? absint( $_POST['mowi_bc_budget_id'] ) : 0;
	$category_id = isset( $_POST['mowi_bc_category_id'] ) ? absint( $_POST['mowi_bc_category_id'] ) : 0;
	if ( $budget_id < 1 || $category_id < 1 ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budget-categories', 'mowi_error' => 'invalid' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	DB::insert_budget_category( $budget_id, $category_id );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budget-categories', 'mowi_added' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handle GET request to remove a budget–category link.
 *
 * Expects GET: _wpnonce, budget_id, category_id. Optionally user_id for redirect.
 * Redirects on success or error.
 */
function admin_handle_delete_budget_category() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mowi' ) );
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'mowi_delete_bc' ) ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'mowi-budget-categories' ), admin_url( 'admin.php' ) ) );
		exit;
	}
	$budget_id = isset( $_GET['budget_id'] ) ? absint( $_GET['budget_id'] ) : 0;
	$category_id = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
	if ( $budget_id > 0 && $category_id > 0 ) {
		DB::delete_budget_category( $budget_id, $category_id );
	}
	$redirect = array( 'page' => 'mowi-budget-categories', 'mowi_deleted' => '1' );
	if ( ! empty( $_GET['user_id'] ) ) {
		$redirect['user_id'] = absint( $_GET['user_id'] );
	}
	wp_safe_redirect( add_query_arg( $redirect, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Render admin transactions list page.
 *
 * Supports user filter, pagination, and inline edit form.
 */
function admin_page_transactions() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	global $wpdb;
	$table = DB::table_transaction();
	$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row = $edit_id ? DB::get_transaction_by_id( $edit_id ) : null;
	if ( $edit_row ) {
		$edit_categories = DB::get_categories_flat( (int) $edit_row['user_id'], true );
	} else {
		$edit_id = 0;
		$edit_row = null;
		$edit_categories = array();
	}
	$where = '1=1';
	$values = array();
	if ( $user_filter > 0 ) {
		$where .= ' AND user_id = %d';
		$values[] = $user_filter;
	}
	$per_page = 20;
	$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$offset = ( $page - 1 ) * $per_page;
	$total = (int) $wpdb->get_var( $values ? $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE $where", $values ) : "SELECT COUNT(*) FROM $table WHERE $where" );
	$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY date DESC, time DESC, id DESC LIMIT %d OFFSET %d", array_merge( $values, array( $per_page, $offset ) ) ), ARRAY_A );
	$currency = get_option( 'mowi_currency', 'IDR' );
	$dec = get_option( 'mowi_decimal_sep', ',' );
	$thou = get_option( 'mowi_thousands_sep', '.' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Transactions', 'mowi' ); ?></h1>
		<?php
		if ( isset( $_GET['mowi_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Transaction updated.', 'mowi' ) . '</p></div>';
		}
		if ( isset( $_GET['mowi_error'] ) && $_GET['mowi_error'] === 'notfound' ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Transaction not found.', 'mowi' ) . '</p></div>';
		}
		if ( $edit_row ) {
			?>
			<div class="mowi-admin-edit-transaction" style="margin-bottom:1.5em;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
				<h2><?php esc_html_e( 'Edit transaction', 'mowi' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mowi_update_transaction" />
					<?php wp_nonce_field( 'mowi_edit_transaction' ); ?>
					<input type="hidden" name="mowi_tx_id" value="<?php echo (int) $edit_row['id']; ?>" />
					<table class="form-table">
						<tr><th scope="row"><?php esc_html_e( 'User ID', 'mowi' ); ?></th><td><input type="text" value="<?php echo esc_attr( $edit_row['user_id'] ); ?>" readonly class="regular-text" /></td></tr>
						<tr><th scope="row"><label for="mowi_edit_date"><?php esc_html_e( 'Date', 'mowi' ); ?></label></th><td><input type="date" id="mowi_edit_date" name="mowi_date" value="<?php echo esc_attr( $edit_row['date'] ); ?>" required /></td></tr>
						<tr><th scope="row"><label for="mowi_edit_time"><?php esc_html_e( 'Time', 'mowi' ); ?></label></th><td><input type="time" id="mowi_edit_time" name="mowi_time" value="<?php echo esc_attr( $edit_row['time'] ?? '' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="mowi_edit_description"><?php esc_html_e( 'Description', 'mowi' ); ?></label></th><td><input type="text" id="mowi_edit_description" name="mowi_description" value="<?php echo esc_attr( $edit_row['description'] ); ?>" class="large-text" /></td></tr>
						<tr><th scope="row"><label for="mowi_edit_category"><?php esc_html_e( 'Category', 'mowi' ); ?></label></th>
						<td><select id="mowi_edit_category" name="mowi_category_id">
							<option value="0" <?php selected( (int) $edit_row['category_id'], 0 ); ?>><?php esc_html_e( 'Uncategorized', 'mowi' ); ?></option>
							<?php foreach ( $edit_categories as $c ) {
								$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
								echo '<option value="' . esc_attr( $c['id'] ) . '" ' . selected( (int) $edit_row['category_id'], (int) $c['id'], false ) . '>' . esc_html( $indent . $c['name'] ) . '</option>';
							} ?>
						</select></td></tr>
						<tr><th scope="row"><label for="mowi_edit_amount"><?php esc_html_e( 'Amount', 'mowi' ); ?></label></th><td><input type="number" id="mowi_edit_amount" name="mowi_amount" step="0.01" min="0" value="<?php echo esc_attr( $edit_row['amount'] ); ?>" required /> <?php echo esc_html( $currency ); ?></td></tr>
						<tr><th scope="row"><label for="mowi_edit_type"><?php esc_html_e( 'Type', 'mowi' ); ?></label></th>
						<td><select id="mowi_edit_type" name="mowi_type">
							<option value="expense" <?php selected( $edit_row['type'], 'expense' ); ?>><?php esc_html_e( 'Expense', 'mowi' ); ?></option>
							<option value="income" <?php selected( $edit_row['type'], 'income' ); ?>><?php esc_html_e( 'Income', 'mowi' ); ?></option>
						</select></td></tr>
					</table>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'mowi' ); ?></button>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mowi-transactions', 'user_id' => $user_filter ?: null ), admin_url( 'admin.php' ) ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'mowi' ); ?></a></p>
				</form>
			</div>
			<?php
		}
		?>
		<form method="get" class="mowi-admin-filter">
			<input type="hidden" name="page" value="mowi-transactions" />
			<label><?php esc_html_e( 'User ID', 'mowi' ); ?> <input type="number" name="user_id" value="<?php echo $user_filter ? esc_attr( $user_filter ) : ''; ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mowi' ); ?></button>
		</form>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr>
				<th><?php esc_html_e( 'ID', 'mowi' ); ?></th><th><?php esc_html_e( 'User ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Date', 'mowi' ); ?></th><th><?php esc_html_e( 'Time', 'mowi' ); ?></th>
				<th><?php esc_html_e( 'Description', 'mowi' ); ?></th><th><?php esc_html_e( 'Category ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Amount', 'mowi' ); ?></th><th><?php esc_html_e( 'Type', 'mowi' ); ?></th><th><?php esc_html_e( 'Actions', 'mowi' ); ?></th>
			</tr></thead>
			<tbody>
			<?php
			if ( empty( $items ) ) {
				echo '<tr><td colspan="9">' . esc_html__( 'No transactions.', 'mowi' ) . '</td></tr>';
			} else {
				foreach ( $items as $row ) {
					$amount = number_format( (float) $row['amount'], $dec === '.' ? 2 : 0, $dec, $thou );
					$edit_url = add_query_arg( array( 'page' => 'mowi-transactions', 'edit' => $row['id'], 'user_id' => $user_filter ?: null ), admin_url( 'admin.php' ) );
					echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td>' . esc_html( $row['date'] ) . '</td><td>' . esc_html( $row['time'] ?? '—' ) . '</td><td>' . esc_html( $row['description'] ) . '</td><td>' . esc_html( $row['category_id'] ) . '</td><td>' . esc_html( $amount ) . ' ' . esc_html( $currency ) . '</td><td>' . esc_html( $row['type'] ) . '</td><td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'mowi' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
		<?php
		if ( $total > $per_page ) {
			echo '<p class="tablenav">' . esc_html__( 'Total:', 'mowi' ) . ' ' . (int) $total . ' | ';
			echo paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => ceil( $total / $per_page ) ) );
			echo '</p>';
		}
		?>
	</div>
	<?php
}

/**
 * Render admin categories list page.
 *
 * Supports user filter, add form, edit form, and delete links.
 */
function admin_page_categories() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id();
	if ( $user_filter < 1 ) {
		$user_filter = get_current_user_id();
	}
	$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row = $edit_id ? DB::get_category_by_id( $edit_id ) : null;
	if ( $edit_row && (int) $edit_row['user_id'] !== $user_filter ) {
		$edit_row = null;
		$edit_id = 0;
	}
	$categories = DB::get_categories_flat( $user_filter, true );
	$message = '';
	if ( isset( $_GET['mowi_updated'] ) ) {
		$message = __( 'Category updated.', 'mowi' );
	}
	if ( isset( $_GET['mowi_error'] ) && $_GET['mowi_error'] === 'notfound' ) {
		$message = __( 'Category not found.', 'mowi' );
	}
	if ( isset( $_POST['mowi_add_category'] ) && check_admin_referer( 'mowi_category' ) && $user_filter ) {
		$name = sanitize_text_field( $_POST['mowi_cat_name'] ?? '' );
		$parent_id = absint( $_POST['mowi_cat_parent'] ?? 0 );
		if ( $name !== '' ) {
			DB::save_category( $user_filter, array( 'name' => $name, 'parent_id' => $parent_id ), 0 );
			$message = __( 'Category added.', 'mowi' );
			$categories = DB::get_categories_flat( $user_filter, true );
		}
	}
	if ( isset( $_GET['delete'] ) && check_admin_referer( 'mowi_delete_cat_' . absint( $_GET['delete'] ) ) ) {
		DB::delete_category( $user_filter, absint( $_GET['delete'] ) );
		$message = __( 'Category deleted.', 'mowi' );
		$categories = DB::get_categories_flat( $user_filter, true );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Categories', 'mowi' ); ?></h1>
		<?php if ( $message ) { echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['mowi_error'] ) && $_GET['mowi_error'] === 'name' ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Name is required.', 'mowi' ) . '</p></div>'; } ?>
		<form method="get" class="mowi-admin-filter">
			<input type="hidden" name="page" value="mowi-categories" />
			<label><?php esc_html_e( 'User ID', 'mowi' ); ?> <input type="number" name="user_id" value="<?php echo esc_attr( $user_filter ); ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mowi' ); ?></button>
		</form>
		<?php if ( $edit_row ) { ?>
		<div class="mowi-admin-edit-box" style="margin:1em 0;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
			<h2><?php esc_html_e( 'Edit category', 'mowi' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mowi_update_category" />
				<?php wp_nonce_field( 'mowi_edit_category' ); ?>
				<input type="hidden" name="mowi_cat_id" value="<?php echo (int) $edit_row['id']; ?>" />
				<input type="hidden" name="mowi_cat_user_id" value="<?php echo (int) $edit_row['user_id']; ?>" />
				<table class="form-table">
					<tr><th scope="row"><label for="mowi_edit_cat_name"><?php esc_html_e( 'Name', 'mowi' ); ?></label></th><td><input type="text" id="mowi_edit_cat_name" name="mowi_cat_name" value="<?php echo esc_attr( $edit_row['name'] ); ?>" class="regular-text" required /></td></tr>
					<tr><th scope="row"><label for="mowi_edit_cat_parent"><?php esc_html_e( 'Parent', 'mowi' ); ?></label></th><td><select id="mowi_edit_cat_parent" name="mowi_cat_parent"><option value="0">—</option>
						<?php foreach ( $categories as $c ) {
							if ( (int) $c['id'] === (int) $edit_row['id'] ) { continue; }
							$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
							echo '<option value="' . esc_attr( $c['id'] ) . '" ' . selected( (int) $edit_row['parent_id'], (int) $c['id'], false ) . '>' . esc_html( $indent . $c['name'] ) . '</option>';
						} ?>
					</select></td></tr>
				</table>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'mowi' ); ?></button>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_filter ), admin_url( 'admin.php' ) ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'mowi' ); ?></a></p>
			</form>
		</div>
		<?php } ?>
		<form method="post" style="margin:1em 0;">
			<?php wp_nonce_field( 'mowi_category' ); ?>
			<label><?php esc_html_e( 'Name', 'mowi' ); ?> <input type="text" name="mowi_cat_name" required /></label>
			<label><?php esc_html_e( 'Parent', 'mowi' ); ?> <select name="mowi_cat_parent"><option value="0">—</option>
				<?php foreach ( $categories as $c ) { ?><option value="<?php echo esc_attr( $c['id'] ); ?>"><?php echo esc_html( str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) ) . $c['name'] ); ?></option><?php } ?>
			</select></label>
			<button type="submit" name="mowi_add_category" class="button"><?php esc_html_e( 'Add category', 'mowi' ); ?></button>
		</form>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'mowi' ); ?></th><th><?php esc_html_e( 'User ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Name', 'mowi' ); ?></th><th><?php esc_html_e( 'Parent ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Actions', 'mowi' ); ?></th></tr></thead>
			<tbody>
			<?php
			if ( empty( $categories ) ) {
				echo '<tr><td colspan="5">' . esc_html__( 'No categories.', 'mowi' ) . '</td></tr>';
			} else {
				foreach ( $categories as $row ) {
					$edit_url = add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_filter, 'edit' => $row['id'] ), admin_url( 'admin.php' ) );
					$del_url = wp_nonce_url( add_query_arg( array( 'page' => 'mowi-categories', 'user_id' => $user_filter, 'delete' => $row['id'] ) ), 'mowi_delete_cat_' . $row['id'] );
					echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td>' . esc_html( str_repeat( '— ', (int) ( $row['depth'] ?? 0 ) ) . $row['name'] ) . '</td><td>' . esc_html( $row['parent_id'] ) . '</td><td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'mowi' ) . '</a> | <a href="' . esc_url( $del_url ) . '" class="submitdelete">' . esc_html__( 'Delete', 'mowi' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render admin budgets list page.
 *
 * Supports user filter and inline edit form with category checkboxes.
 */
function admin_page_budgets() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id();
	if ( $user_filter < 1 ) {
		$user_filter = get_current_user_id();
	}
	$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row = $edit_id ? DB::get_budget_by_id( $edit_id ) : null;
	if ( $edit_row && (int) $edit_row['user_id'] !== $user_filter ) {
		$edit_row = null;
		$edit_id = 0;
	}
	$budgets = DB::get_budgets( $user_filter );
	$edit_categories = $edit_row ? DB::get_categories_flat( $user_filter, true ) : array();
	$currency = get_option( 'mowi_currency', 'IDR' );
	$dec = get_option( 'mowi_decimal_sep', ',' );
	$thou = get_option( 'mowi_thousands_sep', '.' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Budgets', 'mowi' ); ?></h1>
		<?php if ( isset( $_GET['mowi_updated'] ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Budget updated.', 'mowi' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['mowi_error'] ) && $_GET['mowi_error'] === 'notfound' ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Budget not found.', 'mowi' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['mowi_error'] ) && $_GET['mowi_error'] === 'name' ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Name is required.', 'mowi' ) . '</p></div>'; } ?>
		<form method="get" class="mowi-admin-filter">
			<input type="hidden" name="page" value="mowi-budgets" />
			<label><?php esc_html_e( 'User ID', 'mowi' ); ?> <input type="number" name="user_id" value="<?php echo esc_attr( $user_filter ); ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mowi' ); ?></button>
		</form>
		<?php if ( $edit_row ) { ?>
		<div class="mowi-admin-edit-box" style="margin:1em 0;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
			<h2><?php esc_html_e( 'Edit budget', 'mowi' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mowi_update_budget" />
				<?php wp_nonce_field( 'mowi_edit_budget' ); ?>
				<input type="hidden" name="mowi_budget_id" value="<?php echo (int) $edit_row['id']; ?>" />
				<input type="hidden" name="mowi_budget_user_id" value="<?php echo (int) $edit_row['user_id']; ?>" />
				<table class="form-table">
					<tr><th scope="row"><label for="mowi_edit_budget_name"><?php esc_html_e( 'Name', 'mowi' ); ?></label></th><td><input type="text" id="mowi_edit_budget_name" name="mowi_budget_name" value="<?php echo esc_attr( $edit_row['name'] ); ?>" class="regular-text" required /></td></tr>
					<tr><th scope="row"><label for="mowi_edit_budget_target"><?php esc_html_e( 'Target', 'mowi' ); ?></label></th><td><input type="number" id="mowi_edit_budget_target" name="mowi_budget_target" step="0.01" min="0" value="<?php echo esc_attr( $edit_row['target_amount'] ); ?>" required /> <?php echo esc_html( $currency ); ?></td></tr>
					<tr><th scope="row"><label for="mowi_edit_budget_type"><?php esc_html_e( 'Type', 'mowi' ); ?></label></th><td><select id="mowi_edit_budget_type" name="mowi_budget_type"><option value="monthly" <?php selected( $edit_row['type'], 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'mowi' ); ?></option><option value="yearly" <?php selected( $edit_row['type'], 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'mowi' ); ?></option></select></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Categories', 'mowi' ); ?></th><td><fieldset>
						<?php foreach ( $edit_categories as $c ) {
							$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
							$checked = in_array( (int) $c['id'], $edit_row['category_ids'], true );
							echo '<label style="display:block;"><input type="checkbox" name="mowi_budget_categories[]" value="' . esc_attr( $c['id'] ) . '" ' . ( $checked ? 'checked' : '' ) . ' /> ' . esc_html( $indent . $c['name'] ) . '</label>';
						} ?>
					</fieldset></td></tr>
				</table>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'mowi' ); ?></button>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mowi-budgets', 'user_id' => $user_filter ), admin_url( 'admin.php' ) ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'mowi' ); ?></a></p>
			</form>
		</div>
		<?php } ?>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'mowi' ); ?></th><th><?php esc_html_e( 'User ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Name', 'mowi' ); ?></th><th><?php esc_html_e( 'Target', 'mowi' ); ?></th><th><?php esc_html_e( 'Type', 'mowi' ); ?></th><th><?php esc_html_e( 'Category IDs', 'mowi' ); ?></th><th><?php esc_html_e( 'Actions', 'mowi' ); ?></th></tr></thead>
			<tbody>
			<?php
			if ( empty( $budgets ) ) {
				echo '<tr><td colspan="7">' . esc_html__( 'No budgets.', 'mowi' ) . '</td></tr>';
			} else {
				foreach ( $budgets as $row ) {
					$target = number_format( (float) $row['target_amount'], $dec === '.' ? 2 : 0, $dec, $thou );
					$edit_url = add_query_arg( array( 'page' => 'mowi-budgets', 'user_id' => $user_filter, 'edit' => $row['id'] ), admin_url( 'admin.php' ) );
					echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td>' . esc_html( $row['name'] ) . '</td><td>' . esc_html( $target ) . ' ' . esc_html( $currency ) . '</td><td>' . esc_html( $row['type'] ) . '</td><td>' . esc_html( implode( ', ', $row['category_ids'] ) ) . '</td><td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'mowi' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render admin budget–category links page.
 *
 * Supports user filter, add-link form, and delete links.
 */
function admin_page_budget_categories() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	global $wpdb;
	$table = DB::table_budget_category();
	$b_table = DB::table_budget();
	$c_table = DB::table_category();
	$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	$where = '1=1';
	$values = array();
	if ( $user_filter > 0 ) {
		$where = "b.user_id = %d";
		$values[] = $user_filter;
	}
	$sql = "SELECT bc.budget_id, bc.category_id, b.name AS budget_name, b.user_id, c.name AS category_name FROM $table bc JOIN $b_table b ON b.id = bc.budget_id LEFT JOIN $c_table c ON c.id = bc.category_id AND c.user_id = b.user_id WHERE $where ORDER BY b.name, c.name";
	$items = $values ? $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
	$budgets_for_user = $user_filter > 0 ? DB::get_budgets( $user_filter ) : array();
	$categories_for_user = $user_filter > 0 ? DB::get_categories_flat( $user_filter, true ) : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Budget Categories', 'mowi' ); ?></h1>
		<?php if ( isset( $_GET['mowi_added'] ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Link added.', 'mowi' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['mowi_deleted'] ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Link removed.', 'mowi' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['mowi_error'] ) ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid or missing budget/category.', 'mowi' ) . '</p></div>'; } ?>
		<form method="get" class="mowi-admin-filter">
			<input type="hidden" name="page" value="mowi-budget-categories" />
			<label><?php esc_html_e( 'User ID', 'mowi' ); ?> <input type="number" name="user_id" value="<?php echo $user_filter ? esc_attr( $user_filter ) : ''; ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mowi' ); ?></button>
		</form>
		<?php if ( $user_filter > 0 ) { ?>
		<h2><?php esc_html_e( 'Add link', 'mowi' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1em 0;">
			<input type="hidden" name="action" value="mowi_add_budget_category" />
			<?php wp_nonce_field( 'mowi_add_budget_category' ); ?>
			<label><?php esc_html_e( 'Budget', 'mowi' ); ?> <select name="mowi_bc_budget_id" required><option value="">—</option>
				<?php foreach ( $budgets_for_user as $b ) { echo '<option value="' . esc_attr( $b['id'] ) . '">' . esc_html( $b['name'] ) . '</option>'; } ?>
			</select></label>
			<label><?php esc_html_e( 'Category', 'mowi' ); ?> <select name="mowi_bc_category_id" required><option value="">—</option>
				<?php foreach ( $categories_for_user as $c ) {
					$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
					echo '<option value="' . esc_attr( $c['id'] ) . '">' . esc_html( $indent . $c['name'] ) . '</option>';
				} ?>
			</select></label>
			<button type="submit" class="button"><?php esc_html_e( 'Add link', 'mowi' ); ?></button>
		</form>
		<?php } ?>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'Budget ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Budget name', 'mowi' ); ?></th><th><?php esc_html_e( 'Category ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Category name', 'mowi' ); ?></th><th><?php esc_html_e( 'User ID', 'mowi' ); ?></th><th><?php esc_html_e( 'Actions', 'mowi' ); ?></th></tr></thead>
			<tbody>
			<?php
			if ( empty( $items ) ) {
				echo '<tr><td colspan="6">' . esc_html__( 'No budget-category links.', 'mowi' ) . '</td></tr>';
			} else {
				foreach ( $items as $row ) {
					$delete_args = array( 'action' => 'mowi_delete_budget_category', 'budget_id' => $row['budget_id'], 'category_id' => $row['category_id'] );
					if ( $user_filter > 0 ) {
						$delete_args['user_id'] = $user_filter;
					}
					$delete_url = wp_nonce_url( add_query_arg( $delete_args, admin_url( 'admin-post.php' ) ), 'mowi_delete_bc' );
					echo '<tr><td>' . esc_html( $row['budget_id'] ) . '</td><td>' . esc_html( $row['budget_name'] ?? '—' ) . '</td><td>' . esc_html( $row['category_id'] ) . '</td><td>' . esc_html( $row['category_name'] ?? __( 'Uncategorized', 'mowi' ) ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td><a href="' . esc_url( $delete_url ) . '" class="submitdelete">' . esc_html__( 'Delete', 'mowi' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
}
