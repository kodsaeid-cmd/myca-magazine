# MYCA Magazine — Editorial enhancements (draft)

Scope: dedicated magazine only: `blog.mycaapp.ir`; no changes to `mycaapp.com`, shop or landing.

## Before deployment
1. Export a tested backup of the LIVE magazine files and `mycabl_Myca_Blog` database.
2. Compare live PHP/Svelte distribution against branch `main`; live may have hotfixes not in Git.
3. Run `server/migrations/002_editorial_enhancements.sql` **once** in the magazine DB only.
4. Ensure PHP has DOM and fileinfo, and configure `upload_max_filesize` and `post_max_size` to permit selected video size. Keep uploads non-executable.
5. Deploy compatible API, admin distribution, public templates and CSS together. Do not just copy Svelte source into cPanel.
6. Verify logging in, editor, video upload and playback, source rendering, TOC anchors, related links, reverting older revisions, 15-minute session behavior, SEO and mobile layout. Check error logs.
7. On failure, restore backed-up code and DB. The migration is additive, so keep new columns on partial rollback if data has already been saved.

## Features
- Upload of MP4/WebM to an isolated, non-executable media directory; embedded inline video is HTML5 controls, no autoplay.
- Sliding 15-minute authenticated session with renewed CSRF bound to the PHP session, not an unchecked long-lived token. Editor heartbeat only while editing actively.
- Editorial selection of related published articles (up to six); related links rendered in sidebar and end of each published article, with contextual fallback.
- Optional named expert opinion/role and real references with title and HTTPS link; do not imply professional validation without an actual expert.
- Automatic Table of Contents generated server-side from saved H2/H3 headings with stable in-page section IDs.

This is a **draft PR**, not a production deployment.