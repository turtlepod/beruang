<?php
/**
 * Shared transaction form partial for add and edit.
 *
 * @package Beruang
 * @var string $mode         'add' or 'edit'.
 * @var string $form_id      Form element ID.
 * @var string $field_prefix Prefix for input IDs (e.g. 'beruang' or 'beruang-edit-tx').
 * @var string $today        Default date (Y-m-d).
 * @var string $time         Default time (H:i).
 * @var string $currency     Currency code.
 * @var array  $categories   Flat categories from DB.
 * @var array  $wallets      Wallets from DB.
 * @var int    $default_wallet_id Default wallet ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_edit        = ( 'edit' === $mode );
$date_id        = $field_prefix . '-date';
$time_id        = $field_prefix . '-time';
$desc_id        = $field_prefix . '-description';
$note_id        = $field_prefix . '-note';
$wallet_id      = $field_prefix . '-wallet';
$cat_id         = $field_prefix . '-category';
$amt_id         = $field_prefix . '-amount';
$type_id        = $field_prefix . '-type';
$has_wallets    = ! empty( $wallets );
$default_wallet = null === $default_wallet_id ? '' : (string) $default_wallet_id;
?>
<form class="beruang-form beruang-transaction-form" id="<?php echo esc_attr( $form_id ); ?>" data-mode="<?php echo esc_attr( $mode ); ?>">
	<?php if ( $is_edit ) : ?>
		<input type="hidden" name="id" id="<?php echo esc_attr( $field_prefix ); ?>-id" value="" />
	<?php endif; ?>
	<?php if ( $has_wallets ) : ?>
	<div class="beruang-form-row">
		<label for="<?php echo esc_attr( $wallet_id ); ?>"><?php esc_html_e( 'Wallet', 'beruang' ); ?></label>
		<select id="<?php echo esc_attr( $wallet_id ); ?>" name="wallet_id" data-default-wallet-id="<?php echo esc_attr( $default_wallet ); ?>">
			<option value=""<?php echo selected( '', $default_wallet, false ); ?>><?php esc_html_e( 'No Wallet', 'beruang' ); ?></option>
			<?php
			foreach ( $wallets as $wallet ) {
				echo '<option value="' . esc_attr( $wallet['id'] ) . '"' . selected( (string) $wallet['id'], $default_wallet, false ) . '>' . esc_html( $wallet['name'] ) . '</option>';
			}
			?>
		</select>
	</div>
	<?php else : ?>
		<input type="hidden" name="wallet_id" value="" />
	<?php endif; ?>
	<div class="beruang-form-row beruang-datetime-row">
		<label for="<?php echo esc_attr( $date_id ); ?>"><?php esc_html_e( 'Date', 'beruang' ); ?></label>
		<span class="beruang-datetime-wrap">
			<input type="date" id="<?php echo esc_attr( $date_id ); ?>" name="date" value="<?php echo esc_attr( $today ); ?>" required />
			<input type="time" id="<?php echo esc_attr( $time_id ); ?>" name="time" value="<?php echo esc_attr( $time ); ?>" />
		</span>
	</div>
	<div class="beruang-form-row beruang-description-row">
		<label for="<?php echo esc_attr( $desc_id ); ?>"><?php esc_html_e( 'Description', 'beruang' ); ?></label>
		<span class="beruang-wrap">
			<input type="text" id="<?php echo esc_attr( $desc_id ); ?>" name="description" placeholder="<?php esc_attr_e( 'Meal, Gas, etc...', 'beruang' ); ?>" required />
			<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--secondary beruang-note-btn" title="<?php esc_attr_e( 'Add note', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Add note', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'note', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		</span>
		<textarea id="<?php echo esc_attr( $note_id ); ?>" name="note" rows="3" hidden aria-hidden="true" tabindex="-1"></textarea>
	</div>
	<div class="beruang-form-row beruang-category-row">
		<label for="<?php echo esc_attr( $cat_id ); ?>"><?php esc_html_e( 'Category', 'beruang' ); ?></label>
		<span class="beruang-wrap">
			<select id="<?php echo esc_attr( $cat_id ); ?>" name="category_id">
				<option value="0"><?php esc_html_e( 'Uncategorized', 'beruang' ); ?></option>
				<?php
				foreach ( $categories as $cat ) {
					$depth  = (int) ( $cat['depth'] ?? 0 );
					$indent = str_repeat( '— ', $depth );
					echo '<option value="' . esc_attr( $cat['id'] ) . '">' . esc_html( $indent . $cat['name'] ) . '</option>';
				}
				?>
			</select>
			<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--secondary beruang-manage-categories-btn" title="<?php esc_attr_e( 'Manage categories', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Manage categories', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'list' ); ?></button>
		</span>
	</div>
	<div class="beruang-form-row beruang-amount-row">
		<label for="<?php echo esc_attr( $amt_id ); ?>"><?php esc_html_e( 'Amount', 'beruang' ); ?> <span class="beruang-label-currency">(<?php echo esc_html( $currency ); ?>)</span></label>
		<span class="beruang-wrap">
			<input type="number" id="<?php echo esc_attr( $amt_id ); ?>" name="amount" step="1" min="0" value="" required placeholder="0" />
			<button type="button" class="beruang-btn beruang-btn--icon beruang-btn--secondary beruang-calc-btn" title="<?php esc_attr_e( 'Calculator', 'beruang' ); ?>" aria-label="<?php esc_attr_e( 'Calculator', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'calc' ); ?></button>
		</span>
	</div>
	<div class="beruang-form-row beruang-type-row">
		<label><?php esc_html_e( 'Type', 'beruang' ); ?></label>
		<div class="beruang-type-toggle">
			<button type="button" class="beruang-type-btn active" data-type="expense"><?php esc_html_e( 'Expense', 'beruang' ); ?></button>
			<button type="button" class="beruang-type-btn" data-type="income"><?php esc_html_e( 'Income', 'beruang' ); ?></button>
		</div>
		<input type="hidden" name="type" id="<?php echo esc_attr( $type_id ); ?>" value="expense" />
	</div>
	<div class="beruang-form-row beruang-submit-row<?php echo $is_edit ? ' beruang-modal-actions' : ''; ?>">
		<button type="submit" class="beruang-btn beruang-btn--primary beruang-submit<?php echo $is_edit ? ' beruang-modal-save' : ''; ?>"><?php esc_html_e( 'Save', 'beruang' ); ?></button>
		<?php if ( $is_edit ) : ?>
			<button type="button" class="beruang-btn beruang-btn--secondary beruang-modal-cancel beruang-edit-tx-cancel"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
		<?php endif; ?>
		<span class="beruang-form-message" aria-live="polite"></span>
	</div>
</form>
