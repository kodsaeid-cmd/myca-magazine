import { error } from '@sveltejs/kit';
import { getCategory, getArticlesByCategory } from '$lib/content.js';

export function load({ params }) {
  const category = getCategory(params.slug);
  if (!category) error(404, 'دسته‌بندی پیدا نشد.');
  return { category, articles: getArticlesByCategory(params.slug) };
}
