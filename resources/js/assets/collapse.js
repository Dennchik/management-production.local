export default class Collapse {
	constructor(target, duration = 150) {
		if (!target) throw new Error('Collapse: target is required');
		this.target = target;
		this.duration = duration;
	}

	show() {
		const el = this.target;
		if (el.classList.contains('collapsing') || el.classList.contains(
			'_show')) return;

		el.classList.remove('_collapse');
		const height = el.scrollHeight;

		el.style.height = '0px';
		el.style.overflow = 'hidden';
		el.style.transition = `height ${this.duration}ms ease`;
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
		}, this.duration);
	}

	hide() {
		const el = this.target;
		if (el.classList.contains('collapsing') || !el.classList.contains(
			'_show')) return;

		const height = el.scrollHeight;
		el.style.height = `${height}px`;
		el.offsetHeight;

		el.style.overflow = 'hidden';
		el.style.transition = `height ${this.duration}ms ease`;
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
		}, this.duration);
	}

	toggle() {
		this.target.classList.contains('_show') ? this.hide() : this.show();
	}
}