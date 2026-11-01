import { env } from '$env/dynamic/public';
export function GET({ url }) {
  const origin = (env.PUBLIC_SITE_URL || url.origin).replace(/\/$/, '');
  // Demo content must not be indexed until reviewed and replaced with approved articles.
  const isDemo = env.PUBLIC_ALLOW_INDEXING !== 'true';
  const body = isDemo ? 'User-agent: *\nDisallow: /\n' : 'User-agent: *\nAllow: /\nDisallow: /search\nSitemap: ' + origin + '/sitemap.xml\n';
  return new Response(body, { headers: { 'content-type': 'text/plain; charset=utf-8' } });
}
