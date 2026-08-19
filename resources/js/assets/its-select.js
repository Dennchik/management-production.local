import ItcCollapse from './its-collapse.js';

//* ------------------------------ [Select]-------------------------------------

export function select() {
	document.querySelectorAll('[data-select]').forEach((selectGroup) => {
		const itsSelects = selectGroup.querySelectorAll('.select');

		itsSelects.forEach((itsSelect) => {
			const listItems = itsSelect.querySelectorAll('.select__item');
			const selectButton = itsSelect.querySelector('.select__button');
			const selectValueInput = itsSelect.querySelector('.select__value');

			if (!selectButton || !listItems.length) return;

			let start =
				itsSelect.querySelector('._selected') ||
				listItems[0];

			// Установить выбранное значение
			function setValue(item) {
				if (!item) return;

				start = item;

				// Что видит пользователь
				selectButton.value = item.textContent.trim();

				// Что отправляется в GET
				if (selectValueInput) {
					selectValueInput.value = item.dataset.value || '';
				}

				listItems.forEach((listItem) => {
					listItem.classList.toggle(
						'_selected',
						listItem === item
					);
				});
			}

			// Открытие / закрытие
			function toggleOpen(select) {
				const collapseElement =
					select.querySelector('._collapse');

				if (!collapseElement) return;

				const collapse = new ItcCollapse(collapseElement);

				if (select.classList.contains('_active-collapse')) {
					select.classList.remove('_active-collapse');
					collapse.hide();
				} else {
					document.querySelectorAll('.select._active-collapse').forEach(
						(openedSelect) => {
							if (openedSelect === select) return;

							const openedCollapse =
								openedSelect.querySelector('._collapse');

							if (openedCollapse) {
								new ItcCollapse(openedCollapse).hide();
							}

							openedSelect.classList.remove(
								'_active-collapse'
							);
						});

					select.classList.add('_active-collapse');
					collapse.show();
				}
			}

			// Клик по input
			selectButton.addEventListener('click', (event) => {
				event.stopPropagation();

				toggleOpen(itsSelect);
			});

			// Выбор пункта
			listItems.forEach((listItem) => {
				listItem.addEventListener('click', (event) => {
					event.stopPropagation();

					setValue(listItem);

					const collapseElement =
						itsSelect.querySelector('._collapse');

					if (collapseElement) {
						new ItcCollapse(collapseElement).hide();
					}

					itsSelect.classList.remove('_active-collapse');

					selectButton.blur();
				});
			});

			// Клавиатура
			selectGroup.addEventListener('keydown', (event) => {
				if (
					event.key !== 'ArrowUp' &&
					event.key !== 'ArrowDown' &&
					event.key !== 'Enter'
				) {
					return;
				}

				event.preventDefault();

				if (event.key === 'ArrowUp') {
					const previous = start.previousElementSibling;

					if (previous) {
						setValue(previous);
						previous.focus();
					}
				}

				if (event.key === 'ArrowDown') {
					const next = start.nextElementSibling;

					if (next) {
						setValue(next);
						next.focus();
					}
				}

				if (event.key === 'Enter') {
					toggleOpen(itsSelect);
				}
			});

			// Клик вне select
			document.addEventListener('click', (event) => {
				if (itsSelect.contains(event.target)) return;

				if (
					itsSelect.classList.contains(
						'_active-collapse'
					)
				) {
					const collapseElement =
						itsSelect.querySelector('._collapse');

					if (collapseElement) {
						new ItcCollapse(collapseElement).hide();
					}

					itsSelect.classList.remove(
						'_active-collapse'
					);
				}
			});

			// Escape
			document.addEventListener('keydown', (event) => {
				if (event.key !== 'Escape') return;

				const collapseElement =
					itsSelect.querySelector('._collapse');

				if (collapseElement) {
					new ItcCollapse(collapseElement).hide();
				}

				itsSelect.classList.remove(
					'_active-collapse'
				);
			});

			// Начальное состояние
			setValue(start);
		});
	});
}