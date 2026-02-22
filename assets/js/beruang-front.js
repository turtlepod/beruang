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

	// Modal x close (dismiss without saving)
	jQuery(document).on('click', '.beruang-modal-close-x', function () {
		jQuery(this).closest('.beruang-modal').attr('hidden', true);
	});

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
		var optionTpl = wp.template('beruang-option');
		var catItemTpl = wp.template('beruang-cat-item');
		var catEmptyTpl = wp.template('beruang-cat-empty');

		function buildCategoryOptions(categories, excludeId) {
			var opts = optionTpl({ value: '0', label: '—' });
			(categories || []).forEach(function (c) {
				if (excludeId && parseInt(c.id, 10) === parseInt(excludeId, 10)) return;
				var depth = parseInt(c.depth, 10) || 0;
				var indent = new Array(depth + 1).join('— ');
				opts += optionTpl({ value: c.id, label: indent + (c.name || '') });
			});
			return opts;
		}

		function buildMainCategoryOptions(categories) {
			var opts = optionTpl({ value: '0', label: i18n.uncategorized || 'Uncategorized' });
			(categories || []).forEach(function (c) {
				var depth = parseInt(c.depth, 10) || 0;
				var indent = new Array(depth + 1).join('— ');
				opts += optionTpl({ value: c.id, label: indent + (c.name || '') });
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
						listHtml += catItemTpl({
							id: c.id,
							name: c.name || '',
							parent: c.parent_id || 0,
							displayName: indent + (c.name || ''),
							editLabel: i18n.edit || 'Edit',
							deleteLabel: i18n.delete || 'Delete'
						});
					});
				$catList.html(listHtml || catEmptyTpl({ message: i18n.no_categories || 'No categories yet.' }));
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

		jQuery(document).on('click', '.beruang-action-edit', function () {
			var $li = jQuery(this).closest('.beruang-cat-item');
			if (!$li.length) return;
			var id = $li.data('id');
			var name = $li.data('name');
			var parent = $li.data('parent');
			$catEditId.val(id);
			$catName.val(name);
			$catSubmitBtn.text(i18n.update_category || 'Update category').show();
			$catCancelBtn.show();
			refreshCategoriesInModal(id, parent || '0');
		});

		jQuery(document).on('click', '.beruang-action-delete', function () {
			var $li = jQuery(this).closest('.beruang-cat-item');
			if (!$li.length) return;
			var id = $li.data('id');
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
			$calcModal.find('.beruang-calc-insert-close').on('click', function () {
				$amountInput.val($calcDisplay.val());
				$calcModal.attr('hidden', true);
			});
			$calcModal.on('click', function (e) {
				if (e.target === $calcModal[0]) $calcModal.attr('hidden', true);
			});
			// Calc: 4x4 grid (7-9/÷, 4-6/×, 1-3/-, 0/000/./+), bottom row: Insert & Close, =
			var calcVal = '0';
			var calcOp = null;
			var calcPrev = null;
			function updateDisplay() { $calcDisplay.val(calcVal); }
			function doEquals() {
				if (calcOp && calcPrev !== null) {
					var a = parseFloat(calcPrev);
					var bNum = parseFloat(calcVal);
					var op = calcOp === '\u00f7' ? '/' : (calcOp === '\u00d7' ? '*' : calcOp);
					if (op === '+') calcVal = String(a + bNum);
					else if (op === '-') calcVal = String(a - bNum);
					else if (op === '*') calcVal = String(a * bNum);
					else if (op === '/') calcVal = bNum !== 0 ? String(a / bNum) : '0';
					calcOp = null;
					calcPrev = null;
				}
				updateDisplay();
			}
			var btns = [
				['7','8','9','\u00f7'],
				['4','5','6','\u00d7'],
				['1','2','3','-'],
				['0','000','.','+']
			];
			var $container = $calcModal.find('.beruang-calc-buttons');
			$container.empty();
			btns.forEach(function (row) {
				row.forEach(function (key) {
					var isOp = key === '+' || key === '-' || key === '*' || key === '/' || key === '\u00f7' || key === '\u00d7';
					var b = jQuery('<button type="button">').text(key).toggleClass('beruang-calc-op', isOp);
					b.on('click', function () {
						if (key === '+' || key === '-' || key === '*' || key === '/' || key === '\u00f7' || key === '\u00d7') {
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
			$calcModal.find('.beruang-calc-equals').on('click', doEquals);
		}
	});

	// --- List ---
	jQuery(function () {
		var $accordion = jQuery('#beruang-list-accordion');
		if (!$accordion.length) return;

		var $listWrap = $accordion.closest('.beruang-list-wrapper');
		var $filters = $listWrap.find('#beruang-list-filters');
		var $filterBtn = $listWrap.find('.beruang-filter-btn');
		if ($filterBtn.length) {
			$filterBtn.on('click', function () {
				var hidden = $filters.attr('hidden');
				if (hidden !== undefined && hidden !== false) $filters.attr('hidden', false);
				else $filters.attr('hidden', true);
			});
		}

		var msgTpl = wp.template('beruang-message');
		var txItemTpl = wp.template('beruang-transaction-item');
		var accordionTpl = wp.template('beruang-accordion-group');

		function loadList() {
			var $monthSel = $listWrap.find('.beruang-filter-month');
			var $yearSel = $listWrap.find('.beruang-filter-year');
			var month = $monthSel.length ? parseInt($monthSel.val(), 10) : $accordion.data('month');
			var year = $yearSel.length ? parseInt($yearSel.val(), 10) : $accordion.data('year');
			var search = $listWrap.find('.beruang-filter-search').val() || '';
			var categoryId = $listWrap.find('.beruang-filter-category').val() || '';
			$accordion.html(msgTpl({ message: i18n.loading || 'Loading…' }));
			request('beruang_get_transactions', {
				month: month,
				year: year,
				search: search,
				category_id: categoryId,
				page: 1
			}, 'GET').done(function (r) {
				if (!r.success || !r.data || !r.data.items) {
					$accordion.html(msgTpl({ message: i18n.error || 'Error' }));
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
					var itemsHtml = '';
					dayItems.forEach(function (tx) {
						itemsHtml += txItemTpl({
							id: tx.id,
							description: tx.description || '—',
							amountDisplay: (tx.type === 'income' ? '+' : '-') + formatNum(Math.abs(parseFloat(tx.amount))),
							type: tx.type,
							editLabel: i18n.edit || 'Edit',
							deleteLabel: i18n.delete || 'Delete'
						});
					});
					html += accordionTpl({
						date: formatWpDate(date, beruang.date_format || 'F j, Y'),
						dayTotal: formatNum(dayTotal),
						itemsHtml: itemsHtml
					});
				});
				if (!dates.length) html = msgTpl({ message: i18n.no_transactions || 'No transactions.' });
				$accordion.html(html);
			}).fail(function () {
				$accordion.html(msgTpl({ message: i18n.error || 'Error' }));
			});
		}

		jQuery('.beruang-filter-apply').on('click', loadList);
		jQuery('.beruang-filter-reset').on('click', function () {
			$listWrap.find('.beruang-filter-month').val($accordion.data('month'));
			$listWrap.find('.beruang-filter-year').val($accordion.data('year'));
			$listWrap.find('.beruang-filter-search').val('');
			$listWrap.find('.beruang-filter-category').val('');
			loadList();
		});

		// Edit transaction modal
		var $editModal = jQuery('#beruang-edit-tx-modal');
		var $editForm = jQuery('#beruang-edit-tx-form');
		if ($editModal.length && $editForm.length) {
			jQuery(document).on('click', '.beruang-action-edit', function () {
				var $item = jQuery(this).closest('.beruang-transaction-item');
				if (!$item.length) return;
				var id = $item.data('id');
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
			jQuery(document).on('click', '.beruang-action-delete', function () {
				var $item = jQuery(this).closest('.beruang-transaction-item');
				if (!$item.length) return;
				var id = $item.data('id');
				if (!id || !confirm(i18n.confirm_delete_transaction || 'Delete this transaction?')) return;
				request('beruang_delete_transaction', { id: id }).done(function (r) {
					if (r.success) loadList();
				});
			});
		}

		loadList();
	});

	function formatWpDate(dateStr, format) {
		if (!dateStr || !format) return dateStr || '';
		var parts = String(dateStr).split('-');
		if (parts.length !== 3) return dateStr;
		var y = parseInt(parts[0], 10);
		var m = parseInt(parts[1], 10) - 1;
		var d = parseInt(parts[2], 10);
		var date = new Date(y, m, d);
		if (isNaN(date.getTime())) return dateStr;
		var locale = beruang.locale || 'en-US';
		var intlOpts = { timeZone: 'UTC' };
		var day = date.getDate();
		var month = date.getMonth();
		var year = date.getFullYear();
		var dayOfWeek = date.getDay();
		var ord = ['th', 'st', 'nd', 'rd'];
		var suff = ord[day % 10 > 3 ? 0 : ((day % 100) - (day % 10) === 10 ? 0 : day % 10)];
		var F = new Intl.DateTimeFormat(locale, { month: 'long', ...intlOpts }).format(date);
		var M = new Intl.DateTimeFormat(locale, { month: 'short', ...intlOpts }).format(date);
		var l = new Intl.DateTimeFormat(locale, { weekday: 'long', ...intlOpts }).format(date);
		var D = new Intl.DateTimeFormat(locale, { weekday: 'short', ...intlOpts }).format(date);
		var tokens = {
			F: F, M: M, l: l, D: D,
			Y: String(year),
			y: String(year).slice(-2),
			n: String(month + 1),
			m: String(month + 1).padStart(2, '0'),
			j: String(day),
			d: String(day).padStart(2, '0'),
			S: suff
		};
		return format.replace(/(F|M|l|D|Y|y|n|m|j|d|S)/g, function (m) { return tokens[m] || m; });
	}

	function formatNum(n) {
		var dec = beruang.decimal_sep || ',';
		var thou = beruang.thousands_sep || '.';
		var s = Number(n).toFixed(dec === '.' ? 2 : 0);
		var parts = s.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thou);
		return parts.join(dec) + ' ' + (beruang.currency || 'IDR');
	}

	// --- Graph ---
	jQuery(function () {
		var $wrap = jQuery('.beruang-graph-wrapper');
		var $canvas = jQuery('#beruang-graph-canvas');
		if (!$canvas.length || typeof window.Chart === 'undefined') return;

		var $graphFilters = jQuery('#beruang-graph-filters');
		var $graphFilterBtn = $wrap.find('.beruang-filter-btn');
		if ($graphFilterBtn.length && $graphFilters.length) {
			$graphFilterBtn.on('click', function () {
				var hidden = $graphFilters.attr('hidden');
				if (hidden !== undefined && hidden !== false) $graphFilters.attr('hidden', false);
				else $graphFilters.attr('hidden', true);
			});
		}

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

		var msgTpl = wp.template('beruang-message');
		var budgetCardTpl = wp.template('beruang-budget-card');

		jQuery(document).on('click', '.beruang-action-delete', function () {
			var $btn = jQuery(this);
			if (!$btn.closest('.beruang-budget-card').length) return;
			var id = $btn.data('id');
			if (!id || !confirm(i18n.confirm_delete || 'Delete this budget?')) return;
			request('beruang_delete_budget', { id: id }).done(function (res) {
				if (res.success) loadBudgets();
			});
		});
		jQuery(document).on('click', '.beruang-action-edit', function () {
			var $btn = jQuery(this);
			if (!$btn.closest('.beruang-budget-card').length) return;
			var id = $btn.data('id');
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

		function loadBudgets() {
			$list.html(msgTpl({ message: i18n.loading || 'Loading…' }));
			request('beruang_get_budgets').done(function (r) {
				if (!r.success || !r.data || !r.data.budgets) {
					$list.html(msgTpl({ message: i18n.error || 'Error' }));
					return;
				}
				var budgets = r.data.budgets;
				var html = '';
				budgets.forEach(function (b) {
					var pct = Math.round(parseFloat(b.progress) || 0);
					var over = pct > 100;
					html += budgetCardTpl({
						id: b.id,
						name: b.name,
						typeLabel: b.type === 'yearly' ? (i18n.yearly || 'Yearly') : (i18n.monthly || 'Monthly'),
						progressWidth: Math.min(pct, 100),
						progressClass: over ? 'over' : '',
						spentFormatted: formatNum(b.spent),
						targetFormatted: formatNum(b.target_amount),
						pct: pct,
						editLabel: i18n.edit || 'Edit',
						deleteLabel: i18n.delete || 'Delete'
					});
				});
				if (!budgets.length) html = msgTpl({ message: i18n.no_budgets || 'No budgets.' });
				$list.html(html);
			}).fail(function () {
				$list.html(msgTpl({ message: i18n.error || 'Error' }));
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
