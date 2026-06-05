<?php
/**
 * Template Name: Account
 * Template Post Type: page
 *
 * @package BeruangSaaS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

// Handle CSV export before any HTML output.
if ( is_user_logged_in() && 'POST' === $request_method && isset( $_POST['beruang_user_export_csv'] ) ) {
	if ( ! isset( $_POST['beruang_export_csv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['beruang_export_csv_nonce'] ) ), 'beruang_user_export_csv' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'beruang-saas' ) );
	}
	$csv_user_id = get_current_user_id();
	if ( $csv_user_id && class_exists( 'Beruang\DB' ) ) {
		$transactions = \Beruang\DB::get_transactions( $csv_user_id, array( 'per_page' => 99999 ) );
		$items        = $transactions['items'];
		$categories   = \Beruang\DB::get_categories_flat( $csv_user_id, false );
		$wallets      = \Beruang\DB::get_wallets( $csv_user_id );
		$cat_names    = array();
		$wallet_names = array();
		foreach ( $categories as $c ) {
			$cat_names[ (int) $c['id'] ] = $c['name'] ?? '';
		}
		foreach ( $wallets as $w ) {
			$wallet_names[ (int) $w['id'] ] = $w['name'] ?? '';
		}
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="beruang-transactions-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fprintf( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
		fputcsv( $output, array( 'id', 'date', 'time', 'description', 'note', 'wallet_id', 'wallet_name', 'category_id', 'category_name', 'amount', 'type' ) );
		foreach ( $items as $row ) {
			$cat_id      = isset( $row['category_id'] ) ? (int) $row['category_id'] : 0;
			$cat_name    = $cat_id && isset( $cat_names[ $cat_id ] ) ? $cat_names[ $cat_id ] : '';
			$wallet_id   = isset( $row['wallet_id'] ) ? (int) $row['wallet_id'] : 0;
			$wallet_name = $wallet_id && isset( $wallet_names[ $wallet_id ] ) ? $wallet_names[ $wallet_id ] : __( 'No Wallet', 'beruang' );
			fputcsv(
				$output,
				array(
					$row['id'] ?? '',
					$row['date'] ?? '',
					$row['time'] ?? '',
					$row['description'] ?? '',
					$row['note'] ?? '',
					$row['wallet_id'] ?? '',
					$wallet_name,
					$row['category_id'] ?? '',
					$cat_name,
					$row['amount'] ?? '',
					$row['type'] ?? '',
				)
			);
		}
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}

$messages        = array();
$errors          = array();
$budget_messages = array();
$budget_errors   = array();
$redirect_tab    = 'profile';

if ( is_user_logged_in() && 'POST' === $request_method ) {
	$current_user = wp_get_current_user();
	$user_id      = (int) $current_user->ID;

	// Account form (email, password, language).
	if ( isset( $_POST['beruang_account_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['beruang_account_nonce'] ) ), 'beruang_account_update' ) ) {
			$errors[] = __( 'Security check failed. Please try again.', 'beruang-saas' );
		} else {
			$new_email    = isset( $_POST['account_email'] ) ? sanitize_email( wp_unslash( $_POST['account_email'] ) ) : '';
			$new_password = isset( $_POST['account_password'] ) ? (string) wp_unslash( $_POST['account_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$new_locale   = isset( $_POST['account_locale'] ) ? sanitize_text_field( wp_unslash( $_POST['account_locale'] ) ) : '';
			$userdata     = array( 'ID' => $user_id );
			$has_changes  = false;

			if ( empty( $new_email ) || ! is_email( $new_email ) ) {
				$errors[] = __( 'Please enter a valid email address.', 'beruang-saas' );
			} elseif ( $new_email !== $current_user->user_email ) {
				$exists = email_exists( $new_email );
				if ( $exists && (int) $exists !== $user_id ) {
					$errors[] = __( 'That email is already used by another account.', 'beruang-saas' );
				} else {
					$userdata['user_email'] = $new_email;
					$has_changes            = true;
				}
			}

			if ( '' !== $new_password ) {
				if ( strlen( $new_password ) < 8 ) {
					$errors[] = __( 'Password must be at least 8 characters.', 'beruang-saas' );
				} else {
					$userdata['user_pass'] = $new_password;
					$has_changes           = true;
				}
			}

			// Language / locale.
			if ( 'site-default' === $new_locale ) {
				$new_locale = '';
			} elseif ( '' === $new_locale ) {
				$new_locale = 'en_US';
			} elseif ( ! in_array( $new_locale, get_available_languages(), true ) ) {
				$new_locale = '';
			}
			$current_locale = get_user_meta( $user_id, 'locale', true );
			if ( $new_locale !== $current_locale ) {
				$userdata['locale'] = $new_locale;
				$has_changes        = true;
			}

			if ( $has_changes && empty( $errors ) ) {
				$result = wp_update_user( $userdata );
				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				} else {
					wp_safe_redirect( add_query_arg( 'updated', '1', get_permalink() ) . '#profile' );
					exit;
				}
			} elseif ( ! $has_changes && empty( $errors ) ) {
				$messages[] = __( 'No changes were made.', 'beruang-saas' );
			}
		}
	}

	// Budget settings form.
	if ( isset( $_POST['beruang_budget_nonce'] ) ) {
		$redirect_tab = 'budget';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['beruang_budget_nonce'] ) ), 'beruang_budget_settings_update' ) ) {
			$budget_errors[] = __( 'Security check failed. Please try again.', 'beruang-saas' );
		} else {
			$bud_save_currency      = isset( $_POST['budget_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['budget_currency'] ) ) : '';
			$bud_save_decimal_sep   = isset( $_POST['budget_decimal_sep'] ) ? sanitize_text_field( wp_unslash( $_POST['budget_decimal_sep'] ) ) : '';
			$bud_save_thousands_sep = isset( $_POST['budget_thousands_sep'] ) ? sanitize_text_field( wp_unslash( $_POST['budget_thousands_sep'] ) ) : '';
			$bud_save_dec_places    = isset( $_POST['budget_decimal_places'] ) ? sanitize_text_field( wp_unslash( $_POST['budget_decimal_places'] ) ) : '';

			if ( '' !== $bud_save_dec_places ) {
				$bud_save_dec_places = (string) min( 4, absint( $bud_save_dec_places ) );
			}

			update_user_meta( $user_id, 'beruang_user_currency', $bud_save_currency );
			update_user_meta( $user_id, 'beruang_user_decimal_sep', $bud_save_decimal_sep );
			update_user_meta( $user_id, 'beruang_user_thousands_sep', $bud_save_thousands_sep );
			update_user_meta( $user_id, 'beruang_user_decimal_places', $bud_save_dec_places );

			wp_safe_redirect( add_query_arg( 'updated', '1', get_permalink() ) . '#budget' );
			exit;
		}
	}
}

get_header();

$has_export = class_exists( 'Beruang\DB' );

$account_tabs = array(
	array(
		'id'    => 'profile',
		'label' => __( 'Account', 'beruang-saas' ),
	),
	array(
		'id'    => 'budget',
		'label' => __( 'Budget', 'beruang-saas' ),
	),
);
if ( $has_export ) {
	$account_tabs[] = array(
		'id'    => 'export',
		'label' => __( 'Export', 'beruang-saas' ),
	);
}
?>

<?php if ( is_user_logged_in() ) : ?>
	<nav class="site-nav beruang-tabs-nav" data-tabs-nav="account" aria-label="<?php esc_attr_e( 'Account sections', 'beruang-saas' ); ?>">
		<ul role="tablist" aria-label="<?php esc_attr_e( 'Account tabs', 'beruang-saas' ); ?>">
			<?php foreach ( $account_tabs as $index => $tab ) : ?>
				<li>
					<button
						type="button"
						class="beruang-tab<?php echo 0 === $index ? ' is-active' : ''; ?>"
						id="account-tab-<?php echo esc_attr( $tab['id'] ); ?>"
						data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
						role="tab"
						aria-controls="account-panel-<?php echo esc_attr( $tab['id'] ); ?>"
						aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
						tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
					>
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				</li>
			<?php endforeach; ?>
			<span class="beruang-tab-indicator" aria-hidden="true"></span>
		</ul>
	</nav>
<?php endif; ?>

<main id="primary" class="site-main account-main">
	<?php if ( is_user_logged_in() ) : ?>
		<?php
		$current_user = wp_get_current_user();
		$user_id      = (int) $current_user->ID;
		$logout_url   = wp_logout_url( get_permalink() );
		$user_locale  = get_user_meta( $user_id, 'locale', true );
		$languages    = get_available_languages();
		if ( 'en_US' === $user_locale ) {
			$user_locale = '';
		} elseif ( '' === $user_locale || ! in_array( $user_locale, $languages, true ) ) {
			$user_locale = 'site-default';
		}

		// Budget setting values (user meta or '' if not overridden).
		$bud_currency       = (string) get_user_meta( $user_id, 'beruang_user_currency', true );
		$bud_decimal_sep    = (string) get_user_meta( $user_id, 'beruang_user_decimal_sep', true );
		$bud_thousands_sep  = (string) get_user_meta( $user_id, 'beruang_user_thousands_sep', true );
		$bud_decimal_places = (string) get_user_meta( $user_id, 'beruang_user_decimal_places', true );

		// Global defaults shown as placeholders.
		$global_currency       = get_option( 'beruang_currency', 'IDR' );
		$global_decimal_sep    = get_option( 'beruang_decimal_sep', ',' );
		$global_thousands_sep  = get_option( 'beruang_thousands_sep', '.' );
		$global_decimal_places = (int) get_option( 'beruang_decimal_places', 2 );
		?>

		<div class="beruang-tabs-content" data-tabs-content="account" data-swipe-tabs="true">
			<div class="beruang-tabs-strip">

				<!-- ── Profile tab panel ──────────────────────────── -->
				<section
					class="beruang-tab-panel account-tab-panel is-active"
					id="account-panel-profile"
					data-panel="profile"
					role="tabpanel"
					aria-labelledby="account-tab-profile"
					aria-hidden="false"
				>
				<div class="account-panel-inner">
					<?php if ( ! empty( $errors ) ) : ?>
						<div class="account-alert account-alert-error" role="alert">
							<?php foreach ( $errors as $error_message ) : ?>
								<p><?php echo esc_html( $error_message ); ?></p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $messages ) ) : ?>
						<div class="account-alert account-alert-success" role="status">
							<?php foreach ( $messages as $message ) : ?>
								<p><?php echo esc_html( $message ); ?></p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<form method="post" class="account-form">
						<p class="account-field">
							<label for="account-email"><?php esc_html_e( 'Email Address', 'beruang-saas' ); ?></label>
							<input type="email" id="account-email" name="account_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
						</p>

						<?php if ( ! empty( $languages ) ) : ?>
						<p class="account-field">
							<label for="account-locale"><?php esc_html_e( 'Language', 'beruang-saas' ); ?></label>
							<?php
							wp_dropdown_languages(
								array(
									'name'      => 'account_locale',
									'id'        => 'account-locale',
									'selected'  => $user_locale,
									'languages' => $languages,
									'show_available_translations' => false,
									'show_option_site_default' => true,
								)
							);
							?>
						</p>
						<?php endif; ?>

						<p class="account-field">
							<label for="account-password"><?php esc_html_e( 'New Password', 'beruang-saas' ); ?></label>
							<span class="account-pw-wrap">
								<input type="password" id="account-password" name="account_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep current password', 'beruang-saas' ); ?>">
								<button type="button" id="account-pw-toggle" class="account-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'beruang-saas' ); ?>" aria-pressed="false">
									<svg id="account-pw-eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<svg id="account-pw-eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
								</button>
							</span>
							<span id="account-password-strength" class="account-password-strength" aria-live="polite"></span>
						</p>

						<?php wp_nonce_field( 'beruang_account_update', 'beruang_account_nonce' ); ?>
						<button type="submit" class="account-submit"><?php esc_html_e( 'Save Changes', 'beruang-saas' ); ?></button>
					</form>

					<p class="account-logout-wrap">
						<a class="account-logout" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Log out', 'beruang-saas' ); ?></a>
					</p>
				</div>
				</section>

				<!-- ── Budget settings tab panel ──────────────────── -->
				<section
					class="beruang-tab-panel account-tab-panel"
					id="account-panel-budget"
					data-panel="budget"
					role="tabpanel"
					aria-labelledby="account-tab-budget"
					aria-hidden="true"
				>
				<div class="account-panel-inner">
					<p class="account-section-desc"><?php esc_html_e( 'Override the site-wide display settings for your account. Leave a field blank to use the site default.', 'beruang-saas' ); ?></p>

					<?php if ( ! empty( $budget_errors ) ) : ?>
						<div class="account-alert account-alert-error" role="alert">
							<?php foreach ( $budget_errors as $msg ) : ?>
								<p><?php echo esc_html( $msg ); ?></p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $budget_messages ) ) : ?>
						<div class="account-alert account-alert-success" role="status">
							<?php foreach ( $budget_messages as $msg ) : ?>
								<p><?php echo esc_html( $msg ); ?></p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<form method="post" class="account-form">
						<p class="account-field">
							<label for="budget-currency"><?php esc_html_e( 'Currency', 'beruang-saas' ); ?></label>
							<input type="text" id="budget-currency" name="budget_currency" value="<?php echo esc_attr( $bud_currency ); ?>" placeholder="<?php echo esc_attr( $global_currency ); ?>">
							<span class="account-field-hint">
								<?php
								// translators: %s: site default currency code.
								printf( esc_html__( 'Site default: %s', 'beruang-saas' ), esc_html( $global_currency ) );
								?>
							</span>
						</p>

						<p class="account-field">
							<label for="budget-decimal-sep"><?php esc_html_e( 'Decimal Separator', 'beruang-saas' ); ?></label>
							<input type="text" id="budget-decimal-sep" name="budget_decimal_sep" value="<?php echo esc_attr( $bud_decimal_sep ); ?>" placeholder="<?php echo esc_attr( $global_decimal_sep ); ?>" maxlength="2">
							<span class="account-field-hint">
								<?php
								// translators: %s: site default decimal separator character.
								printf( esc_html__( 'Site default: %s', 'beruang-saas' ), esc_html( $global_decimal_sep ) );
								?>
							</span>
						</p>

						<p class="account-field">
							<label for="budget-thousands-sep"><?php esc_html_e( 'Thousand Separator', 'beruang-saas' ); ?></label>
							<input type="text" id="budget-thousands-sep" name="budget_thousands_sep" value="<?php echo esc_attr( $bud_thousands_sep ); ?>" placeholder="<?php echo esc_attr( $global_thousands_sep ); ?>" maxlength="2">
							<span class="account-field-hint">
								<?php
								// translators: %s: site default thousands separator character.
								printf( esc_html__( 'Site default: %s', 'beruang-saas' ), esc_html( $global_thousands_sep ) );
								?>
							</span>
						</p>

						<p class="account-field">
							<label for="budget-decimal-places"><?php esc_html_e( 'Decimal Places', 'beruang-saas' ); ?></label>
							<select id="budget-decimal-places" name="budget_decimal_places">
								<option value="" <?php selected( $bud_decimal_places, '' ); ?>>
									<?php
									// translators: %d: site default decimal places count.
									printf( esc_html__( 'Site default (%d)', 'beruang-saas' ), $global_decimal_places ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</option>
								<option value="0" <?php selected( $bud_decimal_places, '0' ); ?>><?php esc_html_e( 'None (integers only)', 'beruang-saas' ); ?></option>
								<option value="1" <?php selected( $bud_decimal_places, '1' ); ?>>1</option>
								<option value="2" <?php selected( $bud_decimal_places, '2' ); ?>>2</option>
								<option value="3" <?php selected( $bud_decimal_places, '3' ); ?>>3</option>
								<option value="4" <?php selected( $bud_decimal_places, '4' ); ?>>4</option>
							</select>
						</p>

						<?php wp_nonce_field( 'beruang_budget_settings_update', 'beruang_budget_nonce' ); ?>
						<button type="submit" class="account-submit"><?php esc_html_e( 'Save Budget Settings', 'beruang-saas' ); ?></button>
					</form>
				</div>
				</section>

				<?php if ( $has_export ) : ?>
				<!-- ── Export tab panel ───────────────────────────── -->
				<section
					class="beruang-tab-panel account-tab-panel"
					id="account-panel-export"
					data-panel="export"
					role="tabpanel"
					aria-labelledby="account-tab-export"
					aria-hidden="true"
				>
				<div class="account-panel-inner">
					<p class="account-section-desc"><?php esc_html_e( 'Download all your transactions as a CSV file.', 'beruang-saas' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'beruang_user_export_csv', 'beruang_export_csv_nonce' ); ?>
						<button type="submit" name="beruang_user_export_csv" value="1" class="account-submit"><?php esc_html_e( 'Export Transactions as CSV', 'beruang-saas' ); ?></button>
					</form>
				</div>
				</section>
				<?php endif; ?>

			</div>
		</div>

		<script>
		( function () {
			var hash = window.location.hash ? window.location.hash.slice( 1 ) : '';
			if ( ! hash ) return;
			var navEl = document.querySelector( '[data-tabs-nav="account"]' );
			if ( ! navEl ) return;
			var tabs  = Array.from( navEl.querySelectorAll( '.beruang-tab' ) );
			var valid = tabs.some( function ( t ) { return t.dataset.tab === hash; } );
			if ( ! valid ) return;
			var contentEl = document.querySelector( '[data-tabs-content="account"]' );
			var panels    = contentEl ? Array.from( contentEl.querySelectorAll( '.beruang-tab-panel' ) ) : [];
			tabs.forEach( function ( t ) {
				var active = t.dataset.tab === hash;
				t.classList.toggle( 'is-active', active );
				t.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				t.setAttribute( 'tabindex', active ? '0' : '-1' );
			} );
			panels.forEach( function ( p ) {
				var active = p.dataset.panel === hash;
				p.classList.toggle( 'is-active', active );
				p.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			} );
		}() );
		</script>

	<?php else : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
