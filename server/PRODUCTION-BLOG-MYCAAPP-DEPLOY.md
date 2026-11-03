# MYCA Magazine — production deployment on blog.mycaapp.ir

**Ownership/operations:** MYCA retains full control of source, CMS, MySQL, cPanel/ParsPack, media and publication. The `mycaapp.ir` domain owner only provides the `blog` DNS A record. No Docker, GitHub access or server access from the parent-domain owner is required.

## 1. DNS + cPanel

1. DNS: `blog.mycaapp.ir` must resolve to the verified **public IPv4 of our own ParsPack host**. Do not use an unverified IP or change the parent's NS records.
2. cPanel > Domains: add `blog.mycaapp.ir` with **Share document root OFF**. Set independent root to `/home/<cpanel-account>/blog.mycaapp.ir` (replace placeholder with actual home path). Never point it to staging `public_html`.
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
- The backend looks for the private production file when the production document-root folder is named **exactly** `blog.mycaapp.ir`. For a different folder name use the server environment variable `MYCA_CMS_CONFIG_PATH` pointing to an absolute private config path.
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

## 6. GitHub → ParsPack continuous deployment (after first release)

The existing `.github/workflows/parspack-ftps.yml` is for staging ONLY.
Do not point its staging FTP user to the production directory or reuse staging deployment secrets. The previous staging FTPS handshake failed because the server presented a self-signed certificate.

For future hands-free production deployment, create a **dedicated** restricted FTPS or SSH/SFTP production deployment account, verify the server TLS/SSH identity, configure production-only Actions secrets, test a dry run, then opt into automatic deployment after passing CI. Never disable certificate validation as a shortcut. Database and uploaded media are NOT automatically copied by a code deploy.

## Cutover rule

Keep the experimental site blocked from search indexing. The public brand address is only `https://blog.mycaapp.ir`. Do not use URL forwarding or an iframe from the parent domain. Retain staging privately for QA or, if publicly visible staging must be retired after verified production launch, explicitly configure a staging redirect to production at that time — never before acceptance testing.
