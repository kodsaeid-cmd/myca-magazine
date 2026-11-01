import { error } from '@sveltejs/kit';
import { getArticle, articles } from '$lib/content.js';

export function load({ params }) {
  const article = getArticle(params.slug);
  if (!article) error(404, 'مقاله پیدا نشد.');
  const related = articles.filter((item) => item.category === article.category && item.slug !== article.slug).slice(0, 3);
  return { article, related };
}
