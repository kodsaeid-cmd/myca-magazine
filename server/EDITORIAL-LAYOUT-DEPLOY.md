# MYCA editorial test-domain deployment and eventual production migration

**Domain policy, confirmed 2026-11-02**

- `https://mycablog.dandoonii.ir` — **primary experimental development and QA website**, including `/admin/`, PHP API, MySQL CMS, and Editorial Layout Studio.
- `https://staging.mycablog.dandoonii.ir` — previous isolated CMS test host. No longer the default deployment target; retain only as a temporary backup until the main experimental host passes acceptance testing.
- `https://blog.mycaapp.ir` — **final production domain for the magazine**. This is a future separate deployment and migration, NOT yet the target of any experimental ZIP or FTP publishing.

The current `dandoonii.ir` property is experimental; **keep global robots.txt Disallow and X-Robots-Tag noindex,nofollow on it**. The production migration must intentionally remove these restrictions and update all canonical, sitemap and site_url settings.

## Safely consolidate the staging CMS at the existing experimental magazine domain

1. Back up **both** existing document roots and the existing working CMS MySQL database. Back up the `/media/uploads` directory from the existing staging document root; it holds files created by admin uploads, not included in code releases. Preserve the current `public_html` magazine before changing anything.
2. Check in phpMyAdmin whether `cms_editorial_layout` and `cms_editorial_layout_history` already exist in the **existing working CMS DB**. If either is missing, import `server/migrations/001_editorial_layout.sql` into **that DB**. The statements are additive (`IF NOT EXISTS`), not destructive.
3. Keep the existing first administrator and article database. Do **not** create a second admin or clear the tables. The PHP config is stored privately at `/home/mycabl/myca-magazine-config.php`, outside the webroot. Check that `db_*` settings still reference the database where the first admin exists.
4. In that **private config file**, set `'site_url' => 'https://mycablog.dandoonii.ir'`. This matters: the app uses site_url for canonical URLs and CSRF Origin checking; leaving it set to the old staging host blocks admin POST requests on the new experimental host. Keep DB password and session secret unchanged. The former staging admin may stop accepting POSTs after this host change; that is expected because the primary test host moved.
5. Download the `myca-cms-experimental.zip` attached to a **successful** release `experimental-cms-<commit>`. Extract *its contents* into the existing document root of `mycablog.dandoonii.ir`, `/home/mycabl/public_html`. Do NOT upload the older `myca-cms-staging.zip` or the indexable `myca-magazine-cpanel.zip`. Do not delete the document root before deployment.
6. **Copy** every persistent user-uploaded file from the old staging document root's `media/uploads/` to `/home/mycabl/public_html/media/uploads/`, preserving names and directories. The CMS records paths such as `/media/uploads/photo.webp`; the files must exist on the new host for previews and published articles to work.
7. Verify `https://mycablog.dandoonii.ir/api/index.php?route=health`, `/admin/`, login with the **existing** admin, `/stories/`, one article, image upload/readback and `/editorial-preview/`. If `/admin/` logs in but save/publish fails with 403, inspect the private `site_url` and HTTPS Origin. Do not share credentials in chat.
8. Save/publish a test editorial layout from the CMS and verify the live experimental homepage (content order, responsive images, noindex headers, canonical on the temporary host). Ensure `robots.txt` is `Disallow: /`, and server responses carry `X-Robots-Tag: noindex, nofollow`. Do not confuse this experiment with the eventual indexable production domain.
9. Keep the old `staging.mycablog.dandoonii.ir` until the temporary consolidation is fully verified. After that, it may remain as an isolated sandbox, but `mycablog.dandoonii.ir` is the working test domain.

## GitHub release / FTP options

- `.github/workflows/staging-cpanel.yml` now deliberately packages the full CMS for the **experimental mycablog** host with noindex protection; releases are named `experimental-cms-<SHA>`.
- `.github/workflows/parspack-ftps.yml` is an **optional manual** FTPS publisher to the cPanel FTP account already pointed at `/home/mycabl/public_html`. It defaults to dry-run and **must not be published until backups and DB migration are completed**. It preserves PHP CMS rewrite routes and excludes the persistent user-uploaded `media/uploads` folder from sync.
- Updating code in GitHub / creating a ZIP **does not update the actual host**. Upload the release manually or explicitly use Actions `MYCA CMS | Experimental ParsPack FTPS` > Run workflow > `publish=true` once it is safe. There is no automatic live deployment.

## Architecture / rollback

- Layouts are versioned and stored in the existing CMS database; draft changes do not affect the public homepage until published by an editor or admin.
- The experimental PHP homepage renders the published layout with real MySQL articles. Before the first layout publication, it falls back to the existing prerendered index.html. It is not a replacement for a fully deployed production site.
- Rollback: restore the backed-up `public_html` files and any required DB snapshot if schema/data was modified. For homepage-only rollback, remove/comment `RewriteRule ^$ api/homepage.php [L,QSA]` in the experimental `.htaccess`; prerendered `index.html` remains available.
- When migrating to `blog.mycaapp.ir` later, take fresh DB + media backups, deploy a **separate production build** (no experimental noindex header), change `site_url`, SEO canonical and sitemap links, validate DNS/SSL and redirect policy, and run acceptance checks before indexing.
