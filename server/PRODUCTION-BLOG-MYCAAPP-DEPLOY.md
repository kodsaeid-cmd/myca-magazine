# MYCA Magazine — production deployment on blog.mycaapp.ir

**Ownership/operations:** MYCA retains full control of source, CMS, MySQL, cPanel/ParsPack, media and publication. The `mycaapp.ir` domain owner only provides the `blog` DNS A record. No Docker, GitHub access or server access from the parent-domain owner is required.

## 1. DNS + cPanel

1. DNS: `blog.mycaapp.ir` must resolve to the verified **public IPv4 of our own ParsPack host**. Do not use an unverified IP or change the parent's NS records.
2. cPanel > Domains: add `blog.mycaapp.ir` with **Share document root OFF**. Set an independent root, typically `/home/<cpanel-account>/public_html/blog.mycaapp.ir` on current cPanel or `/home/<cpanel-account>/blog.mycaapp.ir` when the host permits it. Do **not** share the staging document root itself (`/home/<cpanel-account>/public_html`). Record the exact root shown after creation.
3. Verify DNS resolves, issue and test a valid HTTPS certificate for the full hostname, and enable Force HTTPS after the certificate works.
4. The Apache vhost must serve PHP and honor `.htaccess` rewrites (mod_rewrite) and `DirectoryIndex index.php`.

## 2. Production MySQL (separate from experimental)

1. Back up the experimental MySQL database and `media/uploads/`, `media/editorial/` before migration.
2. Create a dedicated production MySQL database and user with only the required privileges.
3. Import the current CMS data from an SQL dump from the experimental database. A fresh empty installation may instead import `server/schema.sql` plus migrations, but that **does not transfer the current published articles or users**.
4. Verify the actual CMS article count, editorial layout, revisions, media table, admin accounts, permissions and the two ten-article editorial packs. When importing existing data, don't rerun the initial-admin setup.
5. Audit `cms_articles.canonical_url` for hard-coded `mycablog.dandoonii.ir` values; update any explicitly authored canonical URLs to `https://blog.mycaapp.ir/stories/{slug}/` or clear them to use automatic production canonical generation. Back up before any SQL change.

## 3. Production private PHP config

- Copy `server/myca-config.production.example.php` OUTSIDE the document root to `/home/<cpanel-account>/myca-magazine-config-production.php`.
- Set production-only `db_host`, `db_name`, `db_user`, `db_password`, a random `session_secret`, and `site_url = https://blog.mycaapp.ir`.
- Never add the live configuration, SQL dumps, credentials, or session tokens to GitHub.
- The backend looks for the private production file under the account home when the production document-root folder is named **exactly** `blog.mycaapp.ir`, including the default `public_html/blog.mycaapp.ir` layout. For a different folder name use the server environment variable `MYCA_CMS_CONFIG_PATH` pointing to an absolute private config path.
- The PHP backend rejects requests whose `Host` does not match the configured `site_url`. The public ZIP also contains an Apache Host guard.
- Existing staging remains at `/home/<cpanel-account>/public_html` and uses `/home/<cpanel-account>/myca-magazine-config.php`.

## 4. Obtain and deploy MYCA production package

From GitHub > Actions > `MYCA Magazine | production blog.mycaapp.ir` > Run workflow.
The job builds CSS/JS, local Vazir WOFF2 fonts, CMS PHP, and public article-cover assets. It strips legacy prerendered sample routes, replaces staging robots with production robots, and generates **myca-cms-production.zip**.

This job **packages only**. It does not push to ParsPack or alter live CMS. Back up the target web root, then deploy only the contents of the ZIP into the independent production root; do not delete persistent `media/uploads/` and `media/editorial/` from the production host.

The original curated editorial cover images in `media/editorial/` were imported separately to the experimental host and are NOT tracked in GitHub. Copy them from the original editorial-cover archive or staging media directory before publishing. The additional ten cover assets under `media/editorial-more/` are included by the build.

## 5. Smoke test before public launch

Test each directly on production HTTPS (no search index needed until launch):

- `https://blog.mycaapp.ir/` — real MySQL homepage, not mock Svelte cards.
- `https://blog.mycaapp.ir/admin/` — admin login, CSRF and content editing.
- `https://blog.mycaapp.ir/api/index.php?route=health` — PHP/MySQL healthy.
- `https://blog.mycaapp.ir/stories/` — published article listing.
- `https://blog.mycaapp.ir/stories/choose-elderly-caregiver-at-home/` — real article and cover.
- `https://blog.mycaapp.ir/fonts/Vazir-Regular.woff2` — HTTP 200 WOFF2.
- `https://blog.mycaapp.ir/sitemap.xml` — production URLs.
- `https://blog.mycaapp.ir/robots.txt` — no global `Disallow: /`.
- Verify canonical tags, `og:url`, user login, media upload, and article edits.
- Verify experimental host has `noindex`; production should NOT have a global `X-Robots-Tag: noindex`.
- Verify both desktop/mobile layout and first 10 original + next 10 editorial covers.
- Verify backups/restoration procedures, error logs and file permissions.

## CMS account editing and image diagnostics (November 2026)

- Dashboard > **بررسی تصاویر مقالات** > **اسکن فایل‌های تصاویر**: check every published article's local cover URL against physical files in the production document root. The report separately counts articles without inline images. The current source-backed editorial text packs do **not** include interior illustrations by default.
- Dashboard > **کاربران و دسترسی‌ها**: the admin can click **ویرایش حساب** to change a user's display name, email, role, active status and reset their password. Password fields have visibility toggles. Setting a new password requires 14-256 characters, is hashed server-side, and invalidates prior sessions. Changing your own admin password requires the current password.
- Initial ten cover assets are **not included in git source**. Preserve/copy `media/editorial/cover-01...cover-10*.webp` from the existing experimental site's `media/editorial` folder or the original MYCA editorial covers ZIP, to `/home/<cpanel-account>/blog.mycaapp.ir/media/editorial/`. Do NOT overwrite unrelated uploads.
- Additional ten covers are compiled from the verified photo preparation script into `media/editorial-more/` by `npm run build`.
- The `/api/index.php?route=media-audit` endpoint requires an authenticated admin. It does not expose filesystem paths to visitors or mutate files.
- Do not log into a public site on HTTP or with an untrusted/self-signed TLS identity; resolve CA/CAA and HTTPS before working with live credentials.

## Batch images: all twenty editorial guides

The original server had 21 published articles; the twenty source-backed editorial guides did not include inline images. All covers were reported on disk, but some browsers could not load their direct URLs.

**Fix:** Public homepage, article list, story cover and in-article photos now use the validated first-party PHP transport `/api/image.php?p=<URLENCODED_MEDIA_PATH>`, restricted to verified JPG, PNG and WebP under `/media/` and `/media-check/`.

After extracting a fresh production ZIP into `/home/<account>/blog.mycaapp.ir`:
1. Do not delete `media/editorial/`, `media/uploads/` or `.well-known/`; copy the ten original editorial covers from the staging site's `media/editorial/` if missing. The additional ten covers and child-themed local JPGs are compiled into the production package.
2. On HTTPS in **MYCA Content Studio → Dashboard → بررسی تصاویر مقالات**, run `اسکن فایل‌های تصاویر` to confirm the cover files are valid images, not merely existing paths.
3. As an authenticated admin, click **افزودن تصاویر به تمام ۲۰ مقاله اصلی** once. The UI performs a dry-run preflight and then a transactional, idempotent update; it inserts three contextual first-party images into the body of each of the 20 curated original articles, keeping any article that already contains an image intact. Images come from the existing MYCA photo library; this is **60 image placements**, not 60 independently commissioned unique photographs. The 21st custom article remains untouched.
4. Article snapshots are saved to `cms_revisions` before each update. No schema migration is needed.
5. Verify an example in the browser: `/api/image.php?p=%2Fmedia%2Feditorial%2Fcover-01-caregiver-selection.webp` must return a renderable image with an image Content-Type. Then open `/stories/choose-elderly-caregiver-at-home/` and refresh without cache.
6. If the one-click step reports `INLINE_IMAGE_FILE_MISSING_OR_INVALID`, first restore the exact asset at the named path. Never force replacement of absent or corrupt images.

Public HTTPS remains important for credentials and admin operations. The first-party PHP gateway does not resolve a self-signed SSL certificate or DNS CAA restriction.

## 6. GitHub → ParsPack continuous deployment (after first release)

The existing `.github/workflows/parspack-ftps.yml` is for staging ONLY.
Do not point its staging FTP user to the production directory or reuse staging deployment secrets. The previous staging FTPS handshake failed because the server presented a self-signed certificate.

The production-safe deployment workflow is now at `.github/workflows/production-parspack-ftps.yml`. It is **disabled for automatic publication** by default and never uses the staging FTP account.

1. Create a **dedicated FTPS account** whose FTP root is exactly the **production** document root `blog.mycaapp.ir`. Verify its directory scope in cPanel.
2. On GitHub **Settings > Secrets and variables > Actions**, set repository variable `MYCA_PROD_FTPS_SERVER` to the FTP hostname matching the certificate. Add secrets `MYCA_PROD_FTPS_USERNAME` and `MYCA_PROD_FTPS_PASSWORD`; never share credentials in messages or commit them.
3. If ParsPack uses a private/self-signed FTPS certificate, first independently verify its full certificate and SHA-256 fingerprint with ParsPack support, then store the verified PEM as optional secret `MYCA_PROD_FTPS_CA_PEM`. Prefer a publicly trusted FTPS server certificate. **Never disable TLS checks.**
4. Generate a production candidate for the **current main commit** via `MYCA Magazine | production blog.mycaapp.ir` Action. Successful packaging produces `production-candidate-<sha>`.
5. Run `MYCA Production | ParsPack FTPS Deploy` with `publish=false`. This performs the FTP action's dry-run and MUST list the production directory, not staging files.
6. After database/media backups and SSL/domain checks, run the same Action with `publish=true` once and validate all production routes.
7. Only after successful verification, set repository variable `MYCA_PROD_AUTO_DEPLOY=true`. Automatic delivery then occurs on successful production-package workflow runs. Enable package-on-each-push separately, after signoff; at present its push trigger is limited to production workflow file changes (plus manual dispatch).

Database and uploaded media are NOT automatically copied by a code deployment. The earlier experimental FTPS handshake failed due to a self-signed certificate and must be resolved independently of DNS.

## Cutover rule

Keep the experimental site blocked from search indexing. The public brand address is only `https://blog.mycaapp.ir`. Do not use URL forwarding or an iframe from the parent domain. Retain staging privately for QA or, if publicly visible staging must be retired after verified production launch, explicitly configure a staging redirect to production at that time — never before acceptance testing.
