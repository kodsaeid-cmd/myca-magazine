import { inlinePhotos } from './inlinePhotos.generated.js';

// Critical: visitors must receive real photos IN the HTML itself.
// Separate /images/*.jpg requests are unreliable on some local networks.
// Keep the existing local static URLs as a safe build-time fallback only.
export function imageFor(source, width = 1200, _quality = 86) {
  if (typeof source !== 'string') return source;
  const match = source.match(/^\/images\/([a-z0-9-]+)(?:-(?:sm|md))?\.jpg(?:\?.*)?$/i);
  if (!match) return source;
  const variants = inlinePhotos[match[1]];
  if (!variants) return source;
  return width <= 400 ? variants.sm : variants.md;
}
