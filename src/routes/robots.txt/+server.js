// Staging contains editorial demo data. Do not index this domain.
export const prerender = true;

export function GET() {
  return new Response('User-agent: *\nDisallow: /\n', {
    headers: { 'content-type': 'text/plain; charset=utf-8' }
  });
}
