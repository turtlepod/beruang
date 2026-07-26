<?php
/**
 * Template for [beruang-list] shortcode.
 *
 * @package Beruang
 * @var int    $year        Current year.
 * @var string $today       Default date (Y-m-d).
 * @var string $time        Default time (H:i).
 * @var string $currency    Currency code.
 * @var array  $categories  Flat categories from DB.
 * @var array  $budgets     Budgets from DB.
 * @var array  $wallets     Wallets from DB.
 * @var int    $default_wallet_id Default wallet ID.
 * @var int    $decimal_places    Number of decimal places.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables passed via shortcode_load_template().
?>
<div class="beruang beruang-list-wrapper">
	<div class="beruang-list-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Transactions', 'beruang-budget' ); ?></h2>
		<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--secondary beruang-filter-btn" title="<?php esc_attr_e( 'Filter', 'beruang-budget' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'beruang-budget' ); ?>"><?php \BeruangBudget\beruang_icon( 'filter', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
	</div>
	<div class="beruang-filters beruang-list-filters" id="beruang-list-filters" hidden>
		<select class="beruang-filter-year" aria-label="<?php esc_attr_e( 'Year', 'beruang-budget' ); ?>">
			<?php
			$current_year = (int) current_time( 'Y' );
			for ( $y = $current_year; $y >= $current_year - 10; $y-- ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $y ), selected( $year, $y, false ), esc_html( (string) $y ) );
			}
			?>
		</select>
		<input type="text" class="beruang-filter-search" placeholder="<?php esc_attr_e( 'Search description', 'beruang-budget' ); ?>" />
		<select class="beruang-filter-category">
			<option value=""><?php esc_html_e( 'All categories', 'beruang-budget' ); ?></option>
			<option value="0"><?php esc_html_e( 'Uncategorized', 'beruang-budget' ); ?></option>
			<?php
			foreach ( $categories as $cat ) {
				$depth  = (int) ( $cat['depth'] ?? 0 );
				$indent = str_repeat( '— ', $depth );
				echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
			}
			?>
		</select>
		<select class="beruang-filter-budget">
			<option value=""><?php esc_html_e( 'All budgets', 'beruang-budget' ); ?></option>
			<?php
			foreach ( $budgets as $budget ) {
				echo '<option value="' . esc_attr( $budget['id'] ) . '">' . esc_html( $budget['name'] ) . '</option>';
			}
			?>
		</select>
		<select class="beruang-filter-wallet">
			<option value=""><?php esc_html_e( 'All wallets', 'beruang-budget' ); ?></option>
			<option value="0"><?php esc_html_e( 'No wallet', 'beruang-budget' ); ?></option>
			<?php
			foreach ( $wallets as $wallet ) {
				echo '<option value="' . esc_attr( $wallet['id'] ) . '">' . esc_html( $wallet['name'] ) . '</option>';
			}
			?>
		</select>
		<div class="beruang-filter-actions">
			<button type="button" class="beruang-btn beruang-btn--primary beruang-filter-apply"><?php esc_html_e( 'Apply', 'beruang-budget' ); ?></button>
			<button type="button" class="beruang-btn beruang-btn--secondary beruang-filter-reset"><?php esc_html_e( 'Reset', 'beruang-budget' ); ?></button>
		</div>
	</div>
	<div class="beruang-list-accordion" id="beruang-list-accordion" data-year="<?php echo esc_attr( $year ); ?>">
		<p class="beruang-loading"><?php esc_html_e( 'Loading…', 'beruang-budget' ); ?></p>
	</div>
	<div class="beruang-edit-tx-modal beruang-modal" id="beruang-edit-tx-modal" hidden>
		<div class="beruang-modal-dialog">
			<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang-budget' ); ?>"><?php \BeruangBudget\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
			<div class="beruang-modal-inner beruang-edit-tx-modal-inner">
			<h4><?php esc_html_e( 'Edit transaction', 'beruang-budget' ); ?></h4>
			<?php
			\BeruangBudget\shortcode_load_template(
				'partials/transaction-form.php',
				array(
					'mode'              => 'edit',
					'form_id'           => 'beruang-edit-tx-form',
					'field_prefix'      => 'beruang-edit-tx',
					'today'             => $today,
					'time'              => $time,
					'currency'          => $currency,
					'categories'        => $categories,
					'wallets'           => $wallets,
					'default_wallet_id' => $default_wallet_id,
					'decimal_places'    => $decimal_places,
				)
			);
			?>
			</div>
		</div>
	</div>
</div>
<?php \BeruangBudget\output_modals_once(); ?>