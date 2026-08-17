export const app = {
	autoprefixer: {
		cascade: false,
		grid: 'auto-place',
		overrideBrowserslist: [
			'last 2 versions',
			'ie >= 10',
			'> 1%',
			'not dead',
		],
	},

	postcssMediaMinMax: {},

	postcssSortMediaQueries: {
		sort: 'mobile-first',
	},

	webp: {
		inputDir: 'public/img',
		quality: 100,
	},
};