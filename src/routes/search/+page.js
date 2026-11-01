import { searchArticles } from '$lib/content.js';
export function load({ url }) {
  const q = (url.searchParams.get('q') || '').trim().slice(0, 120);
  return { q, results: searchArticles(q) };
}
