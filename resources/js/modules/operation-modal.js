document.addEventListener('DOMContentLoaded', () => {
	const modal = document.querySelector('[data-operation-modal]');

	if (!modal) {
		return;
	}

	const modalContent = modal.querySelector(
		'[data-operation-modal-content]'
	);

	const modalBox = modal.querySelector(
		'.operation-modal__content'
	);

	const closeButtons = modal.querySelectorAll(
		'[data-operation-modal-close]'
	);

	/*
	 * Открытие модалки.
	 */
	const openModal = () => {
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
	};

	/*
	 * Закрытие модалки.
	 */
	const closeModal = () => {
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('modal-open');

		modalContent.innerHTML = '';
		modalBox.style.height = '';
	};

	/*
	 * Плавная замена содержимого модалки.
	 *
	 * Сначала запоминаем текущую высоту блока,
	 * затем вставляем новый контент,
	 * определяем новую высоту
	 * и плавно переходим к ней.
	 */
	const replaceModalContent = (html) => {
		/*
		 * Текущая высота.
		 */
		const startHeight = modalBox.offsetHeight;

		/*
		 * Фиксируем её.
		 */
		modalBox.style.height = `${startHeight}px`;

		/*
		 * Меняем содержимое.
		 */
		modalContent.innerHTML = html;

		/*
		 * Получаем новую фактическую высоту.
		 *
		 * Для этого временно убираем ограничение.
		 */
		modalBox.style.height = 'auto';

		const endHeight = modalBox.offsetHeight;

		/*
		 * Возвращаем начальную высоту.
		 */
		modalBox.style.height = `${startHeight}px`;

		/*
		 * Принудительный reflow.
		 *
		 * Без него браузер может объединить
		 * два изменения height в одно.
		 */
		modalBox.offsetHeight;

		/*
		 * Запускаем плавное изменение высоты.
		 */
		requestAnimationFrame(() => {
			modalBox.style.height = `${endHeight}px`;
		});

		/*
		 * После завершения анимации
		 * возвращаем auto.
		 */
		const handleTransitionEnd = (event) => {
			if (event.propertyName !== 'height') {
				return;
			}

			modalBox.style.height = 'auto';

			modalBox.removeEventListener(
				'transitionend',
				handleTransitionEnd
			);
		};

		modalBox.addEventListener(
			'transitionend',
			handleTransitionEnd
		);
	};

	/*
	 * Загрузка операции.
	 */
	const loadOperation = async (url, errorMessage) => {
		/*
		 * Показываем загрузку.
		 */
		modalContent.innerHTML = `
       <div class="operation-modal__loader">
         Загрузка...
       </div>
     `;

		/*
		 * Высота loader становится
		 * исходной высотой модалки.
		 */
		modalBox.style.height = 'auto';

		/*
		 * Открываем модалку.
		 */
		openModal();

		try {
			const response = await fetch(url, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					Accept: 'text/html',
				},
			});

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}

			const html = await response.text();

			/*
			 * Здесь происходит главное:
			 *
			 * loader → полноценный ордер
			 *
			 * с плавным изменением высоты.
			 */
			replaceModalContent(html);

		} catch (error) {
			console.error(
				'Ошибка загрузки операции:',
				error
			);

			replaceModalContent(`
         <div class="operation-modal__error">
           ${errorMessage}
         </div>
       `);
		}
	};

	/*
	 * Открытие приходного или расходного ордера.
	 *
	 * Делегирование событий позволяет
	 * работать со строками таблиц.
	 */
	document.addEventListener('click', (event) => {
		const receiptButton = event.target.closest(
			'[data-receipt-modal-open]'
		);

		if (receiptButton) {
			const receiptId = receiptButton.dataset.receiptId;

			if (!receiptId) {
				return;
			}

			loadOperation(
				`/receipts/${receiptId}`,
				'Не удалось загрузить приходный ордер.'
			);

			return;
		}

		const issueButton = event.target.closest(
			'[data-issue-modal-open]'
		);

		if (issueButton) {
			const issueId = issueButton.dataset.issueId;

			if (!issueId) {
				return;
			}

			loadOperation(
				`/issues/${issueId}`,
				'Не удалось загрузить расходный ордер.'
			);
		}
	});

	/*
	 * Закрытие по кнопкам.
	 */
	closeButtons.forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	/*
	 * Закрытие по Escape.
	 */
	document.addEventListener('keydown', (event) => {
		if (
			event.key === 'Escape' &&
			modal.getAttribute('aria-hidden') === 'false'
		) {
			closeModal();
		}
	});
});