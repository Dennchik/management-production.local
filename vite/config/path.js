import { resolve } from 'node:path';

export const paths = {
	fonts: {
		src: resolve('public/fonts'),
		dest: resolve('resources/scss/core/_fonts.scss'),
	},

	scss: {
		src: resolve('resources/scss'),
	},

	webp: {
		src: resolve('public/img'),
	},
};