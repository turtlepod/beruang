<?php
/**
 * Template for [beruang-form] shortcode.
 *
 * @package Beruang
 * @var string $today      Default date (Y-m-d).
 * @var string $time       Default time (H:i).
 * @var string $currency   Currency code.
 * @var array  $categories Flat categories from DB.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-form-wrapper">
	<form class="beruang-form" id="beruang-transaction-form">
		<div class="beruang-form-row beruang-datetime-row">
			<label for="beruang-date"><?php esc_html_e( 'Date', 'beruang' ); ?></label>
			<span class="beruang-datetime-wrap">
				<input type="date" id="beruang-date" name="date" value="<?php echo esc_attr( $today ); ?>" required />
				<input type="time" id="beruang-time" name="time" value="<?php echo esc_attr( $time ); ?>" />
			</span>
		</div>
		<div class="beruang-form-row">
			<label for="beruang-description"><?php esc_html_e( 'Description', 'beruang' ); ?></label>
			<input type="text" id="beruang-description" name="description" placeholder="<?php esc_attr_e( 'Description', 'beruang' ); ?>" />
		</div>
		<div class="beruang-form-row">
			<label for="beruang-category"><?php esc_html_e( 'Category', 'beruang' ); ?></label>
			<span class="beruang-category-wrap">
				<select id="beruang-category" name="category_id">
					<option value="0"><?php esc_html_e( 'Uncategorized', 'beruang' ); ?></option>
					<?php
					foreach ( $categories as $cat ) {
						$depth  = (int) ( $cat['depth'] ?? 0 );
						$indent = str_repeat( '— ', $depth );
						echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
					}
					?>
				</select>
				<button type="button" class="beruang-manage-categories-btn" title="<?php esc_attr_e( 'Manage categories', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Manage categories', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'list' ); ?></button>
			</span>
		</div>
		<div class="beruang-form-row beruang-amount-row">
			<label for="beruang-amount"><?php esc_html_e( 'Amount', 'beruang' ); ?></label>
			<span class="beruang-amount-wrap">
				<input type="number" id="beruang-amount" name="amount" step="0.01" min="0" value="" required placeholder="0" />
				<button type="button" class="beruang-calc-btn" title="<?php esc_attr_e( 'Calculator', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Calculator', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'calc' ); ?></button>
			</span>
			<span class="beruang-currency-label"><?php echo esc_html( $currency ); ?></span>
		</div>
		<div class="beruang-form-row beruang-type-row">
			<label><?php esc_html_e( 'Type', 'beruang' ); ?></label>
			<div class="beruang-type-toggle">
				<button type="button" class="beruang-type-btn active" data-type="expense"><?php esc_html_e( 'Expense', 'beruang' ); ?></button>
				<button type="button" class="beruang-type-btn" data-type="income"><?php esc_html_e( 'Income', 'beruang' ); ?></button>
			</div>
			<input type="hidden" name="type" id="beruang-type" value="expense" />
		</div>
		<div class="beruang-form-row beruang-submit-row">
			<button type="submit" class="beruang-submit"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
			<span class="beruang-form-message" aria-live="polite"></span>
		</div>
	</form>
	<div class="beruang-calc-modal" id="beruang-calc-modal" hidden>
		<div class="beruang-calc-content">
			<input type="text" class="beruang-calc-display" readonly value="0" />
			<div class="beruang-calc-buttons"></div>
			<button type="button" class="beruang-calc-close"><?php esc_html_e( 'Close & Insert', 'beruang' ); ?></button>
		</div>
	</div>
	<div class="beruang-categories-modal" id="beruang-categories-modal" hidden>
		<div class="beruang-categories-modal-inner">
			<h4><?php esc_html_e( 'Manage categories', 'beruang' ); ?></h4>
			<form id="beruang-category-form" class="beruang-categories-form">
				<input type="hidden" id="beruang-cat-edit-id" name="id" value="" />
				<div class="beruang-form-row">
					<label for="beruang-cat-name"><?php esc_html_e( 'Name', 'beruang' ); ?></label>
					<input type="text" id="beruang-cat-name" name="name" required />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-cat-parent"><?php esc_html_e( 'Parent', 'beruang' ); ?></label>
					<select id="beruang-cat-parent" name="parent_id">
						<option value="0">—</option>
					</select>
				</div>
				<div class="beruang-form-row">
					<button type="submit" class="beruang-submit beruang-cat-submit-add"><?php esc_html_e( 'Add category', 'beruang' ); ?></button>
					<button type="button" class="beruang-cat-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
				</div>
			</form>
			<div class="beruang-categories-list-wrap">
				<p class="beruang-loading beruang-cat-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
				<ul class="beruang-categories-list" id="beruang-categories-list"></ul>
			</div>
			<button type="button" class="beruang-categories-modal-close"><?php esc_html_e( 'Close', 'beruang' ); ?></button>
		</div>
	</div>
</div>
