<?php
/**
 * Template for [beruang-list] shortcode.
 *
 * @package Beruang
 * @var int    $year        Current year.
 * @var array  $categories  Flat categories from DB.
 * @var string $amount_step Step attribute for amount input (e.g. '0.01').
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-list-wrapper">
	<div class="beruang-list-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Transactions', 'beruang' ); ?></h2>
		<button type="button" class="beruang-filter-btn" title="<?php esc_attr_e( 'Filter', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'filter', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
	</div>
	<div class="beruang-list-filters" id="beruang-list-filters" hidden>
		<select class="beruang-filter-year" aria-label="<?php esc_attr_e( 'Year', 'beruang' ); ?>">
			<?php
			$current_year = (int) current_time( 'Y' );
			for ( $y = $current_year; $y >= $current_year - 10; $y-- ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $y ), selected( $year, $y, false ), esc_html( (string) $y ) );
			}
			?>
		</select>
		<input type="text" class="beruang-filter-search" placeholder="<?php esc_attr_e( 'Search description', 'beruang' ); ?>" />
		<select class="beruang-filter-category">
			<option value=""><?php esc_html_e( 'All categories', 'beruang' ); ?></option>
			<option value="0"><?php esc_html_e( 'Uncategorized', 'beruang' ); ?></option>
			<?php
			foreach ( $categories as $cat ) {
				$depth  = (int) ( $cat['depth'] ?? 0 );
				$indent = str_repeat( '— ', $depth );
				echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
			}
			?>
		</select>
		<button type="button" class="beruang-filter-apply"><?php esc_html_e( 'Apply', 'beruang' ); ?></button>
		<button type="button" class="beruang-filter-reset"><?php esc_html_e( 'Reset', 'beruang' ); ?></button>
	</div>
	<div class="beruang-list-accordion" id="beruang-list-accordion" data-year="<?php echo esc_attr( $year ); ?>">
		<p class="beruang-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
	</div>
	<div class="beruang-edit-tx-modal beruang-modal" id="beruang-edit-tx-modal" hidden>
		<div class="beruang-modal-dialog">
			<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>">×</button>
			<div class="beruang-edit-tx-modal-inner">
			<h4><?php esc_html_e( 'Edit transaction', 'beruang' ); ?></h4>
			<form id="beruang-edit-tx-form">
				<input type="hidden" name="id" id="beruang-edit-tx-id" value="" />
				<div class="beruang-form-row">
					<label for="beruang-edit-tx-date"><?php esc_html_e( 'Date', 'beruang' ); ?></label>
					<input type="date" id="beruang-edit-tx-date" name="date" required />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-edit-tx-time"><?php esc_html_e( 'Time', 'beruang' ); ?></label>
					<input type="time" id="beruang-edit-tx-time" name="time" />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-edit-tx-description"><?php esc_html_e( 'Description', 'beruang' ); ?></label>
					<input type="text" id="beruang-edit-tx-description" name="description" />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-edit-tx-category"><?php esc_html_e( 'Category', 'beruang' ); ?></label>
					<select id="beruang-edit-tx-category" name="category_id">
						<option value="0"><?php esc_html_e( 'Uncategorized', 'beruang' ); ?></option>
						<?php
						foreach ( $categories as $cat ) {
							$depth  = (int) ( $cat['depth'] ?? 0 );
							$indent = str_repeat( '— ', $depth );
							echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="beruang-form-row">
					<label for="beruang-edit-tx-amount"><?php esc_html_e( 'Amount', 'beruang' ); ?></label>
					<input type="number" id="beruang-edit-tx-amount" name="amount" step="<?php echo esc_attr( $amount_step ?? '0.01' ); ?>" min="0" required />
				</div>
				<div class="beruang-form-row">
					<label><?php esc_html_e( 'Type', 'beruang' ); ?></label>
					<select id="beruang-edit-tx-type" name="type">
						<option value="expense"><?php esc_html_e( 'Expense', 'beruang' ); ?></option>
						<option value="income"><?php esc_html_e( 'Income', 'beruang' ); ?></option>
					</select>
				</div>
				<div class="beruang-form-row beruang-modal-actions">
					<button type="submit" class="beruang-submit beruang-modal-save"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
					<button type="button" class="beruang-modal-cancel beruang-edit-tx-cancel"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
					<span class="beruang-form-message" aria-live="polite"></span>
				</div>
			</form>
			</div>
		</div>
	</div>
</div>
