document.addEventListener('DOMContentLoaded', () => {
	const modal = document.querySelector('[data-operation-modal]');
	if (!modal) return;

	const content = modal.querySelector('[data-operation-modal-content]');
	const closeButtons = modal.querySelectorAll('[data-operation-modal-close]');

	function open() {
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
	}

	function close() {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.remove('modal-open');
		content.innerHTML = '';
	}

	function load(url, errorMsg) {
		content.innerHTML = '<div class="operation-modal__loader">Загрузка...</div>';
		open();

		fetch(url, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'text/html'
			}
		}).then(r => {
			if (!r.ok) throw new Error();
			return r.text();
		}).then(html => {
			content.innerHTML = html;
		}).catch(() => {
			content.innerHTML = `<div class="operation-modal__error">${errorMsg}</div>`;
		});
	}

	// Открытие по клику на строку таблицы
	document.addEventListener('click', (e) => {
		const receipt = e.target.closest('[data-receipt-modal-open]');
		if (receipt) {
			load(`/receipts/${receipt.dataset.receiptId}`,
				'Не удалось загрузить приходный ордер.');
			return;
		}

		const issue = e.target.closest('[data-issue-modal-open]');
		if (issue) {
			load(`/issues/${issue.dataset.issueId}`,
				'Не удалось загрузить расходный ордер.');
		}
	});

	// Закрытие
	closeButtons.forEach(btn => btn.addEventListener('click', close));

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
			close();
		}
	});
});