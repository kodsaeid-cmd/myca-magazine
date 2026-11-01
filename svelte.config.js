import adapter from '@sveltejs/adapter-static';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';
import { articles, categories } from './src/lib/content.js';

export default {
  preprocess: vitePreprocess(),
  kit: {
    adapter: adapter({ pages: 'build', assets: 'build', strict: true }),
    trailingSlash: 'always',
    prerender: {
      origin: 'https://mycablog.dandoonii.ir',
      entries: [
        '*',
        ...categories.map(({ slug }) => '/category/' + slug),
        ...articles.map(({ slug }) => '/article/' + slug)
      ]
    }
  }
};
