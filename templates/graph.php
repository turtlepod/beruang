<?php
/**
 * Template for [mowi-graph] shortcode.
 *
 * @package Mowi
 * @var int $year Current year.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mowi mowi-graph-wrapper">
	<div class="mowi-graph-controls">
		<select class="mowi-graph-year">
			<?php for ( $y = $year; $y >= $year - 5; $y-- ) { ?>
				<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>><?php echo (int) $y; ?></option>
			<?php } ?>
		</select>
		<select class="mowi-graph-group">
			<option value="month"><?php esc_html_e( 'By month', 'mowi' ); ?></option>
			<option value="category"><?php esc_html_e( 'By category', 'mowi' ); ?></option>
		</select>
	</div>
	<div class="mowi-graph-canvas-wrap">
		<canvas id="mowi-graph-canvas" width="400" height="300"></canvas>
	</div>
</div>
