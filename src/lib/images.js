// Media transport confirmed directly on /media-check/sample.jpg without VPN.
// Use the same first-party directory for every photo; no browser CDN calls.
export function imageFor(source, width = 1200, _quality = 86) {
  if (typeof source !== 'string' || !source.startsWith('/media-check/photo-')) return source;
  if (width > 450) return source;
  return source.replace(/\.jpg(?=\?|$)/i, '-sm.jpg');
}
