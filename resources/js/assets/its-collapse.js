export default class ItcCollapse {
  constructor(target, duration = 350) {
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
    if (el.classList.contains('collapsing') || !el.classList.contains('_show'))
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
//* ----------------------------------------------------------------------------
// import ItcCollapse from './assets/its-collapse.js';
// const item = document.querySelectorAll('._slideToggle');

// item.forEach((item) => {
//   const trigger = item.querySelector('._trigger');
//   if (!trigger) return;
//   // Создаём объект ItcCollapse один раз и сохраняем в элементе
//   const collapseEl = item.querySelector('._collapse');
//   if (!collapseEl) return;
//   item._collapseInstance = new ItcCollapse(collapseEl);
//   trigger.addEventListener('click', () => {
//     // Закрываем другие элементы в том же аккордеоне
//     const accordion = item.closest('.accordion');
//     if (accordion) {
//       const opened = item.querySelector('._open');
//       if (opened && opened !== item) {
//         opened.classList.remove('_open');
//         opened._collapseInstance.toggle();
//       }
//     }
//     // Переключаем текущий
//     item.classList.toggle('_open');
//     item._collapseInstance.toggle();
//   });
// });
