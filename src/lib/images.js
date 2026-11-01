// Use Unsplash Image CDN's built-in size negotiation so a thumbnail never downloads a 2K hero.
export function imageFor(source, width = 1200, quality = 86) {
  try {
    const url = new URL(source);
    if (url.hostname !== 'images.unsplash.com') return source;
    url.searchParams.set('auto', 'format');
    url.searchParams.set('fit', 'crop');
    url.searchParams.set('w', String(width));
    url.searchParams.set('q', String(quality));
    return url.toString();
  } catch {
    return source;
  }
}
