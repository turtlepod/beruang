<?php
/**
 * Admin menu and list pages for Beruang.
 *
 * GET params are used for filtering/display; form submissions use admin_post with nonce.
 *
 * @package Beruang
 */

namespace Beruang;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET for filters; admin_post forms verified.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Admin slug and capability. */
const ADMIN_SLUG       = 'beruang';
const ADMIN_CAPABILITY = 'manage_options';

/**
 * Register admin menu and settings hooks. Hooked to init.
 */
function admin_setup() {
	add_action( 'admin_menu', __NAMESPACE__ . '\admin_register_menu' );
	add_action( 'admin_init', __NAMESPACE__ . '\admin_register_settings' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_styles' );
	add_action( 'admin_notices', __NAMESPACE__ . '\admin_notice_dist_missing' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\admin_setup' );

/**
 * Show admin notice when dist/ is missing.
 */
function admin_notice_dist_missing() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) || beruang_dist_exists() ) {
		return;
	}
	$message = sprintf(
		/* translators: %s: npm build command */
		__( 'Beruang: Built assets are missing. Run %s in the plugin directory.', 'beruang' ),
		'<code>npm install && npm run build</code>'
	);
	echo '<div class="notice notice-warning"><p>' . wp_kses_post( $message ) . '</p></div>';
}

/**
 * Enqueue admin styles when on Beruang admin pages.
 *
 * @param string $hook Current admin page hook.
 */
function admin_enqueue_styles( $hook ) {
	if ( strpos( $hook, 'beruang' ) === false ) {
		return;
	}
	if ( ! beruang_dist_exists() ) {
		return;
	}
	$admin_css_dist  = BERUANG_PLUGIN_DIR . 'dist/css/admin-style.css';
	$admin_css_asset = BERUANG_PLUGIN_DIR . 'dist/css/admin-style.asset.php';
	if ( ! file_exists( $admin_css_dist ) ) {
		return;
	}
	$deps = array();
	$ver  = BERUANG_VERSION;
	if ( file_exists( $admin_css_asset ) ) {
		$asset = include $admin_css_asset;
		$deps  = $asset['dependencies'] ?? array();
		$ver   = $asset['version'] ?? $ver;
	}
	wp_enqueue_style(
		'beruang-admin',
		BERUANG_PLUGIN_URL . 'dist/css/admin-style.css',
		array_merge( $deps, array( 'list-tables' ) ),
		$ver
	);
}

/**
 * Register top-level Beruang menu and submenus (Settings, Transactions, Categories, Budgets).
 */
function admin_register_menu() {
	add_menu_page(
		__( 'Beruang Budget', 'beruang' ),
		__( 'Beruang Budget', 'beruang' ),
		ADMIN_CAPABILITY,
		ADMIN_SLUG,
		__NAMESPACE__ . '\admin_page_settings',
		'dashicons-money-alt',
		30
	);
	add_submenu_page( ADMIN_SLUG, __( 'Settings', 'beruang' ), __( 'Settings', 'beruang' ), ADMIN_CAPABILITY, ADMIN_SLUG, __NAMESPACE__ . '\admin_page_settings' );
	add_submenu_page( ADMIN_SLUG, __( 'Transactions', 'beruang' ), __( 'Transactions', 'beruang' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-transactions', __NAMESPACE__ . '\admin_page_transactions' );
	add_submenu_page( ADMIN_SLUG, __( 'Categories', 'beruang' ), __( 'Categories', 'beruang' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-categories', __NAMESPACE__ . '\admin_page_categories' );
	add_submenu_page( ADMIN_SLUG, __( 'Budgets', 'beruang' ), __( 'Budgets', 'beruang' ), ADMIN_CAPABILITY, ADMIN_SLUG . '-budgets', __NAMESPACE__ . '\admin_page_budgets' );
}

/**
 * Register admin_post handlers and option settings (currency, decimal/thousands separators).
 */
function admin_register_settings() {
	add_action( 'admin_post_beruang_export', __NAMESPACE__ . '\admin_handle_export' );
	add_action( 'admin_post_beruang_export_csv', __NAMESPACE__ . '\admin_handle_export_csv' );
	add_action( 'admin_post_beruang_update_transaction', __NAMESPACE__ . '\admin_handle_update_transaction' );
	add_action( 'admin_post_beruang_update_category', __NAMESPACE__ . '\admin_handle_update_category' );
	add_action( 'admin_post_beruang_update_budget', __NAMESPACE__ . '\admin_handle_update_budget' );
	register_setting(
		'beruang_settings',
		'beruang_currency',
		array(
			'type'              => 'string',
			'default'           => 'IDR',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_decimal_sep',
		array(
			'type'              => 'string',
			'default'           => ',',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_thousands_sep',
		array(
			'type'              => 'string',
			'default'           => '.',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_decimal_places',
		array(
			'type'              => 'integer',
			'default'           => 2,
			'sanitize_callback' => function ( $v ) {
				$v = absint( $v );
				return $v <= 4 ? $v : 2;
			},
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_pwa_enabled',
		array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => function ( $v ) {
				return ! empty( $v );
			},
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_pwa_app_name',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_pwa_short_name',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	register_setting(
		'beruang_settings',
		'beruang_pwa_theme_color',
		array(
			'type'              => 'string',
			'default'           => '#2271b1',
			'sanitize_callback' => function ( $v ) {
				$sanitized = sanitize_hex_color( $v );
				return ! empty( $sanitized ) ? $sanitized : '#2271b1';
			},
		)
	);
}

/**
 * Render settings page: currency, number format, and export/import forms.
 */
function admin_page_settings() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	if ( isset( $_POST['beruang_import'] ) && check_admin_referer( 'beruang_import' ) && ! empty( $_FILES['beruang_import_file']['tmp_name'] ) ) {
		admin_handle_import();
	}
	$currency        = get_option( 'beruang_currency', 'IDR' );
	$decimal_sep     = get_option( 'beruang_decimal_sep', ',' );
	$thousands_sep   = get_option( 'beruang_thousands_sep', '.' );
	$decimal_places  = (int) get_option( 'beruang_decimal_places', 2 );
	$pwa_enabled     = (bool) get_option( 'beruang_pwa_enabled', false );
	$pwa_app_name    = get_option( 'beruang_pwa_app_name', '' );
	$pwa_short_name  = get_option( 'beruang_pwa_short_name', '' );
	$pwa_theme_color = get_option( 'beruang_pwa_theme_color', '#2271b1' );
	settings_errors( 'beruang_import' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Beruang Budget Settings', 'beruang' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'beruang_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="beruang_currency"><?php esc_html_e( 'Currency', 'beruang' ); ?></label></th>
					<td><input type="text" id="beruang_currency" name="beruang_currency" value="<?php echo esc_attr( $currency ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_decimal_sep"><?php esc_html_e( 'Decimal separator', 'beruang' ); ?></label></th>
					<td><input type="text" id="beruang_decimal_sep" name="beruang_decimal_sep" value="<?php echo esc_attr( $decimal_sep ); ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_thousands_sep"><?php esc_html_e( 'Thousands separator', 'beruang' ); ?></label></th>
					<td><input type="text" id="beruang_thousands_sep" name="beruang_thousands_sep" value="<?php echo esc_attr( $thousands_sep ); ?>" maxlength="2" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_decimal_places"><?php esc_html_e( 'Decimal places', 'beruang' ); ?></label></th>
					<td>
						<select id="beruang_decimal_places" name="beruang_decimal_places">
							<option value="0" <?php selected( $decimal_places, 0 ); ?>><?php esc_html_e( 'None (integers only)', 'beruang' ); ?></option>
							<option value="1" <?php selected( $decimal_places, 1 ); ?>>1</option>
							<option value="2" <?php selected( $decimal_places, 2 ); ?>>2</option>
							<option value="3" <?php selected( $decimal_places, 3 ); ?>>3</option>
							<option value="4" <?php selected( $decimal_places, 4 ); ?>>4</option>
						</select>
						<p class="description"><?php esc_html_e( 'Number of digits after the decimal in amount fields.', 'beruang' ); ?></p>
					</td>
				</tr>
			</table>
			<h2><?php esc_html_e( 'Web app', 'beruang' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Allow visitors to install your site as an app on their device (Add to Home Screen).', 'beruang' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable web app install', 'beruang' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="beruang_pwa_enabled" value="1" <?php checked( $pwa_enabled ); ?> />
							<?php esc_html_e( 'Enable', 'beruang' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_pwa_app_name"><?php esc_html_e( 'App name', 'beruang' ); ?></label></th>
					<td>
						<input type="text" id="beruang_pwa_app_name" name="beruang_pwa_app_name" value="<?php echo esc_attr( $pwa_app_name ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Display name when installed. Leave blank to use site title.', 'beruang' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_pwa_short_name"><?php esc_html_e( 'Short name', 'beruang' ); ?></label></th>
					<td>
						<input type="text" id="beruang_pwa_short_name" name="beruang_pwa_short_name" value="<?php echo esc_attr( $pwa_short_name ); ?>" class="regular-text" maxlength="12" />
						<p class="description"><?php esc_html_e( 'Shown on home screen when space is limited (max 12 chars). Leave blank to auto-truncate app name.', 'beruang' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="beruang_pwa_theme_color"><?php esc_html_e( 'Theme color', 'beruang' ); ?></label></th>
					<td>
						<input type="text" id="beruang_pwa_theme_color" name="beruang_pwa_theme_color" value="<?php echo esc_attr( $pwa_theme_color ); ?>" class="small-text" />
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr />
		<h2><?php esc_html_e( 'Export / Import', 'beruang' ); ?></h2>
		<p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:block;margin-bottom:1em;">
				<input type="hidden" name="action" value="beruang_export" />
				<?php wp_nonce_field( 'beruang_export' ); ?>
				<label><?php esc_html_e( 'User', 'beruang' ); ?>
				<?php
				wp_dropdown_users(
					array(
						'name'     => 'beruang_export_user_id',
						'selected' => get_current_user_id(),
					)
				);
				?>
				</label>
				<button type="submit" name="beruang_export" class="button"><?php esc_html_e( 'Export data (JSON)', 'beruang' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:block;margin-bottom:1em;">
				<input type="hidden" name="action" value="beruang_export_csv" />
				<?php wp_nonce_field( 'beruang_export_csv' ); ?>
				<label><?php esc_html_e( 'User', 'beruang' ); ?>
				<?php
				wp_dropdown_users(
					array(
						'name'     => 'beruang_export_user_id',
						'selected' => get_current_user_id(),
					)
				);
				?>
				</label>
				<button type="submit" name="beruang_export_csv" class="button"><?php esc_html_e( 'Export transactions (CSV)', 'beruang' ); ?></button>
			</form>
		</p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'beruang_import' ); ?>
			<p>
				<input type="file" name="beruang_import_file" accept=".json" />
				<button type="submit" name="beruang_import" class="button"><?php esc_html_e( 'Import from JSON', 'beruang' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Export current user's data as JSON and send download response.
 *
 * Handled via admin_post before any output; exits after sending headers and body.
 */
function admin_handle_export() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	check_admin_referer( 'beruang_export' );
	$user_id = isset( $_POST['beruang_export_user_id'] ) ? absint( $_POST['beruang_export_user_id'] ) : 0;
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	$user         = get_userdata( $user_id );
	$categories   = DB::get_categories_flat( $user_id, false );
	$transactions = DB::get_transactions( $user_id, array( 'per_page' => 99999 ) );
	$budgets      = DB::get_budgets( $user_id );
	$data         = array(
		'version'      => 1,
		'exported'     => current_time( 'c' ),
		'user_id'      => $user_id,
		'user_login'   => $user ? $user->user_login : '',
		'user_email'   => $user ? $user->user_email : '',
		'display_name' => $user ? $user->display_name : '',
		'categories'   => $categories,
		'transactions' => $transactions['items'],
		'budgets'      => $budgets,
	);
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="beruang-export-' . gmdate( 'Y-m-d' ) . '.json"' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}

/**
 * Export current user's transactions as CSV.
 *
 * Handled via admin_post before any output; exits after sending headers and body.
 */
function admin_handle_export_csv() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	check_admin_referer( 'beruang_export_csv' );
	$user_id = isset( $_POST['beruang_export_user_id'] ) ? absint( $_POST['beruang_export_user_id'] ) : 0;
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	$user         = get_userdata( $user_id );
	$user_login   = $user ? $user->user_login : '';
	$user_email   = $user ? $user->user_email : '';
	$display_name = $user ? $user->display_name : '';
	$transactions = DB::get_transactions( $user_id, array( 'per_page' => 99999 ) );
	$items        = $transactions['items'];
	$categories   = DB::get_categories_flat( $user_id, false );
	$cat_names    = array();
	foreach ( $categories as $c ) {
		$cat_names[ (int) $c['id'] ] = $c['name'] ?? '';
	}
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="beruang-transactions-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$output = fopen( 'php://output', 'w' );
	// UTF-8 BOM for Excel compatibility.
	fprintf( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, array( 'id', 'user_id', 'user_login', 'user_email', 'display_name', 'date', 'time', 'description', 'category_id', 'category_name', 'amount', 'type' ) );
	foreach ( $items as $row ) {
		$cat_id   = isset( $row['category_id'] ) ? (int) $row['category_id'] : 0;
		$cat_name = $cat_id && isset( $cat_names[ $cat_id ] ) ? $cat_names[ $cat_id ] : '';
		fputcsv(
			$output,
			array(
				$row['id'] ?? '',
				$row['user_id'] ?? '',
				$user_login,
				$user_email,
				$display_name,
				$row['date'] ?? '',
				$row['time'] ?? '',
				$row['description'] ?? '',
				$row['category_id'] ?? '',
				$cat_name,
				$row['amount'] ?? '',
				$row['type'] ?? '',
			)
		);
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream, no filesystem path.
	fclose( $output );
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
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name validated by is_uploaded_file.
	if ( ! isset( $_FILES['beruang_import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['beruang_import_file']['tmp_name'] ) ) {
		add_settings_error( 'beruang_import', 'beruang_import', __( 'Invalid file upload.', 'beruang' ), 'error' );
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Validated via is_uploaded_file, local file.
	$raw  = file_get_contents( $_FILES['beruang_import_file']['tmp_name'] );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || ( empty( $data['categories'] ) && empty( $data['transactions'] ) && empty( $data['budgets'] ) ) ) {
		add_settings_error( 'beruang_import', 'beruang_import', __( 'Invalid or empty import file.', 'beruang' ), 'error' );
		return;
	}
	$map_cat = array();
	foreach ( $data['categories'] ?? array() as $cat ) {
		$old_id    = isset( $cat['id'] ) ? (int) $cat['id'] : 0;
		$parent_id = isset( $cat['parent_id'] ) ? (int) $cat['parent_id'] : 0;
		if ( $parent_id && isset( $map_cat[ $parent_id ] ) ) {
			$parent_id = $map_cat[ $parent_id ];
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
			$map_cat[ $old_id ] = $new_id;
		}
	}
	foreach ( $data['transactions'] ?? array() as $tx ) {
		$cat_id = isset( $tx['category_id'] ) ? (int) $tx['category_id'] : 0;
		if ( $cat_id && isset( $map_cat[ $cat_id ] ) ) {
			$cat_id = $map_cat[ $cat_id ];
		}
		DB::insert_transaction(
			$user_id,
			array(
				'date'        => $tx['date'] ?? '',
				'time'        => $tx['time'] ?? null,
				'description' => $tx['description'] ?? '',
				'category_id' => $cat_id,
				'amount'      => $tx['amount'] ?? 0,
				'type'        => 'income' === ( $tx['type'] ?? 'expense' ) ? 'income' : 'expense',
			)
		);
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
		DB::save_budget(
			$user_id,
			array(
				'name'          => $budget['name'] ?? '',
				'target_amount' => $budget['target_amount'] ?? 0,
				'type'          => 'yearly' === ( $budget['type'] ?? 'monthly' ) ? 'yearly' : 'monthly',
				'category_ids'  => $cat_ids,
			),
			0
		);
	}
	add_settings_error( 'beruang_import', 'beruang_import', __( 'Import completed.', 'beruang' ), 'success' );
}

/**
 * Handle admin-post form submission to update a transaction.
 *
 * Expects POST: beruang_tx_id, beruang_date, beruang_time, beruang_description,
 * beruang_category_id, beruang_amount, beruang_type. Redirects on success or error.
 */
function admin_handle_update_transaction() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	check_admin_referer( 'beruang_edit_transaction' );
	$id = isset( $_POST['beruang_tx_id'] ) ? absint( $_POST['beruang_tx_id'] ) : 0;
	if ( ! $id ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-transactions',
					'beruang_error' => 'invalid',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$existing = DB::get_transaction_by_id( $id );
	if ( ! $existing ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-transactions',
					'beruang_error' => 'notfound',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$date = isset( $_POST['beruang_date'] ) ? sanitize_text_field( wp_unslash( $_POST['beruang_date'] ) ) : $existing['date'];
	$time = isset( $_POST['beruang_time'] ) ? sanitize_text_field( wp_unslash( $_POST['beruang_time'] ) ) : null;
	if ( '' === $time ) {
		$time = null;
	}
	$description = isset( $_POST['beruang_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['beruang_description'] ) ) : '';
	$category_id = isset( $_POST['beruang_category_id'] ) ? absint( $_POST['beruang_category_id'] ) : 0;
	$amount      = isset( $_POST['beruang_amount'] ) ? floatval( $_POST['beruang_amount'] ) : 0;
	$type        = isset( $_POST['beruang_type'] ) && 'income' === $_POST['beruang_type'] ? 'income' : 'expense';
	DB::update_transaction(
		$id,
		array(
			'date'        => $date,
			'time'        => $time,
			'description' => $description,
			'category_id' => $category_id,
			'amount'      => $amount,
			'type'        => $type,
		)
	);
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'beruang-transactions',
				'beruang_updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Handle admin-post form submission to update a category.
 *
 * Expects POST: beruang_cat_id, beruang_cat_user_id, beruang_cat_name, beruang_cat_parent.
 * Redirects on success or error.
 */
function admin_handle_update_category() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	check_admin_referer( 'beruang_edit_category' );
	$id      = isset( $_POST['beruang_cat_id'] ) ? absint( $_POST['beruang_cat_id'] ) : 0;
	$user_id = isset( $_POST['beruang_cat_user_id'] ) ? absint( $_POST['beruang_cat_user_id'] ) : 0;
	if ( ! $id || ! $user_id ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-categories',
					'beruang_error' => 'invalid',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$existing = DB::get_category_by_id( $id );
	if ( ! $existing || $user_id !== (int) $existing['user_id'] ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-categories',
					'user_id'       => $user_id,
					'beruang_error' => 'notfound',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$name      = isset( $_POST['beruang_cat_name'] ) ? sanitize_text_field( wp_unslash( $_POST['beruang_cat_name'] ) ) : '';
	$parent_id = isset( $_POST['beruang_cat_parent'] ) ? absint( $_POST['beruang_cat_parent'] ) : 0;
	if ( '' === $name ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-categories',
					'user_id'       => $user_id,
					'edit'          => $id,
					'beruang_error' => 'name',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	DB::save_category(
		$user_id,
		array(
			'name'      => $name,
			'parent_id' => $parent_id,
		),
		$id
	);
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'beruang-categories',
				'user_id'         => $user_id,
				'beruang_updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Handle admin-post form submission to update a budget.
 *
 * Expects POST: beruang_budget_id, beruang_budget_user_id, beruang_budget_name,
 * beruang_budget_target, beruang_budget_type, beruang_budget_categories[].
 * Redirects on success or error.
 */
function admin_handle_update_budget() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		wp_die( esc_html__( 'Not allowed.', 'beruang' ) );
	}
	check_admin_referer( 'beruang_edit_budget' );
	$id      = isset( $_POST['beruang_budget_id'] ) ? absint( $_POST['beruang_budget_id'] ) : 0;
	$user_id = isset( $_POST['beruang_budget_user_id'] ) ? absint( $_POST['beruang_budget_user_id'] ) : 0;
	if ( ! $id || ! $user_id ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-budgets',
					'beruang_error' => 'invalid',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$existing = DB::get_budget_by_id( $id );
	if ( ! $existing || $user_id !== (int) $existing['user_id'] ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-budgets',
					'user_id'       => $user_id,
					'beruang_error' => 'notfound',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$name          = isset( $_POST['beruang_budget_name'] ) ? sanitize_text_field( wp_unslash( $_POST['beruang_budget_name'] ) ) : '';
	$target_amount = isset( $_POST['beruang_budget_target'] ) ? floatval( $_POST['beruang_budget_target'] ) : 0;
	$type          = isset( $_POST['beruang_budget_type'] ) && 'yearly' === $_POST['beruang_budget_type'] ? 'yearly' : 'monthly';
	$category_ids  = ! empty( $_POST['beruang_budget_categories'] ) && is_array( $_POST['beruang_budget_categories'] ) ? array_map( 'absint', $_POST['beruang_budget_categories'] ) : array();
	if ( '' === $name ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'beruang-budgets',
					'user_id'       => $user_id,
					'edit'          => $id,
					'beruang_error' => 'name',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	DB::save_budget(
		$user_id,
		array(
			'name'          => $name,
			'target_amount' => $target_amount,
			'type'          => $type,
			'category_ids'  => $category_ids,
		),
		$id
	);
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'beruang-budgets',
				'user_id'         => $user_id,
				'beruang_updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Render admin transactions list page.
 *
 * Supports user filter, pagination, sortable columns, and inline edit form.
 * Uses WP_List_Table for the transactions table.
 */
function admin_page_transactions() {
	if ( ! current_user_can( ADMIN_CAPABILITY ) ) {
		return;
	}
	$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	$edit_id     = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row    = $edit_id ? DB::get_transaction_by_id( $edit_id ) : null;
	if ( $edit_row ) {
		$edit_categories = DB::get_categories_flat( (int) $edit_row['user_id'], true );
	} else {
		$edit_id         = 0;
		$edit_row        = null;
		$edit_categories = array();
	}
	$currency = get_option( 'beruang_currency', 'IDR' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Transactions', 'beruang' ); ?></h1>
		<?php
		if ( isset( $_GET['beruang_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Transaction updated.', 'beruang' ) . '</p></div>';
		}
		if ( isset( $_GET['beruang_error'] ) && 'notfound' === $_GET['beruang_error'] ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Transaction not found.', 'beruang' ) . '</p></div>';
		}
		if ( $edit_row ) {
			?>
			<div class="beruang-admin-edit-transaction" style="margin-bottom:1.5em;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
				<h2><?php esc_html_e( 'Edit transaction', 'beruang' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="beruang_update_transaction" />
					<?php wp_nonce_field( 'beruang_edit_transaction' ); ?>
					<input type="hidden" name="beruang_tx_id" value="<?php echo (int) $edit_row['id']; ?>" />
					<table class="form-table">
						<tr><th scope="row"><?php esc_html_e( 'User ID', 'beruang' ); ?></th><td><input type="text" value="<?php echo esc_attr( $edit_row['user_id'] ); ?>" readonly class="regular-text" /></td></tr>
						<tr><th scope="row"><label for="beruang_edit_date"><?php esc_html_e( 'Date', 'beruang' ); ?></label></th><td><input type="date" id="beruang_edit_date" name="beruang_date" value="<?php echo esc_attr( $edit_row['date'] ); ?>" required /></td></tr>
						<tr><th scope="row"><label for="beruang_edit_time"><?php esc_html_e( 'Time', 'beruang' ); ?></label></th><td><input type="time" id="beruang_edit_time" name="beruang_time" value="<?php echo esc_attr( $edit_row['time'] ?? '' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="beruang_edit_description"><?php esc_html_e( 'Description', 'beruang' ); ?></label></th><td><input type="text" id="beruang_edit_description" name="beruang_description" value="<?php echo esc_attr( $edit_row['description'] ); ?>" class="large-text" /></td></tr>
						<tr><th scope="row"><label for="beruang_edit_category"><?php esc_html_e( 'Category', 'beruang' ); ?></label></th>
						<td><select id="beruang_edit_category" name="beruang_category_id">
							<option value="0" <?php selected( (int) $edit_row['category_id'], 0 ); ?>><?php esc_html_e( 'Uncategorized', 'beruang' ); ?></option>
							<?php
							foreach ( $edit_categories as $c ) {
								$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
								echo '<option value="' . esc_attr( $c['id'] ) . '" ' . selected( (int) $edit_row['category_id'], (int) $c['id'], false ) . '>' . esc_html( $indent . $c['name'] ) . '</option>';
							}
							?>
						</select></td></tr>
						<tr><th scope="row"><label for="beruang_edit_amount"><?php esc_html_e( 'Amount', 'beruang' ); ?></label></th><td><input type="number" id="beruang_edit_amount" name="beruang_amount" step="1" min="0" value="<?php echo esc_attr( shortcode_format_amount_input_value( $edit_row['amount'] ) ); ?>" required /> <?php echo esc_html( $currency ); ?></td></tr>
						<tr><th scope="row"><label for="beruang_edit_type"><?php esc_html_e( 'Type', 'beruang' ); ?></label></th>
						<td><select id="beruang_edit_type" name="beruang_type">
							<option value="expense" <?php selected( $edit_row['type'], 'expense' ); ?>><?php esc_html_e( 'Expense', 'beruang' ); ?></option>
							<option value="income" <?php selected( $edit_row['type'], 'income' ); ?>><?php esc_html_e( 'Income', 'beruang' ); ?></option>
						</select></td></tr>
					</table>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'beruang' ); ?></button>
					<a href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page'    => 'beruang-transactions',
								'user_id' => $user_filter ? $user_filter : null,
							),
							admin_url( 'admin.php' )
						)
					);
					?>
					" class="button"><?php esc_html_e( 'Cancel', 'beruang' ); ?></a></p>
				</form>
			</div>
			<?php
		}
		?>
		<?php
		$list_table = new Transactions_List_Table( $user_filter );
		$list_table->prepare_items();
		?>
		<form id="beruang-transactions-filter" method="get">
			<input type="hidden" name="page" value="beruang-transactions" />
			<div class="beruang-admin-table-wrap beruang-transactions-table-wrap">
				<?php $list_table->display(); ?>
			</div>
		</form>
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
	$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row = $edit_id ? DB::get_category_by_id( $edit_id ) : null;
	if ( $edit_row && $user_filter !== (int) $edit_row['user_id'] ) {
		$edit_row = null;
		$edit_id  = 0;
	}
	$categories = DB::get_categories_flat( $user_filter, true );
	$message    = '';
	if ( isset( $_GET['beruang_updated'] ) ) {
		$message = __( 'Category updated.', 'beruang' );
	}
	if ( isset( $_GET['beruang_error'] ) && 'notfound' === $_GET['beruang_error'] ) {
		$message = __( 'Category not found.', 'beruang' );
	}
	if ( isset( $_GET['delete'] ) && check_admin_referer( 'beruang_delete_cat_' . absint( $_GET['delete'] ) ) ) {
		DB::delete_category( $user_filter, absint( $_GET['delete'] ) );
		$message    = __( 'Category deleted.', 'beruang' );
		$categories = DB::get_categories_flat( $user_filter, true );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Categories', 'beruang' ); ?></h1>
		<?php if ( $message ) { echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['beruang_error'] ) && 'name' === $_GET['beruang_error'] ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Name is required.', 'beruang' ) . '</p></div>'; } ?>
		<form method="get" class="beruang-admin-filter">
			<input type="hidden" name="page" value="beruang-categories" />
			<label><?php esc_html_e( 'User ID', 'beruang' ); ?> <input type="number" name="user_id" value="<?php echo esc_attr( $user_filter ); ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'beruang' ); ?></button>
		</form>
		<?php if ( $edit_row ) { ?>
		<div class="beruang-admin-edit-box" style="margin:1em 0;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
			<h2><?php esc_html_e( 'Edit category', 'beruang' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="beruang_update_category" />
				<?php wp_nonce_field( 'beruang_edit_category' ); ?>
				<input type="hidden" name="beruang_cat_id" value="<?php echo (int) $edit_row['id']; ?>" />
				<input type="hidden" name="beruang_cat_user_id" value="<?php echo (int) $edit_row['user_id']; ?>" />
				<table class="form-table">
					<tr><th scope="row"><label for="beruang_edit_cat_name"><?php esc_html_e( 'Name', 'beruang' ); ?></label></th><td><input type="text" id="beruang_edit_cat_name" name="beruang_cat_name" value="<?php echo esc_attr( $edit_row['name'] ); ?>" class="regular-text" required /></td></tr>
					<tr><th scope="row"><label for="beruang_edit_cat_parent"><?php esc_html_e( 'Parent', 'beruang' ); ?></label></th><td><select id="beruang_edit_cat_parent" name="beruang_cat_parent"><option value="0">—</option>
						<?php
						foreach ( $categories as $c ) {
							if ( (int) $c['id'] === (int) $edit_row['id'] ) { continue; }
							$indent = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
							echo '<option value="' . esc_attr( $c['id'] ) . '" ' . selected( (int) $edit_row['parent_id'], (int) $c['id'], false ) . '>' . esc_html( $indent . $c['name'] ) . '</option>';
						}
						?>
					</select></td></tr>
				</table>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'beruang' ); ?></button>
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'    => 'beruang-categories',
							'user_id' => $user_filter,
						),
						admin_url( 'admin.php' )
					)
				);
				?>
				" class="button"><?php esc_html_e( 'Cancel', 'beruang' ); ?></a></p>
			</form>
		</div>
		<?php } ?>
		<div class="beruang-admin-table-wrap">
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'beruang' ); ?></th><th><?php esc_html_e( 'User ID', 'beruang' ); ?></th><th><?php esc_html_e( 'Name', 'beruang' ); ?></th><th><?php esc_html_e( 'Parent ID', 'beruang' ); ?></th><th><?php esc_html_e( 'Actions', 'beruang' ); ?></th></tr></thead>
			<tbody>
			<?php
			if ( empty( $categories ) ) {
				echo '<tr><td colspan="5">' . esc_html__( 'No categories.', 'beruang' ) . '</td></tr>';
			} else {
				foreach ( $categories as $row ) {
					$edit_url = add_query_arg(
						array(
							'page'    => 'beruang-categories',
							'user_id' => $user_filter,
							'edit'    => $row['id'],
						),
						admin_url( 'admin.php' )
					);
					$del_url  = wp_nonce_url(
						add_query_arg(
							array(
								'page'    => 'beruang-categories',
								'user_id' => $user_filter,
								'delete'  => $row['id'],
							)
						),
						'beruang_delete_cat_' . $row['id']
					);
					echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td>' . esc_html( str_repeat( '— ', (int) ( $row['depth'] ?? 0 ) ) . $row['name'] ) . '</td><td>' . esc_html( $row['parent_id'] ) . '</td><td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'beruang' ) . '</a> | <a href="' . esc_url( $del_url ) . '" class="submitdelete">' . esc_html__( 'Delete', 'beruang' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
		</div>
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
	$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
	$edit_row = $edit_id ? DB::get_budget_by_id( $edit_id ) : null;
	if ( $edit_row && $user_filter !== (int) $edit_row['user_id'] ) {
		$edit_row = null;
		$edit_id  = 0;
	}
	$budgets         = DB::get_budgets( $user_filter );
	$edit_categories = $edit_row ? DB::get_categories_flat( $user_filter, true ) : array();
	$currency        = get_option( 'beruang_currency', 'IDR' );
	$dec             = get_option( 'beruang_decimal_sep', ',' );
	$thou            = get_option( 'beruang_thousands_sep', '.' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Budgets', 'beruang' ); ?></h1>
		<?php if ( isset( $_GET['beruang_updated'] ) ) { echo '<div class="notice notice-success"><p>' . esc_html__( 'Budget updated.', 'beruang' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['beruang_error'] ) && 'notfound' === $_GET['beruang_error'] ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Budget not found.', 'beruang' ) . '</p></div>'; } ?>
		<?php if ( isset( $_GET['beruang_error'] ) && 'name' === $_GET['beruang_error'] ) { echo '<div class="notice notice-error"><p>' . esc_html__( 'Name is required.', 'beruang' ) . '</p></div>'; } ?>
		<form method="get" class="beruang-admin-filter">
			<input type="hidden" name="page" value="beruang-budgets" />
			<label><?php esc_html_e( 'User ID', 'beruang' ); ?> <input type="number" name="user_id" value="<?php echo esc_attr( $user_filter ); ?>" min="1" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'beruang' ); ?></button>
		</form>
		<?php if ( $edit_row ) { ?>
		<div class="beruang-admin-edit-box" style="margin:1em 0;padding:1em;background:#f0f0f1;border-left:4px solid #2271b1;">
			<h2><?php esc_html_e( 'Edit budget', 'beruang' ); ?> #<?php echo (int) $edit_row['id']; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="beruang_update_budget" />
				<?php wp_nonce_field( 'beruang_edit_budget' ); ?>
				<input type="hidden" name="beruang_budget_id" value="<?php echo (int) $edit_row['id']; ?>" />
				<input type="hidden" name="beruang_budget_user_id" value="<?php echo (int) $edit_row['user_id']; ?>" />
				<table class="form-table">
					<tr><th scope="row"><label for="beruang_edit_budget_name"><?php esc_html_e( 'Name', 'beruang' ); ?></label></th><td><input type="text" id="beruang_edit_budget_name" name="beruang_budget_name" value="<?php echo esc_attr( $edit_row['name'] ); ?>" class="regular-text" required /></td></tr>
					<tr><th scope="row"><label for="beruang_edit_budget_target"><?php esc_html_e( 'Target', 'beruang' ); ?></label></th><td><input type="number" id="beruang_edit_budget_target" name="beruang_budget_target" step="1" min="0" value="<?php echo esc_attr( shortcode_format_amount_input_value( $edit_row['target_amount'] ) ); ?>" required /> <?php echo esc_html( $currency ); ?></td></tr>
					<tr><th scope="row"><label for="beruang_edit_budget_type"><?php esc_html_e( 'Type', 'beruang' ); ?></label></th><td><select id="beruang_edit_budget_type" name="beruang_budget_type"><option value="monthly" <?php selected( $edit_row['type'], 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'beruang' ); ?></option><option value="yearly" <?php selected( $edit_row['type'], 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'beruang' ); ?></option></select></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Categories', 'beruang' ); ?></th><td><fieldset>
						<?php
						foreach ( $edit_categories as $c ) {
							$indent  = str_repeat( '— ', (int) ( $c['depth'] ?? 0 ) );
							$checked = in_array( (int) $c['id'], $edit_row['category_ids'], true );
							echo '<label style="display:block;"><input type="checkbox" name="beruang_budget_categories[]" value="' . esc_attr( $c['id'] ) . '" ' . ( $checked ? 'checked' : '' ) . ' /> ' . esc_html( $indent . $c['name'] ) . '</label>';
						}
						?>
					</fieldset></td></tr>
				</table>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'beruang' ); ?></button>
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'    => 'beruang-budgets',
							'user_id' => $user_filter,
						),
						admin_url( 'admin.php' )
					)
				);
				?>
				" class="button"><?php esc_html_e( 'Cancel', 'beruang' ); ?></a></p>
			</form>
		</div>
		<?php } ?>
		<div class="beruang-admin-table-wrap">
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'ID', 'beruang' ); ?></th><th><?php esc_html_e( 'User ID', 'beruang' ); ?></th><th><?php esc_html_e( 'Name', 'beruang' ); ?></th><th><?php esc_html_e( 'Target', 'beruang' ); ?></th><th><?php esc_html_e( 'Type', 'beruang' ); ?></th><th><?php esc_html_e( 'Category IDs', 'beruang' ); ?></th><th><?php esc_html_e( 'Actions', 'beruang' ); ?></th></tr></thead>
			<tbody>
			<?php
			if ( empty( $budgets ) ) {
				echo '<tr><td colspan="7">' . esc_html__( 'No budgets.', 'beruang' ) . '</td></tr>';
			} else {
				foreach ( $budgets as $row ) {
					$target   = number_format( (float) $row['target_amount'], '.' === $dec ? 2 : 0, $dec, $thou );
					$edit_url = add_query_arg(
						array(
							'page'    => 'beruang-budgets',
							'user_id' => $user_filter,
							'edit'    => $row['id'],
						),
						admin_url( 'admin.php' )
					);
					echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( $row['user_id'] ) . '</td><td>' . esc_html( $row['name'] ) . '</td><td>' . esc_html( $target ) . ' ' . esc_html( $currency ) . '</td><td>' . esc_html( $row['type'] ) . '</td><td>' . esc_html( implode( ', ', $row['category_ids'] ) ) . '</td><td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'beruang' ) . '</a></td></tr>';
				}
			}
			?>
			</tbody>
		</table>
		</div>
	</div>
	<?php
}
