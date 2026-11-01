// Emergency low-bandwidth delivery mode based on the affected browser's report.
// The confirmed 32 KB control image loads, whereas a 98 KB image times out.
// Use the 17–29 KB JPEG variants in every magazine slot until a larger variant
// is proven safe on the target connection. Never fetch the original full JPEG.
export function imageFor(source, _width = 1200, _quality = 86) {
  if (typeof source !== 'string' || !source.startsWith('/media-check/photo-')) return source;
  return source.replace(/(?:-sm)?\.jpg(?:\?.*)?$/i, '-sm.jpg');
}
