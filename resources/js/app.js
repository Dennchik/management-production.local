import './modules/material-receipt';
import './modules/material-issue.js';
import './modules/message.js';
import './modules/operation-modal.js';
import './modules/clickableRows';
import { select } from './assets/its-select.js';

select();

// document.addEventListener('click', (event) => {
// 	const row = event.target.closest('[data-href]');
//
// 	if (!row) {
// 		return;
// 	}
//
// 	window.location.href = row.dataset.href;
// });
//
// document.addEventListener('keydown', (event) => {
// 	if (event.key !== 'Enter' && event.key !== ' ') {
// 		return;
// 	}
//
// 	const row = event.target.closest('[data-href]');
//
// 	if (!row) {
// 		return;
// 	}
//
// 	event.preventDefault();
//
// 	window.location.href = row.dataset.href;
// });