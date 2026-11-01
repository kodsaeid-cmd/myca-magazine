// MYCA magazine photos are served by the same Cloudflare domain as HTML.
// Pick a truly small file for each on-screen size instead of scaling a giant JPEG.
export function imageFor(source, width = 1200, _quality = 86) {
  if (typeof source !== 'string' || !source.startsWith('/images/')) return source;
  const variant = width <= 450 ? '-sm' : width <= 1050 ? '-md' : '';
  return source.replace(/\.webp(?=\?|$)/i, variant + '.webp');
}
