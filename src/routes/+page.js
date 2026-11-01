// Prerender the high-traffic editorial homepage as static HTML. The tiny
// /magazine-home.js file progressively enhances its interactive news desk.
// Inner article, category and search routes retain normal SvelteKit behavior.
export const csr = false;
