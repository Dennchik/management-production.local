import fs from 'node:fs';
import path from 'node:path';

import ttf2woff from 'ttf2woff';
import ttf2woff2 from 'ttf2woff2';

export function fonts(fontsDir) {
	if (!fs.existsSync(fontsDir)) {
		return;
	}

	const items = fs
	.readdirSync(fontsDir)
	.filter((file) => file.endsWith('.ttf'));

	items.forEach((file) => {
		const ttfPath = path.join(fontsDir, file);
		const baseName = path.basename(file, '.ttf');

		const ttfData = fs.readFileSync(ttfPath);

		const woffData = Buffer.from(
			ttf2woff(ttfData).buffer,
		);

		fs.writeFileSync(
			path.join(fontsDir, `${baseName}.woff`),
			woffData,
		);

		const woff2Data = ttf2woff2(ttfData);

		fs.writeFileSync(
			path.join(fontsDir, `${baseName}.woff2`),
			woff2Data,
		);
	});
}