<?php
/**
 * Template for [beruang-graph] shortcode.
 *
 * @package Beruang
 * @var int $year Current year.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-graph-wrapper">
	<div class="beruang-graph-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Graph', 'beruang' ); ?></h2>
		<button type="button" class="beruang-filter-btn" title="<?php esc_attr_e( 'Filter', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Filter', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'filter', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
	</div>
	<div class="beruang-graph-filters" id="beruang-graph-filters" hidden>
		<select class="beruang-graph-year">
			<?php for ( $y = $year; $y >= $year - 5; $y-- ) { ?>
				<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>><?php echo (int) $y; ?></option>
			<?php } ?>
		</select>
		<select class="beruang-graph-group">
			<option value="month"><?php esc_html_e( 'By month', 'beruang' ); ?></option>
			<option value="category"><?php esc_html_e( 'By category', 'beruang' ); ?></option>
		</select>
	</div>
	<div class="beruang-graph-canvas-wrap">
		<canvas id="beruang-graph-canvas" width="400" height="300"></canvas>
	</div>
</div>
