// All editorial photo URLs are on the same domain as the magazine.
// Three image sizes prevent a large hero JPEG from being used in small cards.
export function imageFor(source, width = 1200, _quality = 86) {
  if (typeof source !== 'string' || !source.startsWith('/images/')) return source;
  const suffix = width <= 450 ? '-sm' : width <= 1050 ? '-md' : '';
  return source.replace(/\.jpg(?=\?|$)/i, suffix + '.jpg');
}
