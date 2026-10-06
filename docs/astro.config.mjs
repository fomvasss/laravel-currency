import { fileURLToPath } from 'node:url';
import { defineConfig, passthroughImageService } from 'astro/config';
import starlight from '@astrojs/starlight';
import starlightGitHubAlerts from 'starlight-github-alerts';
import starlightLinksValidator from 'starlight-links-validator';
import { remarkPlainMarkdown } from './src/remark-plain-markdown.mjs';

const base = '/laravel-currency';

export default defineConfig({
	site: 'https://fomvasss.github.io',
	base,
	image: { service: passthroughImageService() },
	markdown: {
		remarkPlugins: [[remarkPlainMarkdown, { root: fileURLToPath(new URL('.', import.meta.url)), base }]],
	},
	integrations: [
		starlight({
			title: 'Laravel Currency',
			description: 'Currency conversion, exchange rates and formatting for Laravel',
			social: [{ icon: 'github', label: 'GitHub', href: 'https://github.com/fomvasss/laravel-currency' }],
			// the pages live in docs/ itself, not in src/content/docs/
			markdown: { processedDirs: ['.'] },
			expressiveCode: { shiki: { langAlias: { env: 'dotenv' } } },
			editLink: { baseUrl: 'https://github.com/fomvasss/laravel-currency/edit/master/docs/' },
			plugins: [starlightGitHubAlerts(), starlightLinksValidator()],
			sidebar: [
				{ label: 'Getting started', items: [{ label: 'Overview', slug: 'index' }, 'installation', 'configuration'] },
				{
					label: 'Usage',
					items: [
						'usage/conversion',
						'usage/providers',
						'usage/base-currency',
						'usage/historical-rates',
						'usage/formatting',
						'usage/helpers-blade',
						'usage/caching',
						'usage/custom-providers',
					],
				},
				{
					label: 'Reference',
					items: ['reference/currency', 'reference/providers', 'reference/contracts', 'reference/commands'],
				},
				'upgrading',
			],
		}),
	],
});
