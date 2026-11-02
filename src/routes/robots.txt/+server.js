// Live MYCA magazine CMS: index only approved, published server-rendered content.
// The legacy /article/ routes still contain editorial samples and are excluded.
export const prerender = true;
export function GET() {
  const body = [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin/',
    'Disallow: /api/',
    'Disallow: /media-check/',
    'Disallow: /article/',
    '',
    'Sitemap: https://mycablog.dandoonii.ir/sitemap-cms.xml',
    ''
  ].join('\n');
  return new Response(body, { headers: { 'content-type': 'text/plain; charset=utf-8' } });
}
