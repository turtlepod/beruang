<?php
/**
 * Template for [mowi-form] shortcode.
 *
 * @package Mowi
 * @var string $today      Default date (Y-m-d).
 * @var string $time       Default time (H:i).
 * @var string $currency   Currency code.
 * @var array  $categories Flat categories from DB.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mowi mowi-form-wrapper">
	<form class="mowi-form" id="mowi-transaction-form">
		<div class="mowi-form-row mowi-datetime-row">
			<label for="mowi-date"><?php esc_html_e( 'Date', 'mowi' ); ?></label>
			<span class="mowi-datetime-wrap">
				<input type="date" id="mowi-date" name="date" value="<?php echo esc_attr( $today ); ?>" required />
				<input type="time" id="mowi-time" name="time" value="<?php echo esc_attr( $time ); ?>" />
			</span>
		</div>
		<div class="mowi-form-row">
			<label for="mowi-description"><?php esc_html_e( 'Description', 'mowi' ); ?></label>
			<input type="text" id="mowi-description" name="description" placeholder="<?php esc_attr_e( 'Description', 'mowi' ); ?>" />
		</div>
		<div class="mowi-form-row">
			<label for="mowi-category"><?php esc_html_e( 'Category', 'mowi' ); ?></label>
			<span class="mowi-category-wrap">
				<select id="mowi-category" name="category_id">
					<option value="0"><?php esc_html_e( 'Uncategorized', 'mowi' ); ?></option>
					<?php
					foreach ( $categories as $cat ) {
						$depth  = (int) ( $cat['depth'] ?? 0 );
						$indent = str_repeat( '— ', $depth );
						echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
					}
					?>
				</select>
				<button type="button" class="mowi-manage-categories-btn" title="<?php esc_attr_e( 'Manage categories', 'mowi' ); ?>" aria-label="<?php esc_attr_e( 'Manage categories', 'mowi' ); ?>"><?php \Mowi\mowi_icon( 'list' ); ?></button>
			</span>
		</div>
		<div class="mowi-form-row mowi-amount-row">
			<label for="mowi-amount"><?php esc_html_e( 'Amount', 'mowi' ); ?></label>
			<span class="mowi-amount-wrap">
				<input type="number" id="mowi-amount" name="amount" step="0.01" min="0" value="" required placeholder="0" />
				<button type="button" class="mowi-calc-btn" title="<?php esc_attr_e( 'Calculator', 'mowi' ); ?>" aria-label="<?php esc_attr_e( 'Calculator', 'mowi' ); ?>"><?php \Mowi\mowi_icon( 'calc' ); ?></button>
			</span>
			<span class="mowi-currency-label"><?php echo esc_html( $currency ); ?></span>
		</div>
		<div class="mowi-form-row mowi-type-row">
			<label><?php esc_html_e( 'Type', 'mowi' ); ?></label>
			<div class="mowi-type-toggle">
				<button type="button" class="mowi-type-btn active" data-type="expense"><?php esc_html_e( 'Expense', 'mowi' ); ?></button>
				<button type="button" class="mowi-type-btn" data-type="income"><?php esc_html_e( 'Income', 'mowi' ); ?></button>
			</div>
			<input type="hidden" name="type" id="mowi-type" value="expense" />
		</div>
		<div class="mowi-form-row mowi-submit-row">
			<button type="submit" class="mowi-submit"><?php esc_html_e( 'Save', 'mowi' ); ?></button>
			<span class="mowi-form-message" aria-live="polite"></span>
		</div>
	</form>
	<div class="mowi-calc-modal" id="mowi-calc-modal" hidden>
		<div class="mowi-calc-content">
			<input type="text" class="mowi-calc-display" readonly value="0" />
			<div class="mowi-calc-buttons"></div>
			<button type="button" class="mowi-calc-close"><?php esc_html_e( 'Close & Insert', 'mowi' ); ?></button>
		</div>
	</div>
	<div class="mowi-categories-modal" id="mowi-categories-modal" hidden>
		<div class="mowi-categories-modal-inner">
			<h4><?php esc_html_e( 'Manage categories', 'mowi' ); ?></h4>
			<form id="mowi-category-form" class="mowi-categories-form">
				<input type="hidden" id="mowi-cat-edit-id" name="id" value="" />
				<div class="mowi-form-row">
					<label for="mowi-cat-name"><?php esc_html_e( 'Name', 'mowi' ); ?></label>
					<input type="text" id="mowi-cat-name" name="name" required />
				</div>
				<div class="mowi-form-row">
					<label for="mowi-cat-parent"><?php esc_html_e( 'Parent', 'mowi' ); ?></label>
					<select id="mowi-cat-parent" name="parent_id">
						<option value="0">—</option>
					</select>
				</div>
				<div class="mowi-form-row">
					<button type="submit" class="mowi-submit mowi-cat-submit-add"><?php esc_html_e( 'Add category', 'mowi' ); ?></button>
					<button type="button" class="mowi-cat-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancel', 'mowi' ); ?></button>
				</div>
			</form>
			<div class="mowi-categories-list-wrap">
				<p class="mowi-loading mowi-cat-loading"><?php esc_html_e( 'Loading…', 'mowi' ); ?></p>
				<ul class="mowi-categories-list" id="mowi-categories-list"></ul>
			</div>
			<button type="button" class="mowi-categories-modal-close"><?php esc_html_e( 'Close', 'mowi' ); ?></button>
		</div>
	</div>
</div>
