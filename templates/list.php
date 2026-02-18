<?php
/**
 * Template for [mowi-list] shortcode.
 *
 * @package Mowi
 * @var int   $year       Current year.
 * @var int   $month      Current month.
 * @var array $categories Flat categories from DB.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mowi mowi-list-wrapper">
	<div class="mowi-list-header">
		<h3 class="mowi-list-title"><?php esc_html_e( 'Transactions', 'mowi' ); ?></h3>
		<button type="button" class="mowi-filter-btn" title="<?php esc_attr_e( 'Filter', 'mowi' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'mowi' ); ?>">⊕</button>
	</div>
	<div class="mowi-list-filters" id="mowi-list-filters" hidden>
		<input type="text" class="mowi-filter-search" placeholder="<?php esc_attr_e( 'Search description', 'mowi' ); ?>" />
		<select class="mowi-filter-category">
			<option value=""><?php esc_html_e( 'All categories', 'mowi' ); ?></option>
			<?php
			foreach ( $categories as $cat ) {
				$depth  = (int) ( $cat['depth'] ?? 0 );
				$indent = str_repeat( '— ', $depth );
				echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
			}
			?>
		</select>
		<button type="button" class="mowi-filter-apply"><?php esc_html_e( 'Apply', 'mowi' ); ?></button>
	</div>
	<div class="mowi-list-accordion" id="mowi-list-accordion" data-month="<?php echo esc_attr( $month ); ?>" data-year="<?php echo esc_attr( $year ); ?>">
		<p class="mowi-loading"><?php esc_html_e( 'Loading…', 'mowi' ); ?></p>
	</div>
	<div class="mowi-edit-tx-modal" id="mowi-edit-tx-modal" hidden>
		<div class="mowi-edit-tx-modal-inner">
			<h4><?php esc_html_e( 'Edit transaction', 'mowi' ); ?></h4>
			<form id="mowi-edit-tx-form">
				<input type="hidden" name="id" id="mowi-edit-tx-id" value="" />
				<div class="mowi-form-row">
					<label for="mowi-edit-tx-date"><?php esc_html_e( 'Date', 'mowi' ); ?></label>
					<input type="date" id="mowi-edit-tx-date" name="date" required />
				</div>
				<div class="mowi-form-row">
					<label for="mowi-edit-tx-time"><?php esc_html_e( 'Time', 'mowi' ); ?></label>
					<input type="time" id="mowi-edit-tx-time" name="time" />
				</div>
				<div class="mowi-form-row">
					<label for="mowi-edit-tx-description"><?php esc_html_e( 'Description', 'mowi' ); ?></label>
					<input type="text" id="mowi-edit-tx-description" name="description" />
				</div>
				<div class="mowi-form-row">
					<label for="mowi-edit-tx-category"><?php esc_html_e( 'Category', 'mowi' ); ?></label>
					<select id="mowi-edit-tx-category" name="category_id">
						<option value="0"><?php esc_html_e( 'Uncategorized', 'mowi' ); ?></option>
						<?php
						foreach ( $categories as $cat ) {
							$depth  = (int) ( $cat['depth'] ?? 0 );
							$indent = str_repeat( '— ', $depth );
							echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
						}
						?>
					</select>
				</div>
				<div class="mowi-form-row">
					<label for="mowi-edit-tx-amount"><?php esc_html_e( 'Amount', 'mowi' ); ?></label>
					<input type="number" id="mowi-edit-tx-amount" name="amount" step="0.01" min="0" required />
				</div>
				<div class="mowi-form-row">
					<label><?php esc_html_e( 'Type', 'mowi' ); ?></label>
					<select id="mowi-edit-tx-type" name="type">
						<option value="expense"><?php esc_html_e( 'Expense', 'mowi' ); ?></option>
						<option value="income"><?php esc_html_e( 'Income', 'mowi' ); ?></option>
					</select>
				</div>
				<div class="mowi-form-row">
					<button type="submit" class="mowi-submit"><?php esc_html_e( 'Update', 'mowi' ); ?></button>
					<button type="button" class="mowi-edit-tx-cancel"><?php esc_html_e( 'Cancel', 'mowi' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
