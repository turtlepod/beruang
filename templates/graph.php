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
	<div class="beruang-graph-controls">
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
