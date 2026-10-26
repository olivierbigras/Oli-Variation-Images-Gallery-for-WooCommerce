const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
	testDir: '.',
	timeout: 60000,
	expect: {
		timeout: 10000,
	},
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 1 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? 'github' : 'list',
	use: {
		channel: process.env.CI ? undefined : 'chrome',
		headless: true,
		trace: 'off',
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
