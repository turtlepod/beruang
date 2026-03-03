<?php
/**
 * JavaScript templates for Beruang frontend.
 *
 * Uses {{ data.key }} for escaped output and {{{ data.key }}} for raw HTML.
 * Output in wp_footer when Beruang shortcodes are present.
 *
 * @package Beruang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script type="text/html" id="tmpl-beruang-option">
	<option value="{{ data.value }}">{{ data.label }}</option>
</script>

<script type="text/html" id="tmpl-beruang-cat-item">
	<li class="beruang-cat-item" data-id="{{ data.id }}" data-name="{{ data.name }}" data-parent="{{ data.parent }}">
		<span class="beruang-cat-item-name">{{ data.displayName }}</span>
		<button type="button" class="beruang-action-edit">{{ data.editLabel }}</button>
		<button type="button" class="beruang-action-delete">{{ data.deleteLabel }}</button>
	</li>
</script>

<script type="text/html" id="tmpl-beruang-cat-empty">
	<li class="beruang-cat-empty">{{ data.message }}</li>
</script>

<script type="text/html" id="tmpl-beruang-message">
	<p class="beruang-loading">{{ data.message }}</p>
</script>

<script type="text/html" id="tmpl-beruang-transaction-item">
	<div class="beruang-transaction-item" data-id="{{ data.id }}">
		<span class="beruang-tx-datetime">
			<span class="beruang-tx-date">{{ data.dateDisplay }}</span>
			<span class="beruang-tx-time">{{ data.timeDisplay }}</span>
		</span>
		<span class="beruang-tx-desc">{{ data.description }}</span>
		<span class="beruang-tx-amount {{ data.type }}">{{ data.amountDisplay }}</span>
		<span class="beruang-tx-actions">
			<button type="button" class="beruang-action-edit">{{ data.editLabel }}</button>
			<button type="button" class="beruang-action-delete">{{ data.deleteLabel }}</button>
		</span>
	</div>
</script>

<script type="text/html" id="tmpl-beruang-accordion-group">
	<div class="beruang-accordion-group">
		<div class="beruang-accordion-head"><span>{{ data.date }}</span><span>{{ data.dayTotal }}</span></div>
		<div class="beruang-accordion-body">{{{ data.itemsHtml }}}</div>
	</div>
</script>

<script type="text/html" id="tmpl-beruang-accordion-month">
	<div class="beruang-accordion-month{{ data.monthClass }}" data-month-key="{{ data.monthKey }}">
		<div class="beruang-accordion-month-head" role="button" tabindex="0" aria-expanded="{{ data.expandedAttr }}">
			<span>{{ data.monthLabel }}</span>
			<span class="beruang-accordion-month-total"><span class="beruang-accordion-toggle" aria-hidden="true"></span>{{ data.monthTotal }}</span>
		</div>
		<div class="beruang-accordion-month-body">{{{ data.itemsHtml }}}</div>
	</div>
</script>

<script type="text/html" id="tmpl-beruang-budget-card">
	<div class="beruang-budget-card" data-id="{{ data.id }}">
		<h4>{{ data.name }} <small>({{ data.typeLabel }})</small></h4>
		<div class="beruang-budget-progress-wrap"><div class="beruang-budget-progress-bar {{ data.progressClass }}" style="width:{{ data.progressWidth }}%"></div></div>
		<div class="beruang-budget-meta">{{ data.spentFormatted }} / {{ data.targetFormatted }} ({{ data.pct }}%)</div>
		<span class="beruang-budget-actions">
			<button type="button" class="beruang-action-edit" data-id="{{ data.id }}">{{ data.editLabel }}</button>
			<button type="button" class="beruang-action-delete" data-id="{{ data.id }}">{{ data.deleteLabel }}</button>
		</span>
	</div>
</script>
