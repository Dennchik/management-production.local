export default class ItcCollapse {
	constructor(target, duration = 150) {
		if (!target) throw new Error('ItcCollapse: target element is required');
		this._target = target;
		this._duration = duration;
	}

	show() {
		const el = this._target;
		if (el.classList.contains('collapsing') || el.classList.contains('_show'))
			return;

		el.classList.remove('_collapse');
		const height = el.scrollHeight;

		el.style.height = '0px';
		el.style.overflow = 'hidden';
		el.style.transition = `height ${this._duration}ms ease`;
		el.classList.add('collapsing');

		requestAnimationFrame(() => {
			el.style.height = `${height}px`;
		});

		setTimeout(() => {
			el.classList.remove('collapsing');
			el.classList.add('_collapse', '_show');
			el.style.height = '';
			el.style.transition = '';
			el.style.overflow = '';
		}, this._duration);
	}

	hide() {
		const el = this._target;
		if (el.classList.contains('collapsing') || !el.classList.contains(
			'_show'))
			return;

		const height = el.scrollHeight;
		el.style.height = `${height}px`;
		el.offsetHeight; // force reflow

		el.style.overflow = 'hidden';
		el.style.transition = `height ${this._duration}ms ease`;
		el.classList.remove('_collapse', '_show');
		el.classList.add('collapsing');

		requestAnimationFrame(() => {
			el.style.height = '0px';
		});

		setTimeout(() => {
			el.classList.remove('collapsing');
			el.classList.add('_collapse');
			el.style.height = '';
			el.style.transition = '';
			el.style.overflow = '';
		}, this._duration);
	}

	toggle() {
		this._target.classList.contains('_show') ? this.hide() : this.show();
	}

	// Статический метод для инициализации всех коллапсов на странице
	static initAll(selector = '._collapse', duration = 350) {
		const elements = document.querySelectorAll(selector);
		return Array.from(elements).map((el) => new ItcCollapse(el, duration));
	}
}

