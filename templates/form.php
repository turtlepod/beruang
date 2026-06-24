<?php
/**
 * Template for [beruang-form] shortcode.
 *
 * @package Beruang
 * @var string $today       Default date (Y-m-d).
 * @var string $time        Default time (H:i).
 * @var string $currency    Currency code.
 * @var array  $categories  Flat categories from DB.
 * @var array  $wallets     Wallets from DB.
 * @var int    $default_wallet_id Default wallet ID.
 * @var int    $decimal_places    Number of decimal places.
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
			'mode'              => 'add',
			'form_id'           => 'beruang-transaction-form',
			'field_prefix'      => 'beruang',
			'today'             => $today,
			'time'              => $time,
			'currency'          => $currency,
			'categories'        => $categories,
			'wallets'           => $wallets,
			'default_wallet_id' => $default_wallet_id,
			'decimal_places'    => $decimal_places,
		)
	);
	?>
</div>
