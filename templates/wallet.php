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
	<div class="beruang-categories-list-wrap">
		<ul class="beruang-categories-list" id="beruang-wallet-list" data-default-wallet-id="<?php echo esc_attr( (string) $default_wallet_id ); ?>">
			<li class="beruang-wallet-item" data-id="0" data-name="<?php esc_attr_e( 'No Wallet', 'beruang' ); ?>" data-default="1">
				<span class="beruang-cat-item-name"><?php esc_html_e( 'No Wallet', 'beruang' ); ?></span>
			</li>
			<?php
			foreach ( $wallets as $wallet ) {
				?>
				<li class="beruang-wallet-item" data-id="<?php echo esc_attr( $wallet['id'] ); ?>" data-name="<?php echo esc_attr( $wallet['name'] ); ?>" data-default="0">
					<span class="beruang-cat-item-name"><?php echo esc_html( $wallet['name'] ); ?></span>
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
