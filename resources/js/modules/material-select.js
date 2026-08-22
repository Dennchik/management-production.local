// import ItcCollapse from '../assets/its-collapse.js';
//
// const select = document.querySelector('.main-content__select');
//
// if (select) {
// 	const button = select.querySelector('.main-content__select-button');
// 	const list = select.querySelector('.main-content__select-list');
// 	const search = select.querySelector('.main-content__select-search-input');
// 	const options = select.querySelectorAll('.main-content__select-option');
// 	const value = select.querySelector('.main-content__select-value');
// 	const hiddenInput = select.querySelector('#material_id');
// 	const empty = select.querySelector('.main-content__select-empty');
//
// 	const collapse = new ItcCollapse(list);
//
// 	// .main-content__select-button
// 	button.addEventListener('click', () => {
// 		collapse.toggle();
//
// 		button.setAttribute(
// 			'aria-expanded',
// 			String(list.classList.contains('_show'))
// 		);
//
// 		if (list.classList.contains('_show')) {
// 			search.focus();
// 		}
// 	});
//
// 	// .main-content__select-search-input
// 	search.addEventListener('input', (event) => {
// 		const query = event.target.value.toLowerCase().trim();
// 		let visibleCount = 0;
//
// 		options.forEach((option) => {
// 			const text = option.textContent.toLowerCase();
// 			const match = text.includes(query);
//
// 			option.style.display = match ? '' : 'none';
//
// 			if (match) {
// 				visibleCount++;
// 			}
// 		});
//
// 		empty.hidden = visibleCount > 0;
// 	});
//
// 	// .main-content__select-option
// 	options.forEach((option) => {
// 		option.addEventListener('click', () => {
// 			hiddenInput.value = option.dataset.value;
// 			value.textContent = option.textContent.trim();
//
// 			options.forEach((item) => {
// 				item.setAttribute('aria-selected', 'false');
// 			});
//
// 			option.setAttribute('aria-selected', 'true');
//
// 			collapse.hide();
// 			button.setAttribute('aria-expanded', 'false');
//
// 			search.value = '';
//
// 			options.forEach((item) => {
// 				item.style.display = '';
// 			});
//
// 			empty.hidden = true;
//
// 			select.dispatchEvent(
// 				new CustomEvent('material:change', {
// 					detail: {
// 						grammage: option.dataset.grammage || '',
// 						thickness: option.dataset.thickness || '',
// 						format: option.dataset.format || '',
// 						identifier: option.dataset.identifier || '',
// 					},
// 				})
// 			);
// 		});
// 	});
// }