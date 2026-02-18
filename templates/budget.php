<?php
/**
 * Template for [mowi-budget] shortcode.
 *
 * @package Mowi
 * @var string $currency   Currency code.
 * @var array  $categories Flat categories from DB.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mowi mowi-budget-wrapper">
	<div class="mowi-budget-header">
		<h3 class="mowi-budget-title"><?php esc_html_e( 'Budgets', 'mowi' ); ?></h3>
		<button type="button" class="mowi-budget-add"><?php esc_html_e( 'Add budget', 'mowi' ); ?></button>
	</div>
	<div class="mowi-budget-list" id="mowi-budget-list">
		<p class="mowi-loading"><?php esc_html_e( 'Loading…', 'mowi' ); ?></p>
	</div>
	<div class="mowi-budget-modal" id="mowi-budget-modal" hidden>
		<div class="mowi-budget-modal-inner">
			<h4><?php esc_html_e( 'Add / Edit budget', 'mowi' ); ?></h4>
			<form id="mowi-budget-form">
				<input type="hidden" name="id" value="" />
				<div class="mowi-form-row">
					<label for="mowi-budget-name"><?php esc_html_e( 'Budget name', 'mowi' ); ?></label>
					<input type="text" id="mowi-budget-name" name="name" required />
				</div>
				<div class="mowi-form-row">
					<label for="mowi-budget-target"><?php esc_html_e( 'Target', 'mowi' ); ?></label>
					<input type="number" id="mowi-budget-target" name="target_amount" step="0.01" min="0" required /> <?php echo esc_html( $currency ); ?>
				</div>
				<div class="mowi-form-row">
					<label><?php esc_html_e( 'Type', 'mowi' ); ?></label>
					<select name="type">
						<option value="monthly"><?php esc_html_e( 'Monthly', 'mowi' ); ?></option>
						<option value="yearly"><?php esc_html_e( 'Yearly', 'mowi' ); ?></option>
					</select>
				</div>
				<div class="mowi-form-row">
					<label><?php esc_html_e( 'Categories', 'mowi' ); ?></label>
					<div class="mowi-budget-categories-list">
						<?php
						foreach ( $categories as $cat ) {
							$depth  = (int) ( $cat['depth'] ?? 0 );
							$indent = str_repeat( '— ', $depth );
							?>
							<label><input type="checkbox" name="category_ids[]" value="<?php echo esc_attr( $cat['id'] ); ?>" /> <?php echo esc_html( $indent . $cat['name'] ); ?></label>
						<?php } ?>
					</div>
				</div>
				<div class="mowi-form-row">
					<button type="submit" class="mowi-submit"><?php esc_html_e( 'Save', 'mowi' ); ?></button>
					<button type="button" class="mowi-budget-modal-close"><?php esc_html_e( 'Cancel', 'mowi' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
