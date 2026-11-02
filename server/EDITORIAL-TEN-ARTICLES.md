# MYCA first ten real editorial stories — installation guide

## This release is for the existing experimental magazine

Host: `https://mycablog.dandoonii.ir` (temporary, global **noindex**).
Final intended SEO-indexable host: `https://blog.mycaapp.ir` after the separate production migration.

The 10 original Persian articles in `static/api/editorial-pack.php` are backed by credited public health references (NIA, CDC, NHS, MedlinePlus and WHO). Each has H2s, actionable checklists, related links, FAQ questions, image ALT text, keyword, unique SEO title and description. Claims of medical diagnosis, qualified professional review or verified MYCA performance must not be added without evidence.

## Art and media

There are **10 original generated editorial cover images** in a separately supplied `myca-editorial-covers.zip`. For every release/migration, extract the **contents of that archive into the exact experimental Document Root**. It contains `media/editorial/cover-01-...webp` through `cover-10-...webp`. The assets are first-party, 760×760 WebP covers, not loaded from external image CDN.

The separately supplied cover archive is a manual asset; it is **not in GitHub** and is not included in the CMS build ZIP. Back up `media/editorial/` with other persistent media when moving hosts, and do **not** delete it on re-deploy. If the original images archive is missing, request it again before installing the content pack.

## Controlled install

1. Back up the current CMS MySQL database and existing `public_html` files. Keep `media/uploads/` and private config outside webroot.
2. Check the existing CMS is healthy (`/api/index.php?route=health`) and the old admin works.
3. Download the successful `experimental-cms-<sha>` GitHub release `myca-cms-experimental.zip`. **Do not use** the old static `myca-magazine-cpanel.zip` or staging bundle.
4. Extract CMS build contents into `/home/mycabl/public_html` (for `mycablog.dandoonii.ir`) without deleting existing folders.
5. Extract the separately supplied `myca-editorial-covers.zip` into the **same root**; the exact files must resolve as `https://mycablog.dandoonii.ir/media/editorial/cover-01-caregiver-selection.webp` and so on.
6. Sign in to `/admin/` with the existing admin account. On **داشبورد**, click **نصب مقاله‌ها و جایگزینی ویترین**. Confirm the operation. The POST is admin-only, CSRF-guarded, database-transactional and only inserts articles with new slugs; it **never overwrites** previously edited articles.
7. In `مدیریت مقالات` verify ten published articles are available to edit. In `چیدمان ویترین مجله`, verify 4 hero + 3 editor's picks + 3 recent cards. Test `/stories/`, `/category/elder-care/`, `/sitemap-cms.xml`, `/sitemap.xml`, and a full article, e.g. `/stories/choose-elderly-caregiver-at-home/`.
8. Confirm each hero and card image resolves on desktop and mobile. Check original/temporary host still sends `X-Robots-Tag:noindex,nofollow` and `robots.txt` disallows crawling. Do not submit this experimental host for indexing.

## SEO production migration checklist

In the future on `blog.mycaapp.ir`: preserve CMS DB + both media directories, set the private `site_url` to the final canonical origin, deploy an **indexable production-specific build**, remove the experimental global robots noindex only after QA, ensure both sitemap routes use the new canonical origin, perform relevant redirects, configure Search Console and request crawl. **Not one of the experimental ZIP releases can be used directly as an indexable production build**. A real clinician/editorial quality review is advisable for medical and caregiving content before public SEO launch.

## Idempotency and rollback

The install command is safe to repeat: existing *published* package slugs are preserved without overwriting their text; existing draft/review/archived slug collisions cause a clean 409 and rollback. The dashboard still contains the import button after success; repeating with `replace_layout=true` republishes the ordered layout (previous layouts remain in history). A failed transaction does not partially insert the 10 articles.

The installer does not delete unrelated articles. Previous layout versions are restorable through Editorial Layout Studio. To restore the old static homepage during testing, comment `RewriteRule ^$ api/homepage.php [L,QSA]` in the experimental `.htaccess`.
