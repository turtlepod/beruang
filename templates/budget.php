<?php
/**
 * Template for [beruang-budget] shortcode.
 *
 * @package Beruang
 * @var string $currency   Currency code.
 * @var array  $categories Flat categories from DB.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-budget-wrapper">
	<div class="beruang-budget-header">
		<h3 class="beruang-budget-title"><?php esc_html_e( 'Budgets', 'beruang' ); ?></h3>
		<button type="button" class="beruang-budget-add"><?php esc_html_e( 'Add budget', 'beruang' ); ?></button>
	</div>
	<div class="beruang-budget-list" id="beruang-budget-list">
		<p class="beruang-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
	</div>
	<div class="beruang-budget-modal" id="beruang-budget-modal" hidden>
		<div class="beruang-budget-modal-inner">
			<h4><?php esc_html_e( 'Add / Edit budget', 'beruang' ); ?></h4>
			<form id="beruang-budget-form">
				<input type="hidden" name="id" value="" />
				<div class="beruang-form-row">
					<label for="beruang-budget-name"><?php esc_html_e( 'Budget name', 'beruang' ); ?></label>
					<input type="text" id="beruang-budget-name" name="name" required />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-budget-target"><?php esc_html_e( 'Target', 'beruang' ); ?></label>
					<input type="number" id="beruang-budget-target" name="target_amount" step="0.01" min="0" required /> <?php echo esc_html( $currency ); ?>
				</div>
				<div class="beruang-form-row">
					<label><?php esc_html_e( 'Type', 'beruang' ); ?></label>
					<select name="type">
						<option value="monthly"><?php esc_html_e( 'Monthly', 'beruang' ); ?></option>
						<option value="yearly"><?php esc_html_e( 'Yearly', 'beruang' ); ?></option>
					</select>
				</div>
				<div class="beruang-form-row">
					<label><?php esc_html_e( 'Categories', 'beruang' ); ?></label>
					<div class="beruang-budget-categories-list">
						<?php
						foreach ( $categories as $cat ) {
							$depth  = (int) ( $cat['depth'] ?? 0 );
							$indent = str_repeat( '— ', $depth );
							?>
							<label><input type="checkbox" name="category_ids[]" value="<?php echo esc_attr( $cat['id'] ); ?>" /> <?php echo esc_html( $indent . $cat['name'] ); ?></label>
						<?php } ?>
					</div>
				</div>
				<div class="beruang-form-row">
					<button type="submit" class="beruang-submit"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
					<button type="button" class="beruang-budget-modal-close"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
