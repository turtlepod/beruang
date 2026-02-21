(function () {
	'use strict';

	var beruang = window.beruangData || {};
	var ajaxUrl = beruang.ajax_url || '';
	var nonce = beruang.nonce || '';
	var i18n = beruang.i18n || {};

	function request(action, data, method) {
		data = data || {};
		data.action = action;
		data.nonce = nonce;
		method = method || 'POST';
		return jQuery.ajax({
			url: ajaxUrl,
			type: method,
			data: data,
		});
	}

	// --- Form ---
	jQuery(function () {
		var $form = jQuery('#beruang-transaction-form');
		if (!$form.length) return;

		var $typeField = jQuery('#beruang-type');
		var $message = $form.find('.beruang-form-message');

		jQuery('.beruang-type-btn').on('click', function () {
			var t = jQuery(this).data('type');
			jQuery('.beruang-type-btn').removeClass('active');
			jQuery(this).addClass('active');
			$typeField.val(t);
		});

		$form.on('submit', function (e) {
			e.preventDefault();
			$message.text('');
			var data = {
				date: $form.find('[name="date"]').val(),
				time: $form.find('[name="time"]').val() || null,
				description: $form.find('[name="description"]').val(),
				category_id: $form.find('[name="category_id"]').val() || 0,
				amount: $form.find('[name="amount"]').val(),
				type: $typeField.val(),
			};
			request('beruang_save_transaction', data).done(function (r) {
				if (r.success) {
					$message.text(i18n.saved || 'Saved.').css('color', '#00a32a');
					$form.find('[name="description"]').val('');
					$form.find('[name="amount"]').val('');
				} else {
					$message.text(r.data && r.data.message ? r.data.message : (i18n.error || 'Error')).css('color', '#d63638');
				}
			}).fail(function () {
				$message.text(i18n.error || 'Error').css('color', '#d63638');
			});
		});

		// Manage categories modal
		var $catModal = jQuery('#beruang-categories-modal');
		var $catForm = jQuery('#beruang-category-form');
		var $catList = jQuery('#beruang-categories-list');
		var $catEditId = jQuery('#beruang-cat-edit-id');
		var $catName = jQuery('#beruang-cat-name');
		var $catParent = jQuery('#beruang-cat-parent');
		var $catSubmitBtn = $catForm.find('.beruang-cat-submit-add');
		var $catCancelBtn = $catForm.find('.beruang-cat-cancel-edit');
		var $mainCategorySelect = jQuery('#beruang-category');

		function buildCategoryOptions(categories, excludeId) {
			var opts = '<option value="0">—</option>';
			(categories || []).forEach(function (c) {
				if (excludeId && parseInt(c.id, 10) === parseInt(excludeId, 10)) return;
				var depth = parseInt(c.depth, 10) || 0;
				var indent = new Array(depth + 1).join('— ');
				opts += '<option value="' + c.id + '">' + escapeHtml(indent + (c.name || '')) + '</option>';
			});
			return opts;
		}

		function buildMainCategoryOptions(categories) {
			var opts = '<option value="0">' + (i18n.uncategorized || 'Uncategorized') + '</option>';
			(categories || []).forEach(function (c) {
				var depth = parseInt(c.depth, 10) || 0;
				var indent = new Array(depth + 1).join('— ');
				opts += '<option value="' + c.id + '">' + escapeHtml(indent + (c.name || '')) + '</option>';
			});
			return opts;
		}

		function refreshCategoriesInModal(excludeId, selectedParentId) {
			request('beruang_get_categories').done(function (r) {
				if (!r.success || !r.data || !r.data.categories) return;
				var cats = r.data.categories;
				$catParent[0].innerHTML = buildCategoryOptions(cats, excludeId);
				if (selectedParentId !== undefined && selectedParentId !== null) $catParent.val(selectedParentId);
				$mainCategorySelect[0].innerHTML = buildMainCategoryOptions(cats);
				var listHtml = '';
				cats.forEach(function (c) {
					var depth = parseInt(c.depth, 10) || 0;
					var indent = new Array(depth + 1).join('— ');
					listHtml += '<li class="beruang-cat-item" data-id="' + c.id + '" data-name="' + escapeHtml(c.name || '') + '" data-parent="' + (c.parent_id || 0) + '">';
					listHtml += '<span class="beruang-cat-item-name">' + escapeHtml(indent + (c.name || '')) + '</span>';
					listHtml += ' <button type="button" class="beruang-cat-edit-btn">' + (i18n.edit || 'Edit') + '</button>';
					listHtml += ' <button type="button" class="beruang-cat-delete-btn">' + (i18n.delete || 'Delete') + '</button>';
					listHtml += '</li>';
				});
				$catList.html(listHtml || '<li class="beruang-cat-empty">' + (i18n.no_categories || 'No categories yet.') + '</li>');
				jQuery('.beruang-cat-loading').hide();
			});
		}

		jQuery('.beruang-manage-categories-btn').on('click', function () {
			$catEditId.val('');
			$catName.val('');
			$catParent.val('0');
			$catSubmitBtn.text(i18n.add_category || 'Add category').show();
			$catCancelBtn.hide();
			$catModal.attr('hidden', false);
			jQuery('.beruang-cat-loading').show();
			$catList.empty();
			refreshCategoriesInModal();
		});

		$catModal.find('.beruang-categories-modal-close').on('click', function () { $catModal.attr('hidden', true); });
		$catModal.on('click', function (e) {
			if (e.target === $catModal[0]) $catModal.attr('hidden', true);
		});

		$catForm.on('submit', function (e) {
			e.preventDefault();
			var id = $catEditId.val();
			var name = $catName.val();
			var parentId = $catParent.val() || '0';
			request('beruang_save_category', { id: id || 0, name: name, parent_id: parentId }).done(function (r) {
				if (r.success) {
					$catEditId.val('');
					$catName.val('');
					$catParent.val('0');
					$catSubmitBtn.text(i18n.add_category || 'Add category');
					$catCancelBtn.hide();
					refreshCategoriesInModal();
				}
			});
		});

		$catCancelBtn.on('click', function () {
			$catEditId.val('');
			$catName.val('');
			$catParent.val('0');
			$catSubmitBtn.text(i18n.add_category || 'Add category').show();
			$catCancelBtn.hide();
		});

		jQuery(document).on('click', '.beruang-cat-edit-btn', function () {
			var $li = jQuery(this).closest('.beruang-cat-item');
			var id = $li.data('id');
			var name = $li.data('name');
			var parent = $li.data('parent');
			$catEditId.val(id);
			$catName.val(name);
			$catSubmitBtn.text(i18n.update_category || 'Update category').show();
			$catCancelBtn.show();
			refreshCategoriesInModal(id, parent || '0');
		});

		jQuery(document).on('click', '.beruang-cat-delete-btn', function () {
			var id = jQuery(this).closest('.beruang-cat-item').data('id');
			if (!id || !confirm(i18n.confirm_delete_category || 'Delete this category?')) return;
			request('beruang_delete_category', { id: id }).done(function (r) {
				if (r.success) refreshCategoriesInModal();
			});
		});

		// Calculator button: simple modal with basic calc
		var $calcBtn = $form.find('.beruang-calc-btn');
		var $calcModal = jQuery('#beruang-calc-modal');
		var $calcDisplay = $calcModal.find('.beruang-calc-display');
		var $amountInput = $form.find('#beruang-amount');
		if ($calcModal.length && $calcBtn.length) {
			$calcBtn.on('click', function () {
				$calcDisplay.val($amountInput.val() || '0');
				$calcModal.attr('hidden', false);
			});
			$calcModal.find('.beruang-calc-close').on('click', function () {
				$amountInput.val($calcDisplay.val());
				$calcModal.attr('hidden', true);
			});
			$calcModal.on('click', function (e) {
				if (e.target === $calcModal[0]) $calcModal.attr('hidden', true);
			});
			// Minimal calc: 0-9, 00, 000, ., +, -, *, /, =
			var calcVal = '0';
			var calcOp = null;
			var calcPrev = null;
			function updateDisplay() { $calcDisplay.val(calcVal); }
			var btns = [
				['7','8','9','/'],
				['4','5','6','*'],
				['1','2','3','-'],
				['0','00','000','.'],
				['=','+','-','*']
			];
			var $container = $calcModal.find('.beruang-calc-buttons');
			$container.empty();
			btns.forEach(function (row) {
				row.forEach(function (key) {
					var b = jQuery('<button type="button">').text(key);
					b.on('click', function () {
						if (key === '=') {
							if (calcOp && calcPrev !== null) {
								var a = parseFloat(calcPrev);
								var bNum = parseFloat(calcVal);
								if (calcOp === '+') calcVal = String(a + bNum);
								else if (calcOp === '-') calcVal = String(a - bNum);
								else if (calcOp === '*') calcVal = String(a * bNum);
								else if (calcOp === '/') calcVal = bNum !== 0 ? String(a / bNum) : '0';
								calcOp = null;
								calcPrev = null;
							}
						} else if (key === '+' || key === '-' || key === '*' || key === '/') {
							calcPrev = calcVal;
							calcOp = key;
							calcVal = '0';
						} else {
							if (calcVal === '0' && key !== '.') calcVal = key;
							else calcVal += key;
						}
						updateDisplay();
					});
					$container.append(b);
				});
			});
		}
	});

	// --- List ---
	jQuery(function () {
		var $accordion = jQuery('#beruang-list-accordion');
		if (!$accordion.length) return;

		var $filters = jQuery('#beruang-list-filters');
		var $filterBtn = jQuery('.beruang-filter-btn');
		if ($filterBtn.length) {
			$filterBtn.on('click', function () {
				var hidden = $filters.attr('hidden');
				if (hidden !== undefined && hidden !== false) $filters.attr('hidden', false);
				else $filters.attr('hidden', true);
			});
		}

		function loadList() {
			var month = $accordion.data('month');
			var year = $accordion.data('year');
			var search = jQuery('.beruang-filter-search').val() || '';
			var categoryId = jQuery('.beruang-filter-category').val() || '';
			$accordion.html('<p class="beruang-loading">' + (i18n.loading || 'Loading…') + '</p>');
			request('beruang_get_transactions', {
				month: month,
				year: year,
				search: search,
				category_id: categoryId,
				page: 1
			}, 'GET').done(function (r) {
				if (!r.success || !r.data || !r.data.items) {
					$accordion.html('<p class="beruang-loading">' + (i18n.error || 'Error') + '</p>');
					return;
				}
				var items = r.data.items;
				var byDate = {};
				items.forEach(function (tx) {
					var d = tx.date;
					if (!byDate[d]) byDate[d] = [];
					byDate[d].push(tx);
				});
				var html = '';
				var dates = Object.keys(byDate).sort().reverse();
				dates.forEach(function (date) {
					var dayItems = byDate[date];
					var dayTotal = 0;
					dayItems.forEach(function (tx) {
						var amt = parseFloat(tx.amount);
						dayTotal += tx.type === 'income' ? amt : -amt;
					});
					html += '<div class="beruang-accordion-group">';
					html += '<div class="beruang-accordion-head"><span>' + date + '</span><span>' + formatNum(dayTotal) + '</span></div>';
					html += '<div class="beruang-accordion-body">';
					dayItems.forEach(function (tx) {
						html += '<div class="beruang-transaction-item" data-id="' + tx.id + '">';
						html += '<span class="beruang-tx-desc">' + escapeHtml(tx.description || '—') + '</span>';
						html += '<span class="beruang-tx-amount ' + tx.type + '">' + (tx.type === 'income' ? '+' : '-') + formatNum(Math.abs(parseFloat(tx.amount))) + '</span>';
						html += '<span class="beruang-tx-actions"><button type="button" class="beruang-edit-tx-btn">' + (i18n.edit || 'Edit') + '</button></span>';
						html += '</div>';
					});
					html += '</div></div>';
				});
				if (!dates.length) html = '<p class="beruang-loading">' + (i18n.no_transactions || 'No transactions.') + '</p>';
				$accordion.html(html);
			}).fail(function () {
				$accordion.html('<p class="beruang-loading">' + (i18n.error || 'Error') + '</p>');
			});
		}

		jQuery('.beruang-filter-apply').on('click', loadList);

		// Edit transaction modal
		var $editModal = jQuery('#beruang-edit-tx-modal');
		var $editForm = jQuery('#beruang-edit-tx-form');
		if ($editModal.length && $editForm.length) {
			jQuery(document).on('click', '.beruang-edit-tx-btn', function () {
				var id = jQuery(this).closest('.beruang-transaction-item').data('id');
				if (!id) return;
				request('beruang_get_transaction', { id: id }, 'GET').done(function (r) {
					if (!r.success || !r.data || !r.data.transaction) return;
					var t = r.data.transaction;
					jQuery('#beruang-edit-tx-id').val(t.id);
					jQuery('#beruang-edit-tx-date').val(t.date || '');
					jQuery('#beruang-edit-tx-time').val(t.time || '');
					jQuery('#beruang-edit-tx-description').val(t.description || '');
					jQuery('#beruang-edit-tx-category').val(t.category_id || '0');
					jQuery('#beruang-edit-tx-amount').val(t.amount);
					jQuery('#beruang-edit-tx-type').val(t.type === 'income' ? 'income' : 'expense');
					$editModal.attr('hidden', false);
				});
			});
			jQuery('.beruang-edit-tx-cancel').on('click', function () { $editModal.attr('hidden', true); });
			$editModal.on('click', function (e) {
				if (e.target === $editModal[0]) $editModal.attr('hidden', true);
			});
			$editForm.on('submit', function (e) {
				e.preventDefault();
				var data = {
					id: jQuery('#beruang-edit-tx-id').val(),
					date: jQuery('#beruang-edit-tx-date').val(),
					time: jQuery('#beruang-edit-tx-time').val() || null,
					description: jQuery('#beruang-edit-tx-description').val(),
					category_id: jQuery('#beruang-edit-tx-category').val() || 0,
					amount: jQuery('#beruang-edit-tx-amount').val(),
					type: jQuery('#beruang-edit-tx-type').val()
				};
				request('beruang_update_transaction', data).done(function (r) {
					if (r.success) {
						$editModal.attr('hidden', true);
						loadList();
					}
				});
			});
		}

		loadList();
	});

	function formatNum(n) {
		var dec = beruang.decimal_sep || ',';
		var thou = beruang.thousands_sep || '.';
		var s = Number(n).toFixed(dec === '.' ? 2 : 0);
		var parts = s.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thou);
		return parts.join(dec) + ' ' + (beruang.currency || 'IDR');
	}

	function escapeHtml(s) {
		var div = document.createElement('div');
		div.textContent = s;
		return div.innerHTML;
	}

	// --- Graph ---
	jQuery(function () {
		var $wrap = jQuery('.beruang-graph-wrapper');
		var $canvas = jQuery('#beruang-graph-canvas');
		if (!$canvas.length || typeof window.Chart === 'undefined') return;

		var chart = null;

		function loadGraph() {
			var year = parseInt(jQuery('.beruang-graph-year').val(), 10);
			var groupBy = jQuery('.beruang-graph-group').val();
			request('beruang_get_graph_data', { year: year, group_by: groupBy }, 'GET').done(function (r) {
				if (!r.success || !r.data || !r.data.data) return;
				renderChart(r.data.data, groupBy, year);
			});
		}

		function renderChart(data, groupBy, year) {
			var ctx = $canvas[0].getContext('2d');
			if (chart) chart.destroy();

			if (groupBy === 'category') {
				var expenseLabels = [];
				var expenseValues = [];
				var incomeLabels = [];
				var incomeValues = [];
				data.forEach(function (row) {
					if (row.type === 'expense') {
						expenseLabels.push(row.label || 'Uncategorized');
						expenseValues.push(parseFloat(row.total));
					} else {
						incomeLabels.push(row.label || 'Uncategorized');
						incomeValues.push(parseFloat(row.total));
					}
				});
				chart = new window.Chart(ctx, {
					type: 'doughnut',
					data: {
						labels: expenseLabels.length ? expenseLabels : [i18n.no_data || 'No data'],
						datasets: [{
							data: expenseValues.length ? expenseValues : [1],
							backgroundColor: ['#2271b1','#135e96','#0c4a6e','#72aee6','#3582c4']
						}]
					},
					options: { responsive: true, maintainAspectRatio: true }
				});
			} else {
				var months = [];
				var expenseByMonth = [];
				var incomeByMonth = [];
				for (var m = 1; m <= 12; m++) {
					months.push(m + '/' + year);
					expenseByMonth.push(0);
					incomeByMonth.push(0);
				}
				data.forEach(function (row) {
					var m = parseInt(row.month, 10) - 1;
					if (m >= 0 && m < 12) {
						var tot = parseFloat(row.total);
						if (row.type === 'expense') expenseByMonth[m] = tot;
						else incomeByMonth[m] = tot;
					}
				});
				chart = new window.Chart(ctx, {
					type: 'bar',
					data: {
						labels: months,
						datasets: [
							{ label: i18n.expense || 'Expense', data: expenseByMonth, backgroundColor: '#d63638' },
							{ label: i18n.income || 'Income', data: incomeByMonth, backgroundColor: '#00a32a' }
						]
					},
					options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true } } }
				});
			}
		}

		jQuery('.beruang-graph-year, .beruang-graph-group').on('change', loadGraph);
		// Chart.js may load after DOM ready
		if (window.Chart) loadGraph();
		else jQuery(window).on('load', function () { setTimeout(loadGraph, 100); });
	});

	// --- Budget ---
	jQuery(function () {
		var $list = jQuery('#beruang-budget-list');
		var $modal = jQuery('#beruang-budget-modal');
		var $form = jQuery('#beruang-budget-form');
		if (!$list.length) return;

		function loadBudgets() {
			$list.html('<p class="beruang-loading">' + (i18n.loading || 'Loading…') + '</p>');
			request('beruang_get_budgets').done(function (r) {
				if (!r.success || !r.data || !r.data.budgets) {
					$list.html('<p class="beruang-loading">' + (i18n.error || 'Error') + '</p>');
					return;
				}
				var budgets = r.data.budgets;
				var html = '';
				budgets.forEach(function (b) {
					var pct = Math.round(parseFloat(b.progress) || 0);
					var over = pct > 100;
					html += '<div class="beruang-budget-card" data-id="' + b.id + '">';
					html += '<h4>' + escapeHtml(b.name) + ' <small>(' + (b.type === 'yearly' ? (i18n.yearly || 'Yearly') : (i18n.monthly || 'Monthly')) + ')</small></h4>';
					html += '<div class="beruang-budget-progress-wrap"><div class="beruang-budget-progress-bar ' + (over ? 'over' : '') + '" style="width:' + Math.min(pct, 100) + '%"></div></div>';
					html += '<div class="beruang-budget-meta">' + formatNum(b.spent) + ' / ' + formatNum(b.target_amount) + ' (' + pct + '%)</div>';
					html += '<span class="beruang-budget-actions"><button type="button" class="beruang-budget-edit" data-id="' + b.id + '">' + (i18n.edit || 'Edit') + '</button> <button type="button" class="beruang-budget-delete" data-id="' + b.id + '">' + (i18n.delete || 'Delete') + '</button></span>';
					html += '</div>';
				});
				if (!budgets.length) html = '<p class="beruang-loading">' + (i18n.no_budgets || 'No budgets.') + '</p>';
				$list.html(html);

				jQuery('.beruang-budget-delete').on('click', function () {
					var id = jQuery(this).data('id');
					if (!id || !confirm(i18n.confirm_delete || 'Delete this budget?')) return;
					request('beruang_delete_budget', { id: id }).done(function (res) {
						if (res.success) loadBudgets();
					});
				});
				jQuery('.beruang-budget-edit').on('click', function () {
					var id = jQuery(this).data('id');
					if (!id) return;
					request('beruang_get_budget', { id: id }, 'GET').done(function (r) {
						if (!r.success || !r.data || !r.data.budget) return;
						var b = r.data.budget;
						$form.find('[name="id"]').val(b.id);
						$form.find('[name="name"]').val(b.name || '');
						$form.find('[name="target_amount"]').val(b.target_amount || '');
						$form.find('[name="type"]').val(b.type === 'yearly' ? 'yearly' : 'monthly');
						$form.find('[name="category_ids[]"]').prop('checked', false);
						(b.category_ids || []).forEach(function (cid) {
							$form.find('[name="category_ids[]"][value="' + cid + '"]').prop('checked', true);
						});
						$modal.attr('hidden', false);
					});
				});
			}).fail(function () {
				$list.html('<p class="beruang-loading">' + (i18n.error || 'Error') + '</p>');
			});
		}

		jQuery('.beruang-budget-add').on('click', function () {
			$form.find('[name="id"]').val('');
			$form.find('[name="name"]').val('');
			$form.find('[name="target_amount"]').val('');
			$form.find('[name="type"]').val('monthly');
			$form.find('[name="category_ids[]"]').prop('checked', false);
			$modal.attr('hidden', false);
		});

		jQuery('.beruang-budget-modal-close, .beruang-budget-modal').on('click', function (e) {
			if (e.target === this || jQuery(e.target).hasClass('beruang-budget-modal-close')) $modal.attr('hidden', true);
		});

		$form.on('submit', function (e) {
			e.preventDefault();
			var id = $form.find('[name="id"]').val();
			var name = $form.find('[name="name"]').val();
			var target = $form.find('[name="target_amount"]').val();
			var type = $form.find('[name="type"]').val();
			var catIds = [];
			$form.find('[name="category_ids[]"]:checked').each(function () { catIds.push(jQuery(this).val()); });
			request('beruang_save_budget', {
				id: id || 0,
				name: name,
				target_amount: target,
				type: type,
				category_ids: catIds
			}).done(function (r) {
				if (r.success) {
					$modal.attr('hidden', true);
					loadBudgets();
				}
			});
		});

		loadBudgets();
	});
})();
