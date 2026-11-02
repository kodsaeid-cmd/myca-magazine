# MYCA Magazine — GitHub to ParsPack automatic deployment (temporary domain)

## Current hosts and boundaries

- **Testing**: `https://mycablog.dandoonii.ir` => cPanel Domain Document Root `/public_html`
- **Future production**: `https://blog.mycaapp.ir`, no deployment to this host yet.
- The temporary domain MUST remain noindex; the FTP workflow sets disallow/HTTP X-Robots-Tag.
- Existing MySQL records, uploaded images `media/uploads/`, and original editorial covers `media/editorial/` MUST be preserved.

## Existing problem and solution

The FTPS account has a password already configured (`MYCA_FTPS_PASSWORD` GitHub Actions secret), but the last connection failed with `DEPTH_ZERO_SELF_SIGNED_CERT` — a self-signed certificate on the ParsPack FTP service. We **will not** bypass TLS certificate verification.

1. Ask ParsPack support to replace the FTP service TLS certificate with one issued by a trusted CA **for the FTP hostname** (recommended). If that is not possible, request the service certificate as PEM and independently confirm its SHA-256 fingerprint and DNS name with ParsPack support before trusting it.
2. In GitHub `kodsaeid-cmd/myca-magazine`, go to **Settings > Secrets and variables > Actions > Secrets**.
   - `MYCA_FTPS_PASSWORD`: the existing dedicated FTPS deployment account password; do not print or send it here.
   - `MYCA_FTPS_CA_PEM`: entire verified PEM certificate content, added as a repository secret. GitHub Actions uses `NODE_EXTRA_CA_CERTS` with strict certificate validation.
3. Under **Variables**, set `MYCA_FTPS_SERVER` to the FTPS hostname exactly as covered by the verified certificate (if different from `mycablog.dandoonii.ir`). This must be the dedicated FTP endpoint, not necessarily a public website domain.
4. In cPanel **FTP Accounts**, verify user `myca-deploy@mycablog.dandoonii.ir` is restricted to the intended experimental `public_html` directory. If its home differs, reconcile `server-dir` in the workflow before enabling anything.
5. Do NOT set `MYCA_AUTO_DEPLOY` yet. On GitHub **Actions > MYCA CMS | Experimental ParsPack FTPS > Run workflow**, leave `publish=false` (dry-run). Verify it connects successfully and lists intended changes.
6. Run the same workflow with `publish=true` once, after taking a file and database backup, confirming the FTP scope and validating the dry run. Check URLs `/admin/`, `/api/index.php?route=health`, `/stories/`, `/fonts/Vazir-Regular.woff2`, and the homepage.
7. Only after this successful manual publication and verification, set GitHub repository variable **`MYCA_AUTO_DEPLOY=true`**. Future commits to `main` automatically trigger **Magazine CI** (Svelte and MySQL tests), then the deployment workflow on success. Failed builds do not publish.

Do not expose passwords, private keys, API tokens or the CA PEM in chats/screenshots. A trusted CA certificate is public information, but its fingerprint and hostname MUST be confirmed through a trusted ParsPack channel before using it.

### Alternative if FTPS service certificates cannot be fixed

If ParsPack enables SSH/SFTP, switch the workflow to an SSH/SFTP deployment with a dedicated restricted deploy key and verified SSH host key, rather than disabling FTP TLS checks. cPanel **Git Version Control** can also be used if enabled, but requires correct server build/deploy automation: merely cloning source is not enough for this SvelteKit build + PHP CMS.

### Content updates after connection

- GitHub changes update code, styles, templates, and the editorial import pack.
- **Actual CMS articles and layout positions live in MySQL**; deployment deliberately does not overwrite the database.
- To install the second ten articles once, sign into `/admin/` and click **افزودن ۱۰ مقاله جدید و تکمیل ویترین**. That action makes them editable in MySQL and grows the homepage to 20 cards.
- The expanded card feed keeps curated Hero and Editor's Picks sections and changes only Latest to dynamic mode at limit 13. It refuses to overwrite an unpublished layout draft.
- Fonts are delivered locally in `/fonts/` by the build script; the public HTML uses `/vazir-local.css`.

### Rollback

If the automatic deployment fails after touching the server, restore the previous `public_html` snapshot without touching MySQL or persistent media. Disable automation immediately by changing `MYCA_AUTO_DEPLOY` to `false`; this does not interrupt manual CMS editing.
