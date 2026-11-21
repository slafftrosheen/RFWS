# Reclame Fabriek website 2.2: everything from the old site, 6 languages, motion "Night shift", client portal

A small PHP site with its own Russian admin. **No WordPress, no plugins.** Pages, profiles and stories are JSON files in `site/data/`,
photos are in `site/uploads/`. The new **client portal** keeps requests, written quotes and messages in **PostgreSQL**
(a local server or Supabase). The public website itself never needs the database.

- **Languages:** English (main, at the old addresses), Latvian `/lv/`, Russian `/ru/`, Dutch `/nl/`, German `/de/`, Swedish `/sv/`. The portal and its emails use all six.
- **Admin:** `/admin/`, entirely in Russian. A guide for editors is in `ADMIN-GUIDE-RU.md`.
- **Client portal:** `/portal/` (and `/lv/portal/` …). The old WordPress `/my-account/` now leads there.

## Local development with Supabase

Install PHP 8.1 or newer with `pdo_pgsql`, `mbstring`, `gd` (WebP), `zip` and `fileinfo`. On Windows, enable those extensions in `php.ini` and set `extension_dir` to PHP's `ext` directory. You also need Node.js 20 or newer, pnpm, and a running Docker-compatible container runtime such as Docker Desktop.

From the repository root, start the local stack and apply the portal migration:

```sh
pnpm dlx supabase@3.2.0 start
```

Start the site from PowerShell:

```powershell
.\source\dev.ps1
```

Use `-Php 'C:\path\to\php.exe'` if PHP is outside your PATH. On macOS or Linux, you can also use:

```sh
DATABASE_URL='postgresql://postgres:postgres@127.0.0.1:54322/postgres' \
RF_SITE_URL='http://127.0.0.1:8080' RF_NO_MAIL=1 \
php -d upload_max_filesize=25M -d post_max_size=100M -d max_input_vars=10000 \
  -S 127.0.0.1:8080 -t site site/index.php
```

- Open the [website](http://127.0.0.1:8080/), [client portal](http://127.0.0.1:8080/portal/), or [Supabase Studio](http://127.0.0.1:54323/).
- Open the [admin](http://127.0.0.1:8080/admin/) to generate a setup code in `site/data/_auth/setup-code.txt` and create your local administrator.
- The portal uses PHP for account management and stores attachments on disk. Test portal emails, including verification and password reset links, are written to `site/data/_portal/outbox/`. Contact submissions are saved in `site/data/_inbox/`.
- Stop PHP with `Ctrl+C`, then run `pnpm dlx supabase@3.2.0 stop` to stop Supabase and keep your database data.

## New in 2.2

### Nothing from the old site is missing
The live WordPress site was crawled again (all 35 pages) and compared sentence by sentence and image by image. Restored:
- Home: all **28 factory photos** (2.1 showed 8), all **21 profiles** (2.1 showed 9), the photos next to the services, the hero's slideshow photos, and the full original texts of the services and CubeMöbel. Also the heading "Strong team – fulfilling all your wishes".
- Every profile: the **RAL colour guide**, the **construction drawing** (9 profiles), the original "Customisation" text, and the right depth note. Profiles 7 RCP, 7 DTF and 7 TGP now correctly say custom depths are **not** available; 2.1 said they were.
- Signage Research: the **full original text** (2.1 had condensed it), including the "Advanced technologies" introduction and "Leading the future of signage".
- Page header photos on Product profiles, Projects, Research and Contact (they were CSS backgrounds on the old site).
- Old addresses: `/category/...`, `/shop-2/`, `/cart-2/`, `/checkout-2/`, `/jet-popup/...` and `/my-account/` redirect to the right page.
- Result of the re-check: **0 sentences and 0 images missing**. Two stock images from the old site (a night city and a monotone sign) are kept in the media library but not shown, following the brand rules.

### Every text in six languages
The 8 project stories are now translated into LV, RU, NL, DE and SV, as is every restored text. The "this story is in English" note no longer appears anywhere.

### Motion "Night shift" (adds to "Switch-on" from 2.1)
- **Hero:** real project photos cross-fade every 7 seconds with a slow push-in. Only the first photo loads with the page.
- **Profile rail:** all profiles in one snap-scrolling strip with arrows, a progress bar and Box letters / Lightboxes tabs. On lit profiles, a Lumen trace runs around the card on hover. Unlit profiles stay quiet.
- **Factory film strip:** two rows of production photos drifting in opposite directions. Hover or keyboard focus stops them, and every photo opens in the viewer.
- **"Anatomy of the letter"** on every box-letter profile: the profile's real layers (back, LED modules, sides, trim, face) come apart as you scroll, go back together and switch on in that profile's light type (frontlit, halo, neon, side-lit…). The labels come from the profile's own specifications, and a "Take it apart" button works in every motion mode.
- **Research:** the four technologies sit on one line that fills as you read.
- **Small touches:**
  - a 2 px LED-strip reading progress bar under the header;
  - a quick dim-and-strike transition between pages (Chrome, Full motion only);
  - the header shows how many profiles you've added to a request.
- **Under control:**
  - **"Pause motion"** sits in the footer and on the home hero (WCAG 2.2.2) and is remembered across pages;
  - Calm and Off modes, and visitors who ask for reduced motion, get the still, scrollable versions.

### Faster
- **Full-page cache:** rendered pages are served from `data/_cache` in about 1 ms, with ETag revalidation (an unchanged page answers "304 Not Modified" with 0 bytes). Every admin save clears it. The portal, the admin and forms are never cached.
- **Compression:** pages are compressed by PHP when the server doesn't do it. Brotli or deflate handles CSS, JS, JSON and SVG.
- **Bundles:** minified CSS and JS, with the font CSS merged in (one request fewer). Assets are cached for a year and refreshed through `?v=`.
- **Images:**
  - Every photo now also has a **400 px** version, so thumbnails and the film strip load a quarter of the pixels.
  - The header logo went from a 52 KB PNG to a 5 KB WebP.
  - A replaced photo gets a new file name, so long caching never shows the old one.

Measured on a phone (390 px, 2× screen, compressed sizes):

| Page | 2.1 | 2.2 |
|---|---|---|
| Home, first view | 309 KB | **272 KB** |
| Profile 4, first view | 434 KB | **120 KB** |
| Signage Research, first view | 268 KB | **225 KB** |
| Home, scrolled to the end | 1.1 MB | 1.25 MB, now with 28 factory photos (2.1: 8), 21 profiles (2.1: 9) and 4 hero photos |

### Client portal
For B2B clients, in six languages, styled like the site:
- **Requests built from the website's profiles.** Clients can press "Add to request" on any profile page; the header counts them, and the portal opens with those profiles filled in. Each sign has: profile (with photo and link), text or logo, letter height, depth (with that profile's standard depths), quantity, face and side colours (RAL/NCS), mounting (no frame / on frame) and notes. Files can be attached: PDF, AI, EPS, SVG, DXF, DWG, CDR, images or ZIP, up to 25 MB each.
- **Written quotes** with numbers (RF-Q-2026-0001), lines linked to profiles, VAT, validity date, lead time and terms in the client's language. The client **accepts online** by typing their name as a signature, or declines with a comment. New versions replace old ones automatically. The quote can be printed or saved as PDF.
- **One message thread per request**, with attachments, for both sides. Emails go out in the client's language for new messages, quotes and status changes.
- **Production progress:** Received → In review → Quote sent → Quote accepted → In production → Ready → Installed, shown as an LED strip with the current step lit.
- **Accounts:** self-registration with email confirmation (optionally approved by staff first), invitations from the admin, password reset, account and language settings.
- **Admin (Russian):** Client requests (with new and unread badges), a quote editor with live totals, Clients (approve, block, invite, delete with all data), Portal settings, and **"Move to client portal"** for any request sent through the website's contact form.

## Folder

| Path | What |
|---|---|
| `site/` | **Upload this whole folder** (including the hidden `.htaccess` and `.user.ini` files) to the web root. |
| `site/index.php` | Public site: routing, languages, page cache, form endpoint `/api/contact`, `sitemap.xml`, `robots.txt`, the `/portal/` route |
| `site/app/` | Core, page templates, icons, `db.php` (database), `portal.php` (client portal). Blocked from the web |
| `site/admin/` | Russian admin (`portal_admin.php` = portal screens) |
| `site/data/` | All content as JSON (blocked from the web). `_cache/` = cached pages (safe to delete), `_portal/files/` = client files, `_portal/outbox/` = a copy of every portal email, `_auth/` = admin users and the database address |
| `site/uploads/` | Photos as WebP in 400 / 800 / 1600 / 2400 px. Scripts can't run here |
| `source/portal-schema.sql` | The portal's tables (they are created automatically; this file is for reference or for Supabase's SQL editor) |
| `source/tools/` | Content audit and restore, translations, build (`build.sh`), tests (admin e2e, portal e2e, motion, crawl, accessibility, performance) |
| `previews/` | Screenshots and the motion preview |

## Going live

1. **Back up WordPress** (files and database) and keep the backup.
2. Check the host runs **PHP 8.1 or newer** with `gd` (WebP), `zip` and **`pdo_pgsql`** (for the portal). These are standard.
3. Delete the WordPress files from the web root and upload the contents of `site/`.
4. Make `site/data/` and `site/uploads/` writable by PHP (usually `chmod -R 775`, owner = web user).
5. Open `https://reclamefabriek.eu/admin/`. The first visit creates a one-time **setup code** in `data/_auth/setup-code.txt`. Read it over FTP or SSH and enter it to create the first administrator.
6. **Connect the portal database** (see below), then open Admin → Кабинет клиентов → Настройки кабинета. It should say "Подключено". The tables are created automatically.
7. Send a test request from the contact page and register a test client at `/portal/register/`. Check the emails arrive. If the host blocks PHP `mail()`, everything is still saved, and the portal settings show which emails didn't go out. Ask the host for SMTP, or send invitation links from the client card.
8. Submit `https://reclamefabriek.eu/sitemap.xml` in Google Search Console.
9. **Have a person review the privacy policy.** It should now also mention the client portal: account data, files, quotes, messages, and where the database is hosted.

### The portal database: local PostgreSQL or Supabase
**Local PostgreSQL** on the same server:
```
sudo -u postgres createuser rf --pwprompt
sudo -u postgres createdb reclamefabriek -O rf
```
Address: `postgresql://rf:PASSWORD@127.0.0.1:5432/reclamefabriek`

**Supabase:** Project Settings → Database → Connection string → **Session pooler** (port 5432), then add `?sslmode=require` at the end:
`postgresql://postgres.PROJECT:PASSWORD@aws-0-eu-central-1.pooler.supabase.com:5432/postgres?sslmode=require`
Choose an EU region. Turn on daily backups in Supabase.

Put the address in **one** of these places:
- **Recommended:** a server environment variable `DATABASE_URL`, for example `SetEnv DATABASE_URL "postgresql://…"` in the Apache vhost, or `fastcgi_param DATABASE_URL …` on nginx;
- or Admin → Настройки кабинета → "Адрес подключения", which is saved in `data/_auth/db.json` (blocked from the web, never included in backups).

**Back up the database:** `pg_dump` daily on a local server, or Supabase's backups. The admin's backup zip covers the website content, not the portal.

### nginx
```
location ~ ^/(data|app|tools)/ { deny all; }
location = /.user.ini { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
location ~* \.(css|js|json|woff2|webp|png|jpg|svg|ico)$ { expires 1y; add_header Cache-Control "public, immutable"; try_files $uri =404; }
location /admin/ { try_files $uri /admin/index.php?$query_string; }
location / { try_files $uri /index.php?$query_string; }
client_max_body_size 100m;
gzip on; gzip_types text/css application/javascript application/json image/svg+xml;
```
PHP-FPM reads `site/.user.ini` (uploads up to 25 MB, max_input_vars 10000).

### After editing CSS or JS
Run `node source/tools/build.mjs` from the repository root. It rebuilds `site.min.css` (fonts included), `site.min.js`, `portal.min.css` and `portal.min.js`. Without the `.min` files, the site falls back to the readable sources automatically.

## Security
- **Admin:** password hashing, an HttpOnly/SameSite session cookie, CSRF tokens on every form, a strict Content-Security-Policy, no framing, and login lockout after 5 wrong attempts. Uploads are checked and re-encoded.
- **Visitors** get **no cookies**. Only people who open `/portal/` get one, a separate HttpOnly/SameSite/Secure session.
- **Portal:**
  - Every query is limited to the logged-in client: another client's request, quote or file returns 404 (tested).
  - CSRF tokens on every form and a strict CSP.
  - Rate limits on login, registration, password reset, messages and new requests.
  - Email confirmation, with one-time links that expire (48 h confirmation, 2 h reset, 7 days invitation), stored only as hashes.
- **Client files:**
  - stored outside the web under random names;
  - only listed file types, up to 25 MB;
  - always downloaded, never run or shown in the browser.
- A quote acceptance records the name typed as a signature, the time and the IP address.

## Checked (all on 2.2)
- **Content against the live site:** 0 sentences and 0 images missing on all 35 pages.
- **Client portal:**
  - **59 / 59** end-to-end checks (`portal_e2e.py`) on a real PostgreSQL;
  - covers the full flow, from registration and email confirmation to a quote accepted online;
  - security: access control between clients, CSRF, files, password reset, the contact form moved to the portal, and deleting a client.
- **Admin:** **82 / 82** browser checks (`e2e.py`), including the new grouped interface texts, with 0 PHP warnings.
- **Motion:** 19 / 19 (2.1 checks) + 19 / 19 (new 2.2 checks), in both normal and reduced motion.
- **Six-language crawl:** 210 pages at 1440 px plus phones, with 341 internal links. Result: no broken images or links, no sideways scrolling, one H1 per page, red at most once per screen.
- **Accessibility (WCAG 2.2 AA):** 156 page checks covering EN, RU, DE and LV, the portal, the profile anatomy, phones, desktop, and full and reduced motion. Result: **0 violations**.
