import { mkdir, writeFile, access } from 'node:fs/promises';
import { createWriteStream } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { pipeline } from 'node:stream/promises';
import { Readable } from 'node:stream';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
export const CACHE_DIR = join(ROOT, '.cache');
export const FRONTEND_JS = join(ROOT, '..', '..', 'assets', 'js', 'frontend.js');

export const WC_VERSIONS = ['11.1.2', '11.2.1'];

const ASSETS = {
	jquery: 'https://code.jquery.com/jquery-3.7.1.min.js',
	flexslider: (version) =>
		`https://raw.githubusercontent.com/woocommerce/woocommerce/${version}/plugins/woocommerce/client/legacy/js/flexslider/jquery.flexslider.js`,
	singleProduct: (version) =>
		`https://raw.githubusercontent.com/woocommerce/woocommerce/${version}/plugins/woocommerce/client/legacy/js/frontend/single-product.js`,
	variation: (version) =>
		`https://raw.githubusercontent.com/woocommerce/woocommerce/${version}/plugins/woocommerce/client/legacy/js/frontend/add-to-cart-variation.js`,
};

async function exists(path) {
	try {
		await access(path);
		return true;
	} catch {
		return false;
	}
}

async function download(url, dest) {
	if (await exists(dest)) {
		return dest;
	}

	await mkdir(dirname(dest), { recursive: true });
	const response = await fetch(url);
	if (!response.ok) {
		throw new Error(`Failed to download ${url}: ${response.status} ${response.statusText}`);
	}

	const tmp = `${dest}.tmp`;
	await pipeline(Readable.fromWeb(response.body), createWriteStream(tmp));
	const { rename } = await import('node:fs/promises');
	await rename(tmp, dest);
	return dest;
}

export async function ensureAssets(version) {
	const dir = join(CACHE_DIR, version);
	await mkdir(dir, { recursive: true });

	const paths = {
		jquery: await download(ASSETS.jquery, join(CACHE_DIR, 'jquery-3.7.1.min.js')),
		flexslider: await download(ASSETS.flexslider(version), join(dir, 'jquery.flexslider.js')),
		singleProduct: await download(ASSETS.singleProduct(version), join(dir, 'single-product.js')),
		variation: await download(ASSETS.variation(version), join(dir, 'add-to-cart-variation.js')),
		frontend: FRONTEND_JS,
	};

	return paths;
}

export async function writePage(name, html) {
	const dest = join(CACHE_DIR, 'pages', name);
	await mkdir(dirname(dest), { recursive: true });
	await writeFile(dest, html);
	return dest;
}
