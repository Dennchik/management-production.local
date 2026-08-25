import { initSelects } from '../../assets/select';

document.addEventListener('DOMContentLoaded', () => {
	const form = document.querySelector('.issue-order');
	if (!form) return;

	// === Выбор материала ===
	const materialSelect = form.querySelector('.material-select');
	const materialHidden = materialSelect?.querySelector('#material_id');
	const materialValueSpan = materialSelect?.querySelector(
		'.material-select__select-value');
	const materialOptions = materialSelect?.querySelectorAll(
		'.material-select__select-option');
	const materialNameInput = form.querySelector('#material_name');
	const materialIdentifierInput = form.querySelector('#material_identifier');

	// === Выбор рулона ===
	const rollSelect = document.querySelectorAll(
		'.issue-order .material-select')[1];
	if (!rollSelect) return;

	const rollHidden = rollSelect.querySelector('#roll_id');
	const rollValueSpan = rollSelect.querySelector(
		'.material-select__select-value');
	const rollList = rollSelect.querySelector('#rolls-list');
	const rollSearchInput = rollSelect.querySelector('.select__search');
	const rollSearchClear = rollSelect.querySelector('.select__search-clear');
	const rollEmptyMsg = rollSelect.querySelector('.select__empty');

	// === Поля ===
	const rollNumberInput = form.querySelector('#roll_number');
	const remainingWeightInput = form.querySelector('#remaining_weight');
	const weightInput = form.querySelector('#weight');

	// === Загрузка рулонов ===
	async function fetchRolls(materialId) {
		if (!materialId) {
			updateRollList([]);
			return;
		}

		try {
			const response = await fetch(`/api/rolls?material_id=${materialId}`);
			const data = await response.json();
			updateRollList(data);
		} catch (error) {
			console.error('Ошибка загрузки рулонов:', error);
			updateRollList([]);
		}
	}

	function updateRollList(rolls) {
		if (!rollList) return;
		rollList.innerHTML = '';

		if (!rolls || rolls.length === 0) {
			if (rollEmptyMsg) rollEmptyMsg.hidden = false;
			if (rollValueSpan) rollValueSpan.textContent = 'Нет доступных рулонов';
			return;
		}

		if (rollEmptyMsg) rollEmptyMsg.hidden = true;

		rolls.forEach((roll) => {
			const btn = document.createElement('button');
			btn.className = 'material-select__select-option select__item';
			btn.type = 'button';
			btn.role = 'option';
			btn.dataset.value = roll.id;
			btn.dataset.roll = roll.roll_number;
			btn.dataset.weight = roll.weight;
			btn.setAttribute('aria-selected', 'false');
			btn.innerHTML = `<span>${roll.roll_number} | ${roll.weight} кг</span>`;
			btn.addEventListener('click', () => selectRoll(btn));
			rollList.appendChild(btn);
		});

		// 👇 Переинициализируем select для рулонов
		const rollSelectContainer = rollSelect.closest('[data-select]');
		if (rollSelectContainer) {
			// Удаляем старый select и инициализируем заново
			initSelects();
		}

		if (rollHidden && rollHidden.value) {
			const selected = rollList.querySelector(
				`[data-value="${rollHidden.value}"]`);
			if (selected) {
				selected.setAttribute('aria-selected', 'true');
				if (rollValueSpan) rollValueSpan.textContent = selected.textContent.trim();
				if (rollNumberInput) rollNumberInput.value = selected.dataset.roll || '';
				if (remainingWeightInput) remainingWeightInput.value = selected.dataset.weight || '';
				if (weightInput) weightInput.max = selected.dataset.weight || '';
			}
		} else {
			if (rollValueSpan) rollValueSpan.textContent = 'Выберите рулон';
			if (rollNumberInput) rollNumberInput.value = '';
			if (remainingWeightInput) remainingWeightInput.value = '';
			if (weightInput) weightInput.removeAttribute('max');
		}
	}

	function selectRoll(option) {
		if (!option) {
			if (rollHidden) rollHidden.value = '';
			if (rollValueSpan) rollValueSpan.textContent = 'Выберите рулон';
			if (rollNumberInput) rollNumberInput.value = '';
			if (remainingWeightInput) remainingWeightInput.value = '';
			if (weightInput) weightInput.removeAttribute('max');
			return;
		}

		if (rollHidden) rollHidden.value = option.dataset.value || '';
		if (rollValueSpan) rollValueSpan.textContent = option.textContent.trim();
		if (rollNumberInput) rollNumberInput.value = option.dataset.roll || '';
		if (remainingWeightInput) remainingWeightInput.value = option.dataset.weight || '';
		if (weightInput) weightInput.max = option.dataset.weight || '';

		if (rollList) {
			rollList.querySelectorAll('.select__item').forEach(el => {
				el.setAttribute('aria-selected', el === option ? 'true' : 'false');
			});
		}
	}

	// === Выбор материала ===
	materialOptions?.forEach((option) => {
		option.addEventListener('click', () => {
			if (materialHidden) materialHidden.value = option.dataset.value;
			if (materialValueSpan) materialValueSpan.textContent = option.textContent.trim();
			if (materialNameInput) materialNameInput.value = option.dataset.name || '';
			if (materialIdentifierInput) materialIdentifierInput.value = option.dataset.identifier || '';

			materialOptions.forEach(el => {
				el.setAttribute('aria-selected', el === option ? 'true' : 'false');
			});

			fetchRolls(option.dataset.value);

			if (rollHidden) rollHidden.value = '';
			if (rollValueSpan) rollValueSpan.textContent = 'Выберите рулон';
			if (rollNumberInput) rollNumberInput.value = '';
			if (remainingWeightInput) remainingWeightInput.value = '';
			if (weightInput) weightInput.removeAttribute('max');
		});
	});

	// === Поиск по рулонам ===
	if (rollSearchInput && rollList) {
		rollSearchInput.addEventListener('input', (e) => {
			const query = e.target.value.trim().toLowerCase();
			if (rollSearchClear) rollSearchClear.hidden = !query;

			const items = rollList.querySelectorAll('.select__item');

			if (!query) {
				items.forEach(el => el.style.display = '');
				if (rollEmptyMsg) rollEmptyMsg.hidden = true;
				return;
			}

			let visible = 0;
			items.forEach(el => {
				const match = el.textContent.trim().toLowerCase().includes(query);
				el.style.display = match ? '' : 'none';
				if (match) visible++;
			});
			if (rollEmptyMsg) rollEmptyMsg.hidden = visible > 0;
		});

		rollSearchClear?.addEventListener('click', () => {
			if (rollSearchInput) {
				rollSearchInput.value = '';
				rollSearchInput.dispatchEvent(new Event('input'));
				rollSearchInput.focus();
			}
		});
	}

	// === Сброс ===
	form.querySelector('.issue-order__button--reset')?.addEventListener('click',
		() => {
			if (materialHidden) materialHidden.value = '';
			if (materialValueSpan) materialValueSpan.textContent = 'Выберите материал';
			if (materialNameInput) materialNameInput.value = '';
			if (materialIdentifierInput) materialIdentifierInput.value = '';
			materialOptions?.forEach(
				el => el.setAttribute('aria-selected', 'false'));

			if (rollHidden) rollHidden.value = '';
			if (rollValueSpan) rollValueSpan.textContent = 'Сначала выберите материал';
			if (rollNumberInput) rollNumberInput.value = '';
			if (remainingWeightInput) remainingWeightInput.value = '';
			if (weightInput) {
				weightInput.value = '';
				weightInput.removeAttribute('max');
			}
			updateRollList([]);

			if (rollSearchInput) {
				rollSearchInput.value = '';
				if (rollSearchClear) rollSearchClear.hidden = true;
			}
		});

	// === Восстановление ===
	if (materialHidden?.value) {
		const selected = Array.from(materialOptions || []).find(
			o => o.dataset.value === materialHidden.value
		);
		if (selected) {
			if (materialValueSpan) materialValueSpan.textContent = selected.textContent.trim();
			if (materialNameInput) materialNameInput.value = selected.dataset.name || '';
			if (materialIdentifierInput) materialIdentifierInput.value = selected.dataset.identifier || '';
			fetchRolls(materialHidden.value);
		}
	}
});