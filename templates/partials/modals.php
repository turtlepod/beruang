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
			<div class="beruang-calc-bottom-row">
				<button type="button" class="beruang-calc-clear" aria-label="<?php esc_attr_e( 'Clear', 'beruang' ); ?>">C</button>
				<button type="button" class="beruang-calc-backspace" aria-label="<?php esc_attr_e( 'Backspace', 'beruang' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M576 64H205.26A63.97 63.97 0 0 0 160 82.75L9.37 233.37c-12.5 12.5-12.5 32.76 0 45.25L160 429.25c12 12 28.28 18.75 45.25 18.75H576c35.35 0 64-28.65 64-64V128c0-35.35-28.65-64-64-64zm-84.69 254.06c6.25 6.25 6.25 16.38 0 22.63l-22.62 22.62c-6.25 6.25-16.38 6.25-22.63 0L384 301.25l-62.06 62.06c-6.25 6.25-16.38 6.25-22.63 0l-22.62-22.62c-6.25-6.25-6.25-16.38 0-22.63L338.75 256l-62.06-62.06c-6.25-6.25-6.25-16.38 0-22.63l22.62-22.62c6.25-6.25 16.38-6.25 22.63 0L384 210.75l62.06-62.06c6.25-6.25 16.38-6.25 22.63 0l22.62 22.62c6.25 6.25 6.25 16.38 0 22.63L429.25 256l62.06 62.06z"/></svg></button>
				<button type="button" class="beruang-calc-equals">=</button>
			</div>
			<button type="button" class="beruang-calc-insert-close"><?php esc_html_e( 'Insert & Close', 'beruang' ); ?></button>
		</div>
	</div>
</div>
<div class="beruang beruang-note-modal beruang-modal" id="beruang-note-modal" hidden>
	<div class="beruang-modal-dialog">
		<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		<div class="beruang-modal-inner beruang-note-modal-inner">
			<h4><?php esc_html_e( 'Transaction note', 'beruang' ); ?></h4>
			<div class="beruang-form-row">
				<label for="beruang-note-modal-text"><?php esc_html_e( 'Note', 'beruang' ); ?></label>
				<textarea id="beruang-note-modal-text" rows="5" placeholder="<?php esc_attr_e( 'Optional details...', 'beruang' ); ?>"></textarea>
			</div>
			<div class="beruang-form-row beruang-modal-actions">
				<button type="button" class="beruang-btn beruang-btn--primary beruang-submit beruang-modal-save beruang-note-save"><?php esc_html_e( 'Save note', 'beruang' ); ?></button>
				<button type="button" class="beruang-btn beruang-btn--secondary beruang-modal-cancel beruang-note-cancel"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
			</div>
		</div>
	</div>
</div>
<div class="beruang beruang-categories-modal beruang-modal" id="beruang-categories-modal" hidden>
	<div class="beruang-modal-dialog">
		<button type="button" class="beruang-modal-close-x" aria-label="<?php esc_attr_e( 'Close', 'beruang' ); ?>"><?php \Beruang\beruang_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?></button>
		<div class="beruang-modal-inner beruang-categories-modal-inner">
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
				<button type="submit" class="beruang-btn beruang-btn--primary beruang-submit beruang-cat-submit-add"><?php esc_html_e( 'Add category', 'beruang' ); ?></button>
				<button type="button" class="beruang-btn beruang-btn--secondary beruang-cat-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancel', 'beruang' ); ?></button>
				<span class="beruang-form-message" aria-live="polite"></span>
			</div>
		</form>
		<div class="beruang-categories-list-wrap">
			<p class="beruang-loading beruang-cat-loading"><?php esc_html_e( 'Loading…', 'beruang' ); ?></p>
			<ul class="beruang-categories-list" id="beruang-categories-list"></ul>
		</div>
		<button type="button" class="beruang-btn beruang-btn--secondary beruang-categories-modal-close"><?php esc_html_e( 'Close', 'beruang' ); ?></button>
		</div>
	</div>
</div>
