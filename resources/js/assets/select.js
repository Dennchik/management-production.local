import Collapse from './collapse';

export function initSelects() {
	document.querySelectorAll('[data-select]').forEach((container) => {
		// Защита от повторной инициализации
		if (container.dataset.selectInitialized === 'true') {
			return;
		}

		container.dataset.selectInitialized = 'true';

		const selects = container.querySelectorAll('.select');

		selects.forEach((select) => {
			const button = select.querySelector('.select__button');
			const valueInput = select.querySelector('.select__value');
			const dropdown = select.querySelector('.select__dropdown');

			if (!button || !dropdown) {
				return;
			}

			const collapse = new Collapse(dropdown);

			/*
			 * Получить все пункты select.
			 *
			 * Используем функцию, а не заранее сохранённый NodeList,
			 * потому что пункты могут добавляться динамически.
			 */
			const getItems = () => {
				return select.querySelectorAll('.select__item');
			};

			/*
			 * Текущий выбранный пункт.
			 */
			let selected = select.querySelector('.select__item._selected');

			/*
			 * Установить значение select.
			 */
			const setValue = (item) => {
				if (!item) {
					return;
				}

				selected = item;

				/*
				 * Текст кнопки.
				 */
				const buttonText = button.querySelector(
					'.select__button-text'
				);

				if (buttonText) {
					buttonText.textContent = item.textContent.trim();
				} else {
					button.value = item.textContent.trim();
				}

				/*
				 * Hidden input.
				 */
				if (valueInput) {
					valueInput.value = item.dataset.value || '';
				}

				/*
				 * Активный пункт.
				 */
				getItems().forEach((el) => {
					el.classList.toggle('_selected', el === item);
					el.setAttribute(
						'aria-selected',
						el === item ? 'true' : 'false'
					);
				});

				/*
				 * Уведомляем конкретный select,
				 * что значение изменилось.
				 *
				 * bubbles: true позволяет форме
				 * подписаться на событие.
				 */
				select.dispatchEvent(
					new CustomEvent('select-change', {
						bubbles: true,
						detail: {
							value: item.dataset.value || '',
							label: item.textContent.trim(),
							item,
						},
					})
				);
			};

			/*
			 * Открытие / закрытие.
			 */
			button.addEventListener('click', (event) => {
				event.stopPropagation();

				/*
				 * Закрываем другие select.
				 */
				document.querySelectorAll('.select._active-collapse').forEach(
					(otherSelect) => {
						if (otherSelect === select) {
							return;
						}

						const otherDropdown =
							otherSelect.querySelector('.select__dropdown');

						if (otherDropdown) {
							otherDropdown.classList.remove('_show');
						}

						otherSelect.classList.remove('_active-collapse');
					});

				if (dropdown.classList.contains('_show')) {
					collapse.hide();
					select.classList.remove('_active-collapse');
				} else {
					collapse.show();
					select.classList.add('_active-collapse');
				}
			});

			/*
			 * Выбор пункта.
			 *
			 * Делегирование:
			 * работает и для пунктов, которые появились
			 * после AJAX-загрузки.
			 */
			select.addEventListener('click', (event) => {
				const item = event.target.closest('.select__item');

				if (!item || !select.contains(item)) {
					return;
				}

				event.stopPropagation();

				setValue(item);

				collapse.hide();
				select.classList.remove('_active-collapse');
			});

			/*
			 * Закрытие при клике вне select.
			 */
			document.addEventListener('click', (event) => {
				if (
					!select.contains(event.target) &&
					dropdown.classList.contains('_show')
				) {
					collapse.hide();
					select.classList.remove('_active-collapse');
				}
			});

			/*
			 * Escape.
			 */
			document.addEventListener('keydown', (event) => {
				if (
					event.key === 'Escape' &&
					dropdown.classList.contains('_show')
				) {
					collapse.hide();
					select.classList.remove('_active-collapse');
				}
			});

			/*
			 * Начальное состояние.
			 *
			 * Если сервер явно отметил пункт как _selected —
			 * устанавливаем его.
			 *
			 * Если _selected нет —
			 * ничего автоматически не выбираем.
			 */
			if (selected) {
				setValue(selected);
			}
		});
	});
}