<?php
/**
 * Template for [beruang-wallet] shortcode.
 *
 * @package Beruang
 * @var array $wallets Wallets from DB.
 * @var int   $default_wallet_id Default wallet ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-wallet-wrapper">
	<div class="beruang-wallet-header">
		<h2 class="beruang-section-title"><?php esc_html_e( 'Wallets', 'beruang' ); ?></h2>
		<div class="beruang-wallet-header-actions">
			<button type="button" class="beruang-wallet-add" title="<?php esc_attr_e( 'Add wallet', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Add wallet', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'add', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		</div>
	</div>
	<div class="beruang-form-row beruang-wallet-default-row"<?php echo count( $wallets ) >= 2 ? '' : ' hidden'; ?>>
		<label for="beruang-default-wallet-select"><?php esc_html_e( 'Default wallet', 'beruang' ); ?></label>
		<select id="beruang-default-wallet-select" class="beruang-default-wallet-select" data-default-wallet-id="<?php echo esc_attr( null === $default_wallet_id ? '' : (string) $default_wallet_id ); ?>">
			<option value=""><?php esc_html_e( 'No Wallet', 'beruang' ); ?></option>
			<?php foreach ( $wallets as $wallet ) : ?>
				<option value="<?php echo esc_attr( (string) $wallet['id'] ); ?>"<?php echo selected( (string) $wallet['id'], (string) $default_wallet_id, false ); ?>><?php echo esc_html( $wallet['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="beruang-categories-list-wrap">
		<ul class="beruang-categories-list" id="beruang-wallet-list" data-default-wallet-id="<?php echo esc_attr( (string) $default_wallet_id ); ?>">
			<?php
			foreach ( $wallets as $wallet ) {
				$initial_amount = isset( $wallet['initial_amount'] ) ? (float) $wallet['initial_amount'] : 0.0;
				$initial_date   = isset( $wallet['initial_date'] ) ? (string) $wallet['initial_date'] : current_time( 'Y-m-d' );
				$current_amount = isset( $wallet['current_amount'] ) ? (float) $wallet['current_amount'] : $initial_amount;
				?>
				<li class="beruang-wallet-item" data-id="<?php echo esc_attr( $wallet['id'] ); ?>" data-name="<?php echo esc_attr( $wallet['name'] ); ?>" data-initial-amount="<?php echo esc_attr( (string) $initial_amount ); ?>" data-initial-date="<?php echo esc_attr( $initial_date ); ?>">
					<span class="beruang-cat-item-name"><?php echo esc_html( $wallet['name'] ); ?></span>
					<?php /* translators: 1: amount, 2: date (Y-m-d). */ ?>
					<span class="beruang-wallet-meta"><?php echo esc_html( sprintf( __( 'Baseline: %1$s on %2$s', 'beruang' ), number_format_i18n( $initial_amount, 2 ), $initial_date ) ); ?></span>
					<?php /* translators: %s: current wallet amount. */ ?>
					<span class="beruang-wallet-balance"><?php echo esc_html( sprintf( __( 'Current: %s', 'beruang' ), number_format_i18n( $current_amount, 2 ) ) ); ?></span>
					<button type="button" class="beruang-action-edit" title="<?php esc_attr_e( 'Edit', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Edit', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'edit', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
					<button type="button" class="beruang-action-delete" title="<?php esc_attr_e( 'Delete', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Delete', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'trash', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<div class="beruang-wallet-modal beruang-modal" id="beruang-wallet-modal" hidden>
		<div class="beruang-modal-dialog">
			<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
			<div class="beruang-categories-modal-inner">
				<h4><?php esc_html_e( 'Add / Edit wallet', 'beruang' ); ?></h4>
				<form id="beruang-wallet-form" class="beruang-categories-form">
					<input type="hidden" id="beruang-wallet-edit-id" name="id" value="" />
					<div class="beruang-form-row">
						<label for="beruang-wallet-name"><?php esc_html_e( 'Name', 'beruang' ); ?></label>
						<input type="text" id="beruang-wallet-name" name="name" required />
					</div>
					<div class="beruang-form-row">
						<label for="beruang-wallet-initial-amount"><?php esc_html_e( 'Amount on selected date', 'beruang' ); ?></label>
						<input type="number" id="beruang-wallet-initial-amount" name="initial_amount" step="0.01" />
					</div>
					<div class="beruang-form-row">
						<label for="beruang-wallet-initial-date"><?php esc_html_e( 'Selected date', 'beruang' ); ?></label>
						<input type="date" id="beruang-wallet-initial-date" name="initial_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required />
					</div>
					<div class="beruang-form-row">
						<label for="beruang-wallet-set-default"><?php esc_html_e( 'Set as default wallet', 'beruang' ); ?></label>
						<input type="checkbox" id="beruang-wallet-set-default" name="set_as_default" value="1" />
					</div>
					<div class="beruang-form-row beruang-modal-actions">
						<button type="submit" class="beruang-submit beruang-wallet-submit-add beruang-modal-save"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
						<button type="button" class="beruang-modal-cancel beruang-wallet-cancel-edit"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
						<span class="beruang-form-message" aria-live="polite"></span>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
