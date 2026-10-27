const { mkdir, writeFile, access, rename } = require('node:fs/promises');
const { createWriteStream } = require('node:fs');
const { dirname, join } = require('node:path');
const { pipeline } = require('node:stream/promises');
const { Readable } = require('node:stream');

const ROOT = join(__dirname, '..');
const REPO_ROOT = join(ROOT, '..', '..');
const CACHE_DIR = join(ROOT, '.cache');
const FRONTEND_JS = join(REPO_ROOT, 'assets', 'js', 'frontend.js');

function toPublicPath(absPath) {
	return '/' + absPath.slice(REPO_ROOT.length + 1).split('\\').join('/');
}

const WC_VERSIONS = ['11.1.2', '11.2.1'];

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

	const tmp = `${dest}.${process.pid}.${Date.now()}.tmp`;
	await pipeline(Readable.fromWeb(response.body), createWriteStream(tmp));
	try {
		await rename(tmp, dest);
	} catch (error) {
		if (await exists(dest)) {
			return dest;
		}
		throw error;
	}
	return dest;
}

async function ensureAssets(version) {
	const dir = join(CACHE_DIR, version);
	await mkdir(dir, { recursive: true });

	return {
		jquery: toPublicPath(await download(ASSETS.jquery, join(CACHE_DIR, 'jquery-3.7.1.min.js'))),
		flexslider: toPublicPath(await download(ASSETS.flexslider(version), join(dir, 'jquery.flexslider.js'))),
		singleProduct: toPublicPath(await download(ASSETS.singleProduct(version), join(dir, 'single-product.js'))),
		variation: toPublicPath(await download(ASSETS.variation(version), join(dir, 'add-to-cart-variation.js'))),
		frontend: toPublicPath(FRONTEND_JS),
	};
}

async function writePage(name, html) {
	const dest = join(CACHE_DIR, 'pages', name);
	await mkdir(dirname(dest), { recursive: true });
	await writeFile(dest, html);
	return toPublicPath(dest);
}

module.exports = {
	CACHE_DIR,
	FRONTEND_JS,
	WC_VERSIONS,
	ensureAssets,
	writePage,
	toPublicPath,
};
