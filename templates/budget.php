<?php
/**
 * Template for [beruang-budget] shortcode.
 *
 * @package Beruang
 * @var string $currency       Currency code.
 * @var array  $categories     Flat categories from DB.
 * @var int    $year           Current year for filter.
 * @var int    $month          Current month for filter.
 * @var int    $decimal_places Number of decimal places.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables passed via shortcode_load_template().
?>
<div class="beruang beruang-budget-wrapper">
	<div class="beruang-budget-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Budgets', 'beruang-budget' ); ?></h2>
		<div class="beruang-budget-header-actions">
			<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--primary beruang-budget-add" title="<?php esc_attr_e( 'Add budget', 'beruang-budget' ); ?>" aria-label="<?php esc_attr_e( 'Add budget', 'beruang-budget' ); ?>"><?php \BeruangBudget\beruang_icon( 'add', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
			<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--secondary beruang-filter-btn" title="<?php esc_attr_e( 'Filter', 'beruang-budget' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'beruang-budget' ); ?>"><?php \BeruangBudget\beruang_icon( 'filter', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		</div>
	</div>
	<div class="beruang-filters beruang-budget-filters" id="beruang-budget-filters" hidden>
		<select class="beruang-filter-year" aria-label="<?php esc_attr_e( 'Year', 'beruang-budget' ); ?>">
			<?php
			$current_year = (int) current_time( 'Y' );
			for ( $y = $current_year; $y >= $current_year - 10; $y-- ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $y ), selected( $year, $y, false ), esc_html( (string) $y ) );
			}
			?>
		</select>
		<select class="beruang-filter-month" aria-label="<?php esc_attr_e( 'Month', 'beruang-budget' ); ?>">
			<?php
			$month_names = array(
				1  => __( 'January', 'beruang-budget' ),
				2  => __( 'February', 'beruang-budget' ),
				3  => __( 'March', 'beruang-budget' ),
				4  => __( 'April', 'beruang-budget' ),
				5  => __( 'May', 'beruang-budget' ),
				6  => __( 'June', 'beruang-budget' ),
				7  => __( 'July', 'beruang-budget' ),
				8  => __( 'August', 'beruang-budget' ),
				9  => __( 'September', 'beruang-budget' ),
				10 => __( 'October', 'beruang-budget' ),
				11 => __( 'November', 'beruang-budget' ),
				12 => __( 'December', 'beruang-budget' ),
			);
			foreach ( $month_names as $m => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $m ), selected( $month, $m, false ), esc_html( $label ) );
			}
			?>
		</select>
		<div class="beruang-filter-actions">
			<button type="button" class="beruang-btn beruang-btn--primary beruang-filter-apply"><?php esc_html_e( 'Apply', 'beruang-budget' ); ?></button>
			<button type="button" class="beruang-btn beruang-btn--secondary beruang-filter-reset"><?php esc_html_e( 'Reset', 'beruang-budget' ); ?></button>
		</div>
	</div>
	<div class="beruang-budget-list" id="beruang-budget-list" data-year="<?php echo esc_attr( $year ); ?>" data-month="<?php echo esc_attr( $month ); ?>">
		<p class="beruang-loading"><?php esc_html_e( 'Loading…', 'beruang-budget' ); ?></p>
	</div>
	<div class="beruang-budget-modal beruang-modal" id="beruang-budget-modal" hidden>
		<div class="beruang-modal-dialog">
			<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang-budget' ); ?>"><?php \BeruangBudget\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
			<div class="beruang-modal-inner beruang-budget-modal-inner">
			<h4><?php esc_html_e( 'Add / Edit budget', 'beruang-budget' ); ?></h4>
			<form id="beruang-budget-form">
				<input type="hidden" name="id" value="" />
				<div class="beruang-form-row">
					<label for="beruang-budget-name"><?php esc_html_e( 'Budget name', 'beruang-budget' ); ?></label>
					<input type="text" id="beruang-budget-name" name="name" required />
				</div>
				<div class="beruang-form-row">
					<label for="beruang-budget-target"><?php esc_html_e( 'Target', 'beruang-budget' ); ?> <span class="beruang-label-currency">(<?php echo esc_html( $currency ); ?>)</span></label>
					<input type="number" id="beruang-budget-target" name="target_amount" step="<?php echo $decimal_places > 0 ? esc_attr( '0.' . str_repeat( '0', $decimal_places - 1 ) . '1' ) : '1'; ?>" min="0" required />
				</div>
				<div class="beruang-form-row">
					<label><?php esc_html_e( 'Type', 'beruang-budget' ); ?></label>
					<select name="type">
						<option value="monthly"><?php esc_html_e( 'Monthly', 'beruang-budget' ); ?></option>
						<option value="yearly"><?php esc_html_e( 'Yearly', 'beruang-budget' ); ?></option>
					</select>
				</div>
				<div class="beruang-form-row">
					<label><?php esc_html_e( 'Categories', 'beruang-budget' ); ?></label>
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
					<button type="submit" class="beruang-btn beruang-btn--primary beruang-submit beruang-modal-save"><?php esc_html_e( 'Save', 'beruang-budget' ); ?></button>
					<button type="button" class="beruang-btn beruang-btn--secondary beruang-modal-cancel beruang-budget-modal-close"><?php esc_html_e( 'Cancel', 'beruang-budget' ); ?></button>
				</div>
			</form>
			</div>
		</div>
	</div>
</div>
