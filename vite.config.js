import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import postcssMediaMinMax from 'postcss-media-minmax';
import sortMediaQueries from 'postcss-sort-media-queries';
import { defineConfig } from 'vite';

import { app } from './vite/config/app.js';
import { fontStyle } from './vite/tasks/fontsStyle.js';
import { convertImagesToWebp } from './vite/tasks/webp.js';

const __dirname = dirname(fileURLToPath(import.meta.url));

export default defineConfig(({command}) => {
	const isProd = command === 'build';

	// noinspection JSValidateTypes
	return {
		plugins: [
			laravel({
				input: [
					'resources/scss/app.scss',
					'resources/js/app.js',
				],
				refresh: true,
			}),

			fontStyle(),

			convertImagesToWebp(app.webp),
		],

		server: {
			proxy: {
				'/fonts': {
					target: 'http://localhost:8000',
					changeOrigin: true,
				},
			},

			// open: true,
			// 	watch: { 
			// 		ignored: ['**/storage/framework/views/**'],
			// 	},
		},

		css: {
			devSourcemap: !isProd,

			postcss: {
				plugins: [
					...(isProd
						? [
							postcssMediaMinMax(
								app.postcssMediaMinMax,
							),

							sortMediaQueries(
								app.postcssSortMediaQueries,
							),

							autoprefixer(
								app.autoprefixer,
							),
						]
						: []),
				],
			},

			preprocessorOptions: {
				scss: {},
			},
		},

		resolve: {
			alias: {
				'@': resolve(__dirname, 'resources'),
			},
		},

		build: {
			outDir: 'public/build',
			emptyOutDir: true,
			sourcemap: false,
			cssCodeSplit: true,

			chunkSizeWarningLimit: 264,

			modulePreload: {
				polyfill: true,
			},

			commonjsOptions: {
				transformMixedEsModules: true,
			},

			rollupOptions: {
				output: {
					assetFileNames: 'assets/[name].[ext]',
					chunkFileNames: 'assets/vendors/[name].js',

					"manualChunks"(id) {
						if (id.includes('node_modules')) {
							if (
								id.includes('lodash') ||
								id.includes('date-fns')
							) {
								return 'utils';
							}

							if (
								id.includes('chart.js') ||
								id.includes('d3')
							) {
								return 'charts';
							}

							if (
								id.includes('animejs') ||
								id.includes('gsap')
							) {
								return 'anime-vendors';
							}

							return 'vendor';
						}
					},
				},
			},

			optimizeDeps: {
				include: ['lodash', 'axios'],
				exclude: [],
			},
		},

		preview: {
			port: 4173,
			host: true,
		},
	};
});