# MYCA Editorial Layout Studio — deployment and rollback

This feature is committed to the main repository, but committing does **not** deploy it to ParsPack.
It adds an additive MySQL migration and a dynamic PHP-rendered homepage at `/`.

## Deployment order (isolated STAGING only)

1. Back up the entire staging MySQL database and the persistent `media/uploads` directory.
2. In cPanel phpMyAdmin, select the **staging CMS database** (not any live/production database).
3. Import `server/migrations/001_editorial_layout.sql`. Both statements use `CREATE TABLE IF NOT EXISTS`; existing CMS tables and user records are preserved.
4. Download the ZIP produced by a **successful** `Package MYCA CMS for isolated ParsPack staging` GitHub Action for this commit.
5. Extract the ZIP into the staging subdomain's existing document root. Do not delete the document root first. Do not erase or overwrite persistent `media/uploads` or the private `/home/mycabl/myca-magazine-config.php` file.
6. Open `/admin/`, sign in as admin or editor and select **چیدمان ویترین مجله**.
7. Create/publish at least one real CMS article; save a draft layout, visit the authenticated `/editorial-preview/`, then publish it.
8. Verify the public homepage `/`, its card order, section order, server-side HTML with JavaScript disabled, `/stories/`, SEO metadata, and mobile layout.

## How it works

- There is one homepage layout with exactly five movable templates: hero, categories, editor picks, latest, and MYCA call-to-action. Each can be hidden; templates enforce the MYCA design language.
- Manual mode displays precisely the selected article IDs in order. Auto mode puts manually pinned articles first, then fills remaining slots from the latest published articles in the selected category, while avoiding duplicate auto-filled cards between sections.
- Draft changes are private; `layout-save` uses optimistic version checks. `layout-publish` is separate, records a version snapshot, and changes the public layout only after the request succeeds.
- Published articles are fetched exclusively from MySQL, HTML-escaped on output, with links to canonical CMS story routes. Draft, review and archived articles cannot appear in published sections.
- Published homepage HTML is rendered by PHP and cached for at most 90 seconds. The original prerendered Svelte homepage is served until the **first layout publish** or whenever the optional CMS database is unavailable.
- Private preview requires an authenticated editor/admin and is served with `noindex` and no-store headers.
- Latest versions can be restored to a draft, then published deliberately. Restoring does not overwrite the active public homepage.
- The Cloudflare static Worker preview cannot execute PHP; the live editorial homepage requires the PHP-capable ParsPack test server.

## Security and migration checklist

- Never include passwords, setup tokens, or DB credentials in GitHub.
- Do not publish a new layout until at least one real CMS story is already in published state.
- Check that the server routes `/` and `/editorial-preview/` through `.htaccess` and supports PHP + PDO MySQL.
- Current `/category/*` static demonstration pages remain unchanged; the new PHP homepage category cards link to the dynamic `/stories/?category=...` listing.
- If the feature must be disabled after deployment, temporarily remove/comment the **root** `RewriteRule ^$ api/homepage.php [L,QSA]` line in the staging `.htaccess`; the original `index.html` is still in place. The private preview route may be removed independently.
- Do not execute this migration or publish this ZIP on production without reviewing the staging results.
