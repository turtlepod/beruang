(function () {
	'use strict';

	var beruang = window.beruangData || {};
	var restUrl = beruang.rest_url || '';
	var restNonce = beruang.rest_nonce || '';
	var i18n = beruang.i18n || {};

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function beruangTemplate(name) {
		var script = document.getElementById('tmpl-' + name);
		if (!script) return function () { return ''; };
		var html = script.textContent || script.innerText || '';
		return function (data) {
			data = data || {};
			return html
				.replace(/\{\{\{\s*data\.(\w+)\s*\}\}\}/g, function (_, key) {
					var val = data[key];
					return val !== undefined && val !== null ? String(val) : '';
				})
				.replace(/\{\{\s*data\.(\w+)\s*\}\}/g, function (_, key) {
					var val = data[key];
					return escapeHtml(val !== undefined && val !== null ? val : '');
				});
		};
	}

	function request(method, path, data) {
		data = data || {};
		var url = restUrl + path;
		var opts = {
			method: method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': restNonce,
			},
		};
		if (method === 'POST' || method === 'PUT') {
			if (Object.keys(data).length) opts.body = JSON.stringify(data);
		}
		if (method === 'GET' && Object.keys(data).length) {
			url += '?' + new URLSearchParams(data).toString();
		}
		return fetch(url, opts).then(function (r) {
			if (!r.ok) {
				return r.json().then(function (body) {
					var msg = body && body.data && body.data.message ? body.data.message : (body && body.message) || 'Error';
					return { success: false, data: { message: msg } };
				}).catch(function () {
					return { success: false, data: { message: 'Error' } };
				});
			}
			return r.json();
		}).catch(function () {
			return { success: false, data: { message: 'Error' } };
		});
	}

	// Modal x close (dismiss without saving)
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.beruang-modal-close-x');
		if (!btn) return;
		var modal = btn.closest('.beruang-modal');
		if (modal) modal.hidden = true;
	});

	// --- Form ---
	function initForm() {
		var form = document.getElementById('beruang-transaction-form');
		if (!form) return;

		var typeField = document.getElementById('beruang-type');
		var message = form.querySelector('.beruang-form-message');
		var dateInput = document.getElementById('beruang-date');
		var timeInput = document.getElementById('beruang-time');

		function setCurrentDateTime() {
			var now = new Date();
			var y = now.getFullYear();
			var m = String(now.getMonth() + 1).padStart(2, '0');
			var d = String(now.getDate()).padStart(2, '0');
			var h = String(now.getHours()).padStart(2, '0');
			var i = String(now.getMinutes()).padStart(2, '0');
			if (dateInput) dateInput.value = y + '-' + m + '-' + d;
			if (timeInput) timeInput.value = h + ':' + i;
		}

		// On initial load: use client-side date/time so cached pages stay accurate
		setCurrentDateTime();

		form.querySelectorAll('.beruang-type-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var t = this.dataset.type;
				form.querySelectorAll('.beruang-type-btn').forEach(function (b) { b.classList.remove('active'); });
				this.classList.add('active');
				typeField.value = t;
			});
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			message.textContent = '';
			var data = {
				date: form.querySelector('[name="date"]').value,
				time: form.querySelector('[name="time"]').value || null,
				description: form.querySelector('[name="description"]').value,
				category_id: form.querySelector('[name="category_id"]').value || 0,
				amount: form.querySelector('[name="amount"]').value,
				type: typeField.value,
			};
			request('POST', '/transactions', data).then(function (r) {
				if (r.success) {
					message.textContent = i18n.saved || 'Saved.';
					message.style.color = '#00a32a';
					setCurrentDateTime();
					form.querySelector('[name="category_id"]').value = '0';
					typeField.value = 'expense';
					form.querySelectorAll('.beruang-type-btn').forEach(function (b) { b.classList.remove('active'); });
					var expenseBtn = form.querySelector('.beruang-type-btn[data-type="expense"]');
					if (expenseBtn) expenseBtn.classList.add('active');
					form.querySelector('[name="description"]').value = '';
					form.querySelector('[name="amount"]').value = '';
				} else {
					message.textContent = r.data && r.data.message ? r.data.message : (i18n.error || 'Error');
					message.style.color = '#d63638';
				}
			}).catch(function () {
				message.textContent = i18n.error || 'Error';
				message.style.color = '#d63638';
			});
		});

		// Manage categories modal
		var catModal = document.getElementById('beruang-categories-modal');
		var catForm = document.getElementById('beruang-category-form');
		var catList = document.getElementById('beruang-categories-list');
		var catEditId = document.getElementById('beruang-cat-edit-id');
		var catName = document.getElementById('beruang-cat-name');
		var catParent = document.getElementById('beruang-cat-parent');
		var catSubmitBtn = catForm && catForm.querySelector('.beruang-cat-submit-add');
		var catCancelBtn = catForm && catForm.querySelector('.beruang-cat-cancel-edit');
		var mainCategorySelect = document.getElementById('beruang-category');
		var optionTpl = beruangTemplate('beruang-option');
		var catItemTpl = beruangTemplate('beruang-cat-item');
		var catEmptyTpl = beruangTemplate('beruang-cat-empty');

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
			request('GET', '/categories').then(function (r) {
				if (!r.success || !r.data || !r.data.categories) return;
				var cats = r.data.categories;
				catParent.innerHTML = buildCategoryOptions(cats, excludeId);
				if (selectedParentId !== undefined && selectedParentId !== null) catParent.value = selectedParentId;
				mainCategorySelect.innerHTML = buildMainCategoryOptions(cats);
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
				catList.innerHTML = listHtml || catEmptyTpl({ message: i18n.no_categories || 'No categories yet.' });
				var loading = document.querySelector('.beruang-cat-loading');
				if (loading) loading.style.display = 'none';
			});
		}

		var manageBtn = document.querySelector('.beruang-manage-categories-btn');
		if (manageBtn) {
			manageBtn.addEventListener('click', function () {
				catEditId.value = '';
				catName.value = '';
				catParent.value = '0';
				catSubmitBtn.textContent = i18n.add_category || 'Add category';
				catSubmitBtn.style.display = '';
				catCancelBtn.style.display = 'none';
				catModal.hidden = false;
				var loading = document.querySelector('.beruang-cat-loading');
				if (loading) loading.style.display = '';
				catList.innerHTML = '';
				refreshCategoriesInModal();
			});
		}

		var catModalClose = catModal && catModal.querySelector('.beruang-categories-modal-close');
		if (catModalClose) {
			catModalClose.addEventListener('click', function () { catModal.hidden = true; });
		}
		if (catModal) {
			catModal.addEventListener('click', function (e) {
				if (e.target === catModal) catModal.hidden = true;
			});
		}

		if (catForm) {
			catForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var id = catEditId.value;
				var name = catName.value;
				var parentId = catParent.value || '0';
				request('POST', '/categories', { id: id || 0, name: name, parent_id: parentId }).then(function (r) {
					if (r.success) {
						catEditId.value = '';
						catName.value = '';
						catParent.value = '0';
						catSubmitBtn.textContent = i18n.add_category || 'Add category';
						catCancelBtn.style.display = 'none';
						refreshCategoriesInModal();
					}
				});
			});
		}

		if (catCancelBtn) {
			catCancelBtn.addEventListener('click', function () {
				catEditId.value = '';
				catName.value = '';
				catParent.value = '0';
				catSubmitBtn.textContent = i18n.add_category || 'Add category';
				catSubmitBtn.style.display = '';
				catCancelBtn.style.display = 'none';
			});
		}

		document.addEventListener('click', function (e) {
			var editBtn = e.target.closest('.beruang-action-edit');
			if (!editBtn) return;
			var li = editBtn.closest('.beruang-cat-item');
			if (!li || !catModal) return;
			var id = li.dataset.id;
			var name = li.dataset.name;
			var parent = li.dataset.parent;
			catEditId.value = id;
			catName.value = name || '';
			catSubmitBtn.textContent = i18n.update_category || 'Update category';
			catSubmitBtn.style.display = '';
			catCancelBtn.style.display = '';
			refreshCategoriesInModal(id, parent || '0');
		});

		document.addEventListener('click', function (e) {
			var deleteBtn = e.target.closest('.beruang-action-delete');
			if (!deleteBtn) return;
			var li = deleteBtn.closest('.beruang-cat-item');
			if (!li) return;
			var id = li.dataset.id;
			if (!id || !confirm(i18n.confirm_delete_category || 'Delete this category?')) return;
			request('DELETE', '/categories/' + id).then(function (r) {
				if (r.success) refreshCategoriesInModal();
			});
		});

		// Calculator button: simple modal with basic calc
		var calcBtn = form.querySelector('.beruang-calc-btn');
		var calcModal = document.getElementById('beruang-calc-modal');
		var calcDisplay = calcModal && calcModal.querySelector('.beruang-calc-display');
		var amountInput = form.querySelector('#beruang-amount');
		if (calcModal && calcBtn && calcDisplay && amountInput) {
			calcBtn.addEventListener('click', function () {
				calcDisplay.value = amountInput.value || '0';
				calcModal.hidden = false;
			});
			var insertCloseBtn = calcModal.querySelector('.beruang-calc-insert-close');
			if (insertCloseBtn) {
				insertCloseBtn.addEventListener('click', function () {
					amountInput.value = calcDisplay.value;
					calcModal.hidden = true;
				});
			}
			calcModal.addEventListener('click', function (e) {
				if (e.target === calcModal) calcModal.hidden = true;
			});
			var calcVal = '0';
			var calcOp = null;
			var calcPrev = null;
			var updateDisplay = function () { calcDisplay.value = calcVal; };
			var doEquals = function () {
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
			var container = calcModal.querySelector('.beruang-calc-buttons');
			container.innerHTML = '';
			btns.forEach(function (row) {
				row.forEach(function (key) {
					var isOp = key === '+' || key === '-' || key === '*' || key === '/' || key === '\u00f7' || key === '\u00d7';
					var b = document.createElement('button');
					b.type = 'button';
					b.textContent = key;
					if (isOp) b.classList.add('beruang-calc-op');
					b.addEventListener('click', function () {
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
					container.appendChild(b);
				});
			});
			var equalsBtn = calcModal.querySelector('.beruang-calc-equals');
			if (equalsBtn) equalsBtn.addEventListener('click', doEquals);
		}
	}

	// --- List ---
	function initList() {
		var accordion = document.getElementById('beruang-list-accordion');
		if (!accordion) return;

		var listWrap = accordion.closest('.beruang-list-wrapper');
		var filters = listWrap && listWrap.querySelector('#beruang-list-filters');
		var filterBtn = listWrap && listWrap.querySelector('.beruang-filter-btn');
		var yearSel = listWrap && listWrap.querySelector('.beruang-filter-year');
		var searchEl = listWrap && listWrap.querySelector('.beruang-filter-search');
		var categoryEl = listWrap && listWrap.querySelector('.beruang-filter-category');
		if (filterBtn && filters) {
			filterBtn.addEventListener('click', function () {
				filters.hidden = !filters.hidden;
			});
		}

		var msgTpl = beruangTemplate('beruang-message');
		var txItemTpl = beruangTemplate('beruang-transaction-item');
		var accordionMonthTpl = beruangTemplate('beruang-accordion-month');

		function loadList() {
			var year = yearSel ? parseInt(yearSel.value, 10) : parseInt(accordion.dataset.year, 10);
			var search = searchEl ? searchEl.value : '';
			var categoryId = categoryEl ? categoryEl.value : '';
			accordion.innerHTML = msgTpl({ message: i18n.loading || 'Loading…' });
			request('GET', '/transactions', {
				year: year,
				search: search,
				category_id: categoryId,
				page: 1
			}).then(function (r) {
				if (!r.success || !r.data || !r.data.items) {
					accordion.innerHTML = msgTpl({ message: i18n.error || 'Error' });
					return;
				}
				var items = r.data.items;
				var byMonth = {};
				items.forEach(function (tx) {
					var d = String(tx.date || '').trim();
					var parts = d.split('-');
					var monthKey = parts.length === 3 ? parts[0] + '-' + String(parseInt(parts[1], 10)).padStart(2, '0') : d;
					if (!byMonth[monthKey]) byMonth[monthKey] = [];
					byMonth[monthKey].push(tx);
				});
				var monthKeys = Object.keys(byMonth).sort().reverse();
				var now = new Date();
				var currentMonthKey = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
				var hasCurrentMonth = monthKeys.indexOf(currentMonthKey) !== -1;
				var html = '';
				monthKeys.forEach(function (monthKey, idx) {
					var monthItems = byMonth[monthKey];
					var monthTotal = 0;
					monthItems.forEach(function (tx) {
						var amt = parseFloat(tx.amount);
						monthTotal += tx.type === 'income' ? amt : -amt;
					});
					var monthParts = monthKey.split('-');
					var monthLabel = monthKey;
					if (monthParts.length === 2) {
						var y = parseInt(monthParts[0], 10);
						var m = parseInt(monthParts[1], 10);
						var tmpDate = new Date(Date.UTC(y, m - 1, 1));
						var locale = beruang.locale || 'en-US';
						monthLabel = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(tmpDate);
					}
					var itemsHtml = '';
					monthItems.forEach(function (tx) {
						var dateDisplay = '—';
						var timeDisplay = '—';
						if (tx.date) {
							var d = String(tx.date).trim();
							var parts = d.split('-');
							if (parts.length === 3) {
								var y = parseInt(parts[0], 10);
								var m = parseInt(parts[1], 10) - 1;
								var day = parseInt(parts[2], 10);
								var tmpDate = new Date(Date.UTC(y, m, day));
								var locale = beruang.locale || 'en-US';
								dateDisplay = new Intl.DateTimeFormat(locale, { weekday: 'short', day: 'numeric', timeZone: 'UTC' }).format(tmpDate);
							} else {
								dateDisplay = d;
							}
						}
						if (tx.time && String(tx.time).trim()) {
							var t = String(tx.time).trim();
							timeDisplay = t.substring(0, 5);
						}
						itemsHtml += txItemTpl({
							id: tx.id,
							dateDisplay: dateDisplay,
							timeDisplay: timeDisplay,
							description: tx.description || '—',
							amountDisplay: (tx.type === 'income' ? '+' : '-') + formatNum(Math.abs(parseFloat(tx.amount))),
							type: tx.type,
							editLabel: i18n.edit || 'Edit',
							deleteLabel: i18n.delete || 'Delete'
						});
					});
					var expanded = monthKey === currentMonthKey || (!hasCurrentMonth && idx === 0);
					html += accordionMonthTpl({
						monthKey: monthKey,
						monthLabel: monthLabel,
						monthTotal: formatNum(monthTotal),
						itemsHtml: itemsHtml,
						monthClass: expanded ? ' is-open' : '',
						expandedAttr: expanded ? 'true' : 'false'
					});
				});
				if (!monthKeys.length) html = msgTpl({ message: i18n.no_transactions || 'No transactions.' });
				accordion.innerHTML = html;
			}).catch(function () {
				accordion.innerHTML = msgTpl({ message: i18n.error || 'Error' });
			});
		}

		var filterApply = listWrap && listWrap.querySelector('.beruang-filter-apply');
		if (filterApply) filterApply.addEventListener('click', loadList);
		var filterReset = listWrap && listWrap.querySelector('.beruang-filter-reset');
		if (filterReset) {
			filterReset.addEventListener('click', function () {
				if (yearSel) yearSel.value = accordion.dataset.year || '';
				if (searchEl) searchEl.value = '';
				if (categoryEl) categoryEl.value = '';
				loadList();
			});
		}

		accordion.addEventListener('click', function (e) {
			var head = e.target.closest('.beruang-accordion-month-head');
			if (!head) return;
			var month = head.closest('.beruang-accordion-month');
			if (!month) return;
			var isOpen = month.classList.contains('is-open');
			month.classList.toggle('is-open', !isOpen);
			head.setAttribute('aria-expanded', !isOpen);
		});
		accordion.addEventListener('keydown', function (e) {
			var head = e.target.closest('.beruang-accordion-month-head');
			if (!head) return;
			if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') return;
			e.preventDefault();
			var month = head.closest('.beruang-accordion-month');
			if (!month) return;
			var isOpen = month.classList.contains('is-open');
			month.classList.toggle('is-open', !isOpen);
			head.setAttribute('aria-expanded', !isOpen);
		});

		// Edit transaction modal
		var editModal = document.getElementById('beruang-edit-tx-modal');
		var editForm = document.getElementById('beruang-edit-tx-form');
		if (editModal && editForm) {
			document.addEventListener('click', function (e) {
				var editBtn = e.target.closest('.beruang-action-edit');
				if (!editBtn) return;
				var item = editBtn.closest('.beruang-transaction-item');
				if (!item) return;
				var id = item.dataset.id;
				if (!id) return;
				request('GET', '/transactions/' + id).then(function (r) {
					if (!r.success || !r.data || !r.data.transaction) return;
					var t = r.data.transaction;
					document.getElementById('beruang-edit-tx-id').value = t.id;
					document.getElementById('beruang-edit-tx-date').value = t.date || '';
					document.getElementById('beruang-edit-tx-time').value = t.time || '';
					document.getElementById('beruang-edit-tx-description').value = t.description || '';
					document.getElementById('beruang-edit-tx-category').value = t.category_id || '0';
					document.getElementById('beruang-edit-tx-amount').value = t.amount;
					document.getElementById('beruang-edit-tx-type').value = t.type === 'income' ? 'income' : 'expense';
					editModal.hidden = false;
				});
			});

			document.addEventListener('click', function (e) {
				var deleteBtn = e.target.closest('.beruang-action-delete');
				if (!deleteBtn) return;
				var item = deleteBtn.closest('.beruang-transaction-item');
				if (!item) return;
				var id = item.dataset.id;
				if (!id || !confirm(i18n.confirm_delete_transaction || 'Delete this transaction?')) return;
				request('DELETE', '/transactions/' + id).then(function (r) {
					if (r.success) loadList();
				});
			});

			var editCancel = document.querySelector('.beruang-edit-tx-cancel');
			if (editCancel) editCancel.addEventListener('click', function () { editModal.hidden = true; });
			editModal.addEventListener('click', function (e) {
				if (e.target === editModal) editModal.hidden = true;
			});
			editForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var data = {
					id: document.getElementById('beruang-edit-tx-id').value,
					date: document.getElementById('beruang-edit-tx-date').value,
					time: document.getElementById('beruang-edit-tx-time').value || null,
					description: document.getElementById('beruang-edit-tx-description').value,
					category_id: document.getElementById('beruang-edit-tx-category').value || 0,
					amount: document.getElementById('beruang-edit-tx-amount').value,
					type: document.getElementById('beruang-edit-tx-type').value
				};
				request('PUT', '/transactions/' + data.id, data).then(function (r) {
					if (r.success) {
						editModal.hidden = true;
						loadList();
					}
				});
			});
		}

		loadList();
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
	function initGraph() {
		var wrap = document.querySelector('.beruang-graph-wrapper');
		var canvas = document.getElementById('beruang-graph-canvas');
		if (!canvas || typeof window.Chart === 'undefined') return;

		var graphFilters = document.getElementById('beruang-graph-filters');
		var graphFilterBtn = wrap && wrap.querySelector('.beruang-filter-btn');
		if (graphFilterBtn && graphFilters) {
			graphFilterBtn.addEventListener('click', function () {
				graphFilters.hidden = !graphFilters.hidden;
			});
		}

		var chart = null;

		function loadGraph() {
			var yearEl = document.querySelector('.beruang-graph-year');
			var groupEl = document.querySelector('.beruang-graph-group');
			var year = yearEl ? parseInt(yearEl.value, 10) : new Date().getFullYear();
			var groupBy = groupEl ? groupEl.value : 'month';
			request('GET', '/graph', { year: year, group_by: groupBy }).then(function (r) {
				if (!r.success || !r.data || !r.data.data) return;
				renderChart(r.data.data, groupBy, year);
			});
		}

		function renderChart(data, groupBy, year) {
			var ctx = canvas.getContext('2d');
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

		var yearEl = document.querySelector('.beruang-graph-year');
		var groupEl = document.querySelector('.beruang-graph-group');
		if (yearEl) yearEl.addEventListener('change', loadGraph);
		if (groupEl) groupEl.addEventListener('change', loadGraph);
		if (window.Chart) loadGraph();
		else window.addEventListener('load', function () { setTimeout(loadGraph, 100); });
	}

	// --- Budget ---
	function initBudget() {
		var list = document.getElementById('beruang-budget-list');
		var modal = document.getElementById('beruang-budget-modal');
		var form = document.getElementById('beruang-budget-form');
		if (!list) return;

		var budgetWrap = list.closest('.beruang-budget-wrapper');
		var filters = budgetWrap && budgetWrap.querySelector('#beruang-budget-filters');
		var filterBtn = budgetWrap && budgetWrap.querySelector('.beruang-budget-header .beruang-filter-btn');

		if (filterBtn && filters) {
			filterBtn.addEventListener('click', function () {
				filters.hidden = !filters.hidden;
			});
		}

		var msgTpl = beruangTemplate('beruang-message');
		var budgetCardTpl = beruangTemplate('beruang-budget-card');

		document.addEventListener('click', function (e) {
			var deleteBtn = e.target.closest('.beruang-action-delete');
			if (!deleteBtn) return;
			var card = deleteBtn.closest('.beruang-budget-card');
			if (!card) return;
			var id = deleteBtn.dataset.id;
			if (!id || !confirm(i18n.confirm_delete || 'Delete this budget?')) return;
			request('DELETE', '/budgets/' + id).then(function (res) {
				if (res.success) loadBudgets();
			});
		});
		document.addEventListener('click', function (e) {
			var editBtn = e.target.closest('.beruang-action-edit');
			if (!editBtn) return;
			var card = editBtn.closest('.beruang-budget-card');
			if (!card) return;
			var id = editBtn.dataset.id;
			if (!id) return;
			request('GET', '/budgets/' + id).then(function (r) {
				if (!r.success || !r.data || !r.data.budget) return;
				var b = r.data.budget;
				form.querySelector('[name="id"]').value = b.id;
				form.querySelector('[name="name"]').value = b.name || '';
				form.querySelector('[name="target_amount"]').value = b.target_amount || '';
				form.querySelector('[name="type"]').value = b.type === 'yearly' ? 'yearly' : 'monthly';
				form.querySelectorAll('[name="category_ids[]"]').forEach(function (cb) { cb.checked = false; });
				(b.category_ids || []).forEach(function (cid) {
					var cb = form.querySelector('[name="category_ids[]"][value="' + cid + '"]');
					if (cb) cb.checked = true;
				});
				modal.hidden = false;
			});
		});

		function loadBudgets() {
			var yearSel = budgetWrap && budgetWrap.querySelector('.beruang-filter-year');
			var monthSel = budgetWrap && budgetWrap.querySelector('.beruang-filter-month');
			var year = yearSel ? parseInt(yearSel.value, 10) : parseInt(list.dataset.year, 10);
			var month = monthSel ? parseInt(monthSel.value, 10) : parseInt(list.dataset.month, 10);
			list.innerHTML = msgTpl({ message: i18n.loading || 'Loading…' });
			request('GET', '/budgets', { year: year, month: month }).then(function (r) {
				if (!r.success || !r.data || !r.data.budgets) {
					list.innerHTML = msgTpl({ message: i18n.error || 'Error' });
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
				list.innerHTML = html;
			}).catch(function () {
				list.innerHTML = msgTpl({ message: i18n.error || 'Error' });
			});
		}

		if (budgetWrap) {
			budgetWrap.addEventListener('click', function (e) {
				if (e.target.closest('.beruang-filter-apply')) loadBudgets();
			});
			budgetWrap.addEventListener('click', function (e) {
				if (!e.target.closest('.beruang-filter-reset')) return;
				var yearSel = budgetWrap.querySelector('.beruang-filter-year');
				var monthSel = budgetWrap.querySelector('.beruang-filter-month');
				if (yearSel) yearSel.value = list.dataset.year || '';
				if (monthSel) monthSel.value = list.dataset.month || '';
				loadBudgets();
			});
		}

		var budgetAdd = budgetWrap && budgetWrap.querySelector('.beruang-budget-add');
		if (budgetAdd) {
			budgetAdd.addEventListener('click', function () {
				form.querySelector('[name="id"]').value = '';
				form.querySelector('[name="name"]').value = '';
				form.querySelector('[name="target_amount"]').value = '';
				form.querySelector('[name="type"]').value = 'monthly';
				form.querySelectorAll('[name="category_ids[]"]').forEach(function (cb) { cb.checked = false; });
				modal.hidden = false;
			});
		}

		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal || e.target.classList.contains('beruang-budget-modal-close')) modal.hidden = true;
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var id = form.querySelector('[name="id"]').value;
			var name = form.querySelector('[name="name"]').value;
			var target = form.querySelector('[name="target_amount"]').value;
			var type = form.querySelector('[name="type"]').value;
			var catIds = [];
			form.querySelectorAll('[name="category_ids[]"]:checked').forEach(function (cb) { catIds.push(cb.value); });
			request('POST', '/budgets', {
				id: id || 0,
				name: name,
				target_amount: target,
				type: type,
				category_ids: catIds
			}).then(function (r) {
				if (r.success) {
					modal.hidden = true;
					loadBudgets();
				}
			});
		});

		loadBudgets();
	}

	document.addEventListener('DOMContentLoaded', function () {
		initForm();
		initList();
		initGraph();
		initBudget();
	});
})();
