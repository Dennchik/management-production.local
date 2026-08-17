import fs from 'node:fs';
import path from 'node:path';

import { fonts } from './fonts.js';
import { paths } from '../config/path.js';

function generateFontsScss() {
	const fontsDir = paths.fonts.src;
	const targetFile = paths.fonts.dest;

	let content =
		'@use "variables.scss" as *;\r\n' +
		'@use "mixins.scss" as *;\r\n';

	if (fs.existsSync(fontsDir)) {
		const items = fs.readdirSync(fontsDir);

		let currentFontName = '';

		items.forEach((item) => {
			const fontName = item.split('.')[0];

			if (currentFontName !== fontName) {
				content += `@include font-face("${fontName}", "${fontName}", 400, "normal");\r\n`;
			}

			currentFontName = fontName;
		});
	}

	fs.mkdirSync(path.dirname(targetFile), { recursive: true });
	fs.writeFileSync(targetFile, content);
}

export function fontStyle() {
	return {
		name: 'fonts-style-plugin',

		buildStart() {
			fonts(paths.fonts.src);
			generateFontsScss();
		},

		configureServer(server) {
			const fontsPath = paths.fonts.src;

			if (!fs.existsSync(fontsPath)) {
				return;
			}

			fs.watch(
				fontsPath,
				{ persistent: true },
				(_eventType, filename) => {
					if (!filename) {
						return;
					}

					const ext = path.extname(filename).toLowerCase();

					if (ext !== '.ttf') {
						return;
					}

					fonts(fontsPath);
					generateFontsScss();

					server.ws.send({
						type: 'full-reload',
					});
				},
			);
		},
	};
}