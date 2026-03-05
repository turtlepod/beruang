<?php
/**
 * Template for [beruang-form] shortcode.
 *
 * @package Beruang
 * @var string $today       Default date (Y-m-d).
 * @var string $time        Default time (H:i).
 * @var string $currency    Currency code.
 * @var array  $categories  Flat categories from DB.
 * @var string $amount_step Step attribute for amount input (e.g. '0.01').
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Beruang\output_modals_once();
?>
<div class="beruang beruang-form-wrapper">
	<?php
	\Beruang\shortcode_load_template(
		'partials/transaction-form.php',
		array(
			'mode'         => 'add',
			'form_id'      => 'beruang-transaction-form',
			'field_prefix' => 'beruang',
			'today'        => $today,
			'time'         => $time,
			'currency'     => $currency,
			'categories'   => $categories,
			'amount_step'  => $amount_step,
		)
	);
	?>
</div>
