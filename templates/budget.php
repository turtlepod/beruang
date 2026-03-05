<?php
/**
 * Template for [beruang-budget] shortcode.
 *
 * @package Beruang
 * @var string $currency    Currency code.
 * @var array  $categories  Flat categories from DB.
 * @var int    $year        Current year for filter.
 * @var int    $month       Current month for filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-budget-wrapper">
	<div class="beruang-budget-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Budgets', 'beruang' ); ?></h2>
		<div class="beruang-budget-header-actions">
			<button type="button" class="beruang-budget-add" title="<?php esc_attr_e( 'Add budget', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Add budget', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'add', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
			<button type="button" class="beruang-filter-btn" title="<?php esc_attr_e( 'Filter', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'filter', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		</div>
	</div>
	<div class="beruang-budget-filters" id="beruang-budget-filters" hidden>
		<select class="beruang-filter-year" aria-label="<?php esc_attr_e( 'Year', 'beruang' ); ?>">
			<?php
			$current_year = (int) current_time( 'Y' );
			for ( $y = $current_year; $y >= $current_year - 10; $y-- ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $y ), selected( $year, $y, false ), esc_html( (string) $y ) );
			}
			?>
		</select>
		<select class="beruang-filter-month" aria-label="<?php esc_attr_e( 'Month', 'beruang' ); ?>">
			<?php
			$month_names = array(
				1  => __( 'January', 'beruang' ),
				2  => __( 'February', 'beruang' ),
				3  => __( 'March', 'beruang' ),
				4  => __( 'April', 'beruang' ),
				5  => __( 'May', 'beruang' ),
				6  => __( 'June', 'beruang' ),
				7  => __( 'July', 'beruang' ),
				8  => __( 'August', 'beruang' ),
				9  => __( 'September', 'beruang' ),
				10 => __( 'October', 'beruang' ),
				11 => __( 'November', 'beruang' ),
				12 => __( 'December', 'beruang' ),
			);
			foreach ( $month_names as $m => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $m ), selected( $month, $m, false ), esc_html( $label ) );
			}
			?>
		</select>
		<button type="button" class="beruang-filter-apply"><?php esc_html_e( 'Apply', 'beruang' ); ?></button>
		<button type="button" class="beruang-filter-reset"><?php esc_html_e( 'Reset', 'beruang' ); ?></button>
	</div>
	<div class="beruang-budget-list" id="beruang-budget-list" data-year="<?php echo esc_attr( $year ); ?>" data-month="<?php echo esc_attr( $month ); ?>">
		<p class="beruang-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
	</div>
	<div class="beruang-budget-modal beruang-modal" id="beruang-budget-modal" hidden>
		<div class="beruang-modal-dialog">
			<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>">×</button>
			<div class="beruang-budget-modal-inner">
			<h4><?php esc_html_e( 'Add / Edit budget', 'beruang' ); ?></h4>
			<form id="beruang-budget-form">
				<input type="hidden" name="id" value="" />
				<div class="beruang-form-row">
					<label for="beruang-budget-name"><?php esc_html_e( 'Budget name', 'beruang' ); ?></label>
					<input type="text" id="beruang-budget-name" name="name" required />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-budget-target"><?php esc_html_e( 'Target', 'beruang' ); ?> <span class="beruang-label-currency">(<?php echo esc_html( $currency ); ?>)</span></label>
					<input type="number" id="beruang-budget-target" name="target_amount" step="1" min="0" required />
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
				<div class="beruang-form-row beruang-modal-actions">
					<button type="submit" class="beruang-submit beruang-modal-save"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
					<button type="button" class="beruang-modal-cancel beruang-budget-modal-close"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
				</div>
			</form>
			</div>
		</div>
	</div>
</div>
