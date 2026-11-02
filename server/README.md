# MYCA Content Studio — SvelteKit + PHP/MySQL

**Status:** Development only. Not deployed to the live ParsPack hosting.

## Architecture
- UI remains SvelteKit. Admin is /admin/ and uses Tiptap/ProseMirror.
- API is PHP 8.3 PDO/MySQL, session login and role-based access.
- Published stories are true server-rendered HTML at /stories/{slug}/ and sitemap-cms.xml, without relying on the client or triggering a SvelteKit rebuild per article.
- Current public homepage/demo articles remain unchanged in this development branch.

## Implemented now
- Protected first-admin setup; login/logout; session, CSRF and rate limiting.
- Live dashboard metrics, article list, create/edit, draft/review/publish, role enforcement.
- Semantic rich text editor with headings, inline formatting, lists, links, tables, quote, images and undo/redo.
- Meta Title/Description, Canonical, Robots, focus keyword, FAQ JSON-LD, image ALT.
- Article revisions, restore-as-draft, user audit log.
- Image upload to persistent /media/uploads (JPEG/PNG/WebP).
- Server-rendered published story page, public listing, sitemap.

## Not implemented yet
Full MaxGame-level Theme Studio, SEO automated recommendations/internal-link health, user management UI, editorial calendar/scheduling, live homepage content from CMS, advanced media processing and complete WordPress/Gutenberg feature parity. Do not confuse the tested foundation with a finished CMS.

## Setup and security
1. Never deploy an untested feature branch over production. Create a separate cPanel test subdomain and document root.
2. Create a dedicated MySQL database and DB user in ParsPack cPanel. Import server/schema.sql using phpMyAdmin.
3. Copy server/myca-config.example.php into /home/mycabl/myca-magazine-config.php OUTSIDE public_html. Put real DB credentials, a long random one-time setup token, independent session secret. Never commit or share them; restrict file permissions to the account.
4. Build in GitHub with npm install, npm run check, npm run build. Upload ONLY build/ contents to the staging document root. SvelteKit adapter-static copies static/api PHP files to build/api.
5. Use HTTPS to test /api/index.php?route=health and /api/index.php?route=me. Set up initial admin via /admin/ after database is ready, then rotate/remove setup token.
6. Log in and test draft saving, article editing, published /stories/slug/ SEO HTML, revision restoration, uploads, and /sitemap-cms.xml. Verify without JavaScript.
7. Back up DB and media independently. Never erase /media/uploads during deployments.
8. Do not publish this branch until authentication, database, PHP lint, SvelteKit build and crawlable article output are verified.

## Backend protections
Session Secure/HttpOnly/SameSite, CSRF same-origin enforcement, PHP PDO prepared queries, hashed passwords and rate-limited login failures, DOM HTML sanitization, randomized media filenames, MIME/size checks, upload-directory executable-file restrictions. Credentials are always outside the webroot.

## Important hosting detail
PHP uses dirname(__DIR__,2) from /public_html/api/bootstrap.php, pointing to /home/mycabl/myca-magazine-config.php. For isolated staging inside another account adjust the private path deliberately.

## Publishing
Public articles rendered server-side from MySQL. Client admin saves via authenticated API. 90-second public cache header. The static SvelteKit homepage does not yet automatically display new CMS content; additional integration is required before full launch.

## Visual identity
MYCA design language: cyan #00A4D4, navy #062E5C, deep teal #005474, white/offwhite, Vazir, Persian RTL. MaxGame admin informs workflows and density but never its red/black palette.
