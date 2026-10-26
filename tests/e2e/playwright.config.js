const { defineConfig } = require('@playwright/test');
const { join } = require('node:path');

module.exports = defineConfig({
	testDir: '.',
	timeout: 60000,
	expect: {
		timeout: 10000,
	},
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 1 : 0,
	workers: 1,
	reporter: process.env.CI ? 'github' : 'list',
	use: {
		baseURL: 'http://127.0.0.1:4173',
		channel: process.env.CI ? undefined : 'chrome',
		headless: true,
		trace: 'off',
	},
	webServer: {
		command: 'python3 -m http.server 4173 --bind 127.0.0.1',
		cwd: join(__dirname, '..', '..'),
		url: 'http://127.0.0.1:4173/oli-variation-gallery.php',
		reuseExistingServer: !process.env.CI,
		timeout: 15000,
	},
	projects: [
		{
			name: 'chromium',
			use: {
				browserName: 'chromium',
			},
		},
	],
});
