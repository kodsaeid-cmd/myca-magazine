import { articles, categories } from '$lib/content.js';

export const prerender = true;
const origin = 'https://mycablog.dandoonii.ir';

export function GET() {
  const paths = ['/', ...categories.map((c) => '/category/' + c.slug + '/'), ...articles.map((a) => '/article/' + a.slug + '/')];
  const xml = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
    paths.map((path) => '  <url><loc>' + origin + path + '</loc></url>').join('\n') + '\n</urlset>';
  return new Response(xml, { headers: { 'content-type': 'application/xml; charset=utf-8' } });
}
