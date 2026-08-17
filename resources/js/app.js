// app.js

import gsap from 'gsap';
import { ScrollSmoother } from 'gsap/ScrollSmoother';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollSmoother, ScrollTrigger, SplitText);

export function smoother() {
	ScrollSmoother.create({
		wrapper: '#wrapper',
		content: '#content',
		speed: 1,
		smooth: 1,
		effects: true,
		smoothTouch: 0.1,
	});
}

export function applyParallax(element) {
	const smootherInstance = ScrollSmoother.get();
	smootherInstance.effects(element, {
		speed: () => 0.5,
	});
}
applyParallax ()
console.log('Vite Laravel работает');