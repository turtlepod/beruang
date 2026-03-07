<?php
/**
 * Shared modals (calculator, categories) output once per page.
 *
 * @package Beruang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="beruang beruang-calc-modal beruang-modal" id="beruang-calc-modal" hidden>
	<div class="beruang-calc-content">
		<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		<input type="text" class="beruang-calc-display" readonly value="0" />
		<div class="beruang-calc-buttons"></div>
		<div class="beruang-calc-bottom">
			<button type="button" class="beruang-calc-insert-close"><?php esc_html_e( 'Insert & Close', 'beruang' ); ?></button>
			<button type="button" class="beruang-calc-clear" aria-label="<?php esc_attr_e( 'Clear', 'beruang' ); ?>">C</button>
			<button type="button" class="beruang-calc-equals">=</button>
		</div>
	</div>
</div>
<div class="beruang beruang-categories-modal beruang-modal" id="beruang-categories-modal" hidden>
	<div class="beruang-modal-dialog">
		<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		<div class="beruang-categories-modal-inner">
		<h4><?php esc_html_e( 'Manage categories', 'beruang' ); ?></h4>
		<form id="beruang-category-form" class="beruang-categories-form">
			<input type="hidden" id="beruang-cat-edit-id" name="id" value="" />
			<div class="beruang-form-row">
				<label for="beruang-cat-name"><?php esc_html_e( 'Name', 'beruang' ); ?></label>
				<input type="text" id="beruang-cat-name" name="name" required />
			</div>
			<div class="beruang-form-row">
				<label for="beruang-cat-parent"><?php esc_html_e( 'Parent', 'beruang' ); ?></label>
				<select id="beruang-cat-parent" name="parent_id">
					<option value="0">—</option>
				</select>
			</div>
			<div class="beruang-form-row">
				<button type="submit" class="beruang-submit beruang-cat-submit-add"><?php esc_html_e( 'Add category', 'beruang' ); ?></button>
				<button type="button" class="beruang-cat-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
				<span class="beruang-form-message" aria-live="polite"></span>
			</div>
		</form>
		<div class="beruang-categories-list-wrap">
			<p class="beruang-loading beruang-cat-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
			<ul class="beruang-categories-list" id="beruang-categories-list"></ul>
		</div>
		<button type="button" class="beruang-categories-modal-close"><?php esc_html_e( 'Close', 'beruang' ); ?></button>
		</div>
	</div>
</div>
