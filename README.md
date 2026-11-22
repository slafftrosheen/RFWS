# Reclame Fabriek website 2.5: the LUMIGRID page (system + LEDNode controller), on top of 2.4

A small PHP site with its own Russian admin. **No WordPress, no plugins.** Pages, profiles and stories are JSON files in `site/data/`,
photos are in `site/uploads/`. The new **client portal** keeps requests, written quotes and messages in **PostgreSQL**
(a local server or Supabase). The public website itself never needs the database.

- **Languages:** English (main, at the old addresses), Latvian `/lv/`, Russian `/ru/`, Dutch `/nl/`, German `/de/`, Swedish `/sv/`. The portal and its emails use all six.
- **Admin:** `/admin/`, entirely in Russian. A guide for editors is in `ADMIN-GUIDE-RU.md`.
- **Client portal:** `/portal/` (and `/lv/portal/` …). The old WordPress `/my-account/` now leads there.

## Local development with Supabase

Install PHP 8.1 or newer with `pdo_pgsql`, `mbstring`, `gd` (WebP), `zip` and `fileinfo`. On Windows, enable those extensions in `php.ini` and set `extension_dir` to PHP's `ext` directory. You also need Node.js 20 or newer, pnpm, and a running Docker-compatible container runtime such as Docker Desktop.

For LAN testing with Docker Desktop, set **Network → Port binding behavior** to **Localhost by default** before starting Supabase. This keeps Supabase's published ports on the development host.

From the repository root, create a loopback-only Docker network once, then start Supabase and apply the portal migrations:

```sh
docker network create --opt com.docker.network.bridge.host_binding_ipv4=127.0.0.1 rfws-loopback
pnpm dlx supabase@3.2.0 start --network-id rfws-loopback
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

### Test from another PC on the LAN

Find the Windows host's LAN IPv4 address with `ipconfig`. Use that address in place of `192.168.1.50` below:

```powershell
.\source\dev.ps1 -ListenAddress 192.168.1.50
```

If Windows Firewall blocks inbound connections, add a rule from an administrator PowerShell for TCP port 8080 on that address:

```powershell
New-NetFirewallRule -DisplayName 'RFWS dev site (LAN)' -Direction Inbound -Action Allow `
  -Protocol TCP -LocalAddress 192.168.1.50 -LocalPort 8080 -RemoteAddress LocalSubnet
```

On a PC on the same LAN, open `http://192.168.1.50:8080/portal/` for the client portal or `http://192.168.1.50:8080/admin/` for the admin sign-in. Verification and password reset links in the local outbox use the LAN URL.

## New in 2.5: the LUMIGRID page

A new page at **`/lumigrid/`** (and `/lv/lumigrid/`, `/ru/lumigrid/` …), in all six languages, editable in the admin under **Страницы → LUMIGRID**.
It replaces the old landing page (`algorhythmicdev.github.io/lumigrid-lander`), which no longer exists: the **menu item** and the **“Open the LUMIGRID page” button** on Signage Research now open this page.

**Every fact comes from the LUMIGRID firmware repository** (LumigridLedNode 1.0.0-rc2, its docs and `SignageTypes.hpp`): 16 outputs (A1–A8 WS2812 RGB / SK6812 RGBW up to 256 pixels each, P1–P8 independent PWM dimmers on a PCA9685), ESP32 with 8 MB flash, 24 effects, 14 palettes, 32 scenes, 3 layers per output with 4 blend modes, 8 zones, timelines of 16 steps, 8 cue lists × 16, schedules, Art-Net/sACN (off by default), sync of up to 8 nodes, roles and 30-minute sessions, OTA with rollback, backup/restore, soft start, power estimate. The page keeps the firmware’s own honesty rules: no sync-accuracy, flicker or weather claims, power estimate called an estimate, and the firmware called a release candidate in bench testing.

Sections: hero → figures → **the system** (sign → LEDNode → browser → more nodes / lighting desk) → **LEDNode** (board drawing + specifications) → **effect lab** → scenes, schedules and cues → first light in four steps → on your network → contact band with a link to the technical guides on GitHub.

### Motion: one lit moment per screen
- **Hero:** the word LUMIGRID is drawn in LED pixels. One serpentine “strip” runs through the letters and switches on in order, then a slow amber flow runs on it. The real H1 stays in the page for search and screen readers.
- **LEDNode:** the 16 output LEDs on the board light once, one after another, when the drawing is seen. The system line powers up once.
- **Effect lab:** the visitor picks one of the **24 firmware effects** and **14 palettes** and a speed; the browser draws 8 pixel strips and 8 dimmers. The maths is a **direct port of the firmware’s `EffectRenderer.cpp`**, so what is on screen is what the controller computes. Effects that also suit a dimmer are marked, as in the firmware catalogue. Flashing effects (strobe, lightning) are held well below three flashes a second.
- **Calm, Off, reduced motion:** still frames everywhere. The lab still works: every choice draws a still frame at the effect’s most telling moment. The **pause button** stops everything; the animation also stops when the lab is off screen or the tab is hidden.
- Weight: the page script is 14 KB (6 KB compressed) and the page CSS 8 KB; both load **only on this page**. No libraries, no video.

### In the admin
**Страницы → LUMIGRID**: every text in six languages, figures, the four system steps, LEDNode specifications, the lab labels, effect names and descriptions (the effect code must stay as it is), palette names (one line, comma-separated, in firmware order), cards with icons, the four setup steps, the bottom band and the GitHub link. The form stays under 1,000 fields, so it saves on hosts that keep PHP’s default limit.

### Not used on purpose
The old lander folder in the repository holds a few short video clips (named neon, halo, LED node promo, casino front, prop example and one after a film franchise). The current lander no longer uses them, and one is tied to a brand we have no rights to, so the new page does not use them either. Real photos of LUMIGRID installations can be added later.

## New in 2.4

### Night preview (motion + a real tool)
On every profile page, a new dark section **“See your sign switched on”**: the visitor types their text, picks the light (front-lit, halo, front + halo, front + side, neon-look, unlit, flat, lightbox), face, LED and return colours and a wall (brick, plaster, shop window, graphite panel, or **a photo of their own facade**, which stays on their device and is shown whole, never cropped). Then they **switch it on**.
- The sign strikes like a real one: a short flicker, then full light, the halo on the wall and the light spill. One lit moment, then it stays still. Calm motion = a soft fade; reduced motion, “off” or the pause button = instant.
- Drag the sign (or arrow keys) to place it; a 1.8 m figure shows the scale when it fits; the line under the picture says from how far the letters read.
- **Request this sign** opens a new portal request with the profile chosen, the text and height filled in, the picture attached and a summary of the choices in the line notes (it survives the login step).
- In the request form, every line has a **Night preview** button: it opens the same tool prefilled from that line and **attaches the picture** to the request.
- Drawn on a canvas in the brand font, no libraries; `preview.min.js` is 14 KB (5 KB compressed) and loads only on profile pages and the new-request form. The canvas has a live text description for screen readers.

### Status line powers up
In the portal, the request's status line now powers up from left to right and each finished step lights in turn, then the current step keeps its slow hum (full motion only).

### Clients
- **Installation date:** the team proposes a date, time and crew; the client gets an e-mail, sees it in “Needs your attention” and **confirms with one click or asks for another date**. Both sides can **add it to their calendar** (.ics). Shown in the activity feed.
- **Extra notification addresses:** up to 3 (accounting, site manager). They receive request updates, never login or password links.

### Admin (Russian)
- **Production board** (`?a=pboard`): requests in columns by status; **drag a card** to change the status (the client gets the usual e-mail, or not, with one switch). Keyboard and no-JavaScript path: a status menu + → on every card. Cards show the client's deadline (soon / late), unread messages, a proof or quote waiting, the installation date, a reminder and the owner's initials. Filter by colleague.
- **Installation calendar** (`?a=pcal`): month view of installations (confirmed / waiting for the client / new date asked) and client deadlines.
- **Internal card on every request:** owner, **“remind me” date** and **notes for colleagues** (never shown to the client, tested). New list tabs “Mine” and “Remind today”; the dashboard lists today's reminders and the week's installations.
- **Price list** (`?a=pprices`): standard lines with the text in 6 languages; **“+ From price list”** in the quote editor adds the line in the client's language.
- **Reply templates** (`?a=preplies`): frequent answers in 6 languages, inserted in the client's language with `{name}`, `{company}`, `{no}`, `{title}` filled in.
- **CSV export** of all requests (opens in Excel: `;`, UTF-8 with BOM).
- **Dashboard figures:** open requests and how many wait for our quote, quotes waiting for clients, accepted this month (net) and 90-day conversion, installations in the next 7 days, requests per month.
- **Server check:** the dashboard warns when the server's PHP is missing security fixes or close to its end of support.
- Fixed: the site now always uses **Daugavpils time** (`Europe/Riga`), whatever the server's own time zone. Before, a server set to UTC would have shown portal times 2 hours early.
- Database: schema **v3**, upgraded automatically on the first portal request (new columns on requests and clients, a `rf_notes` table). Price list and templates are files: `data/pricelist.json`, `data/replies.json` (included in the admin backup).

## Is PHP still a good choice?
Yes, for this site. It is a content site with a client portal: forms, files, e-mails and a database. Modern PHP (8.4 / 8.5) handles that fast, with no build server and no long-running process to babysit. It runs on any ordinary EU host, the old WordPress site already ran on PHP, and the code is plain typed PHP with no framework or plugins to keep updated. What matters more than the language is **keeping PHP itself updated**:
- Run **PHP 8.4 or 8.5**. They get security fixes until the end of 2028 and 2029. PHP 8.2 stops on 31 December 2026, PHP 8.3 at the end of 2027, and 8.1 and older get no fixes at all.
- In October 2026 PHP fixed two critical remote-code flaws. The minimum safe releases are 8.3.36, 8.4.27 and 8.5.12. The admin dashboard now warns if the server is older.
- The code has been checked for the PHP 8.4 / 8.5 deprecations (implicitly nullable parameters, old casts and similar). Tests run on PHP 8.3.
- Consider a different stack only if the plan changes to something PHP is not built for, for example live machine data from the factory floor over websockets, or a mobile app sharing code with a JavaScript front end.

## New in 2.3.1: original photos are never cropped
Every photo that comes from the original website is now shown **whole**, everywhere: on the website, in the client portal and in the admin.
- Cards, galleries, service photos, search and compare thumbnails keep their tidy grid, but the photo sits inside the frame (`object-fit: contain`) on a quiet matte (paper in the light theme, graphite in the dark), instead of being cut to fill it.
- Hero slideshow and page-head photos: the full photo sits on the dark band, aligned right on desktop and at the top on phones, so the headline keeps its dark side.
- Team portraits, product photos and story covers keep their own proportions.
- Nothing zooms into a photo any more (no hover zoom, no slow push-in), because zooming cuts the edges too. Light sweeps, dimming and the reveal on scroll stay.
- Partner logos in the footer are no longer squeezed on phones.
- **Checked** with `nocrop23.py`: 181 page views (desktop light, phone dark), **3,216 photo placements**, including hovering cards and the next hero slide. Each one is drawn whole at its own proportions, not scaled up and not cut by its frame. **0 cropped.** Accessibility re-check: 116 page checks, 0 violations. Motion checks: 19/19 + 19/19.

## New in 2.3

### Header: theme, language, search
- **Theme switch in the header** (sun / moon): **Light**, **Dark** or **Auto** (follows the visitor's system). The page never flashes the wrong theme: a 0.9 KB script sets it before anything is drawn. When the visitor switches, the new theme spreads out in a circle from the switch, like lights going on or off (full motion only; instant otherwise).
  - The dark theme ("Night shift") is a real second design, not an inverted page: graphite surfaces, the same single red signal, Lumen glows a little more. The written quote, the RAL guide, construction drawings and partner logos stay on light paper so they read like documents.
  - Line animations follow the theme: graphite lines become paper lines in the dark.
  - Admin → Настройки → «Сайт» does not need anything; the default for new visitors is Auto.
- **Language menu**: the language code, its own name and the English name, a tick on the current one, arrow keys, Esc. It keeps you on the same page. The choice is remembered (in the browser, no cookie).
  - **First visit:** if the browser speaks one of the six languages and the page is in another, a slim bar offers it in that language ("Šī lapa ir pieejama arī latviski"). It never redirects by itself, and closing it is remembered.
  - **Phones:** the menu has all six languages as large buttons and the three themes as a segmented switch.
- **Search** (header button, **Ctrl K / ⌘K** or **/**): profiles with photos and light type, project stories, pages and quick actions (request a quote, client portal, new request, switch theme, switch language). It ignores accents ("latviesu" finds "Latviešu") and highlights what matched. Keyboard only works too. The index is one small file per language (`/search.json`, `/lv/search.json` …).

### More signage motion
- **11 new Lottie animations** (same rules: shape layers only, graphite line + one Lumen moment, nothing bounces): drawing and proof, bending the returns, painting, LED modules, mounting, delivery, the whole letter assembling and switching on, a neon "OPEN" sign, "sent", a design proof being approved, and a three-pixel LED loader.
- **Home → "How a box letter is made"** (new section, 6 languages, editable in the admin): the letter assembles on a dark panel (back → returns → LEDs → face → switch-on → slow hum), and six steps sit on one wire that powers up as you scroll; each step's animation plays when the current reaches it. The texts use only facts from the site (machines, Tikkurila paint, LED warranties, template or rails).
- **Profile cards** tilt slightly toward the pointer and a Lumen edge follows it on lit profiles (mouse only, full motion only).
- **Dark sections**: the heading strikes once like a tube when it comes into view.
- **Back to top**: an LED ring that fills as you read.
- **Toasts** confirm "Added to your request · 3 in total · Open request".
- All of it stops with "Pause motion", in Calm/Off mode and for visitors who ask for reduced motion.

### New tools for visitors
- **Compare up to 3 profiles**: a small button on every profile card, a bar at the bottom, and a side-by-side table (light, letter height, standard depths, custom depth, every specification) with the differences marked. Kept while browsing.
- **Contact page**: the neon "OPEN" sign and the **local time at the factory** in Daugavpils. If you enter working hours in Admin → Настройки → «Часы работы», it also says "Open now" or "Closed now".

### Client portal: more for B2B clients
- **"Needs your attention"** on the dashboard: quotes to decide, proofs to approve, unread messages, feedback to give, each with a direct link.
- **Filters and search** on the request list (In progress / Needs me / Finished / All, plus text: number, project, sign text, address). Instant with JavaScript, and they also work without it.
- **A small LED strip** on every request row shows how far along it is.
- **Design proofs:** staff upload a proof (PNG, JPG, WebP or PDF) in the admin. The client sees images right on the page (and full screen), approves with their name as a signature, or writes what to change. A new version replaces an unapproved old one. Everything is recorded (name, time, IP) and both sides get an email.
- **Sites (address book):** shops, branches, building sites with a contact person and mounting notes. Pick one in a new request and the address fills in; or tick "Save this address" while sending.
- **Activity:** one timeline of everything that happened on all requests (status, quotes, proofs, our messages), grouped by day. A badge on the tab counts what's new.
- **Order again:** one click starts a new request with the same signs, colours, sizes and address.
- **All files as ZIP** (sorted into "from you" and "from Reclame Fabriek"), and **Copy link**.
- **Live messages:** an open request checks for new messages every 20 seconds (and when you come back to the tab), adds them to the thread and shows a toast.
- **Letter-height helper:** type a height and it says roughly how far away it reads (rule of thumb for good contrast).
- **Drafts:** an unsent request is kept on the device while typing and offered back next time (files excepted).
- **Feedback after installation:** 1 to 5 stars and a comment; staff see it on the request.
- The portal follows the dark theme too; the written quote stays a white sheet.
- **Database:** 2.3 adds three tables (`rf_sites`, `rf_proofs`, `rf_feedback`) and two columns. They are created automatically the first time the portal opens (schema version 2). Nothing has to be done by hand. `source/portal-schema.sql` is updated for Supabase's SQL editor.

### Admin (Russian)
- Request page: new **«Макеты на утверждение»** block (upload, history of versions, client decisions) and **«Отзыв клиента»**.
- Home page editor: new section **«Как делается вывеска (6 шагов, анимации)»**.
- Settings: **«Часы работы»** for the contact page.
- Interface texts: new group «Главная: шаги производства, «Контакты»: время»; theme, search and compare texts are in «Меню и шапка» and «Профили».

## New in 2.2 (still included)

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
| `site/admin/` | Russian admin (`portal_admin.php` = portal screens, `ops_admin.php` = 2.4 board, calendar, price list, templates, CSV, dashboard figures) |
| `site/data/` | All content as JSON (blocked from the web). `_cache/` = cached pages (safe to delete), `_portal/files/` = client files, `_portal/outbox/` = a copy of every portal email, `_auth/` = admin users and the database address |
| `site/uploads/` | Photos as WebP in 400 / 800 / 1600 / 2400 px. Scripts can't run here |
| `source/portal-schema.sql` | The portal's tables (they are created automatically; this file is for reference or for Supabase's SQL editor) |
| `source/tools/` | 2.4: texts `tr24.py`, tests `portal24_e2e.py`, `motion24.py`, `axe24.py`, screenshots + demo data `shoot24.py`. Earlier: content audit and restore, translations (`tr23.py` = 2.3 texts), animations (`make_lotties23.py`), no-crop check (`nocrop23.py`), build (`build.sh`), tests (admin e2e, portal e2e + `portal23_e2e.py`, header `hdr23.py`, motion `motion23.py`, crawl, accessibility `axe23.py`, performance `perf23.py`) |
| `previews/` | Screenshots (both themes, phone and desktop, portal and admin), the 2.4 night-preview shots and motion preview |

## Going live

1. **Back up WordPress** (files and database) and keep the backup.
2. Check the host runs **PHP 8.4 or 8.5** (minimum 8.3.36; 8.2 and older are end of life or about to be) with `gd` (WebP), `zip` and **`pdo_pgsql`** (for the portal). These are standard.
3. Delete the WordPress files from the web root and upload the contents of `site/`.
4. Make `site/data/` and `site/uploads/` writable by PHP (usually `chmod -R 775`, owner = web user).
5. Open `https://reclamefabriek.eu/admin/`. The first visit creates a one-time **setup code** in `data/_auth/setup-code.txt`. Read it over FTP or SSH and enter it to create the first administrator.
6. **Connect the portal database** (see below; updating from 2.3.x: the database upgrades itself to schema v3 on the first portal visit, and old cached pages are ignored automatically), then open Admin → Кабинет клиентов → Настройки кабинета. It should say "Подключено". The tables are created automatically.
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
Run `node source/tools/build.mjs` from the repository root to rebuild the site, portal, Night preview and LUMIGRID bundles. The site falls back to the readable sources when a minified file is missing.

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

## Checked on 2.5
- **LUMIGRID page** (`lumigrid25.py`): **102 / 102**: six languages (status, H1, nav marked current and internal, 24 effects, 14 palettes, no sideways scroll, no red button in the page, no console errors); the hero word is really lit (canvas pixels read back, warm colour); the lab animates, switches effect and palette, all 24 effects run, the live text follows; the pause button stops the loop and resumes; the board lights and the system line powers up once; **reduced motion**: still frames that are lit, a new still frame on every choice, no auto-play; phones at 390 and 360 px; links (Signage Research button in EN and RU, sitemap, search, GitHub); **axe-core WCAG 2.2 AA: 0 violations** in light and dark, EN and RU.
- **Admin:** the LUMIGRID editor opens, saves a Russian change without touching the other languages, keeps the effect codes, figures and GitHub link, and the site shows the change at once. The form sends 922 fields (under PHP's default limit of 1,000).
- **Whole site crawl** (`qa.py`): **216 pages** (210 + the new page in six languages), 361 page loads, 353 internal links: **0 issues**.
- **Header** (`hdr23.py`): **148 / 148**, now with the LUMIGRID menu item opening inside the site.
- **Client portal** (`portal24_e2e.py`): **48 / 48** on a real PostgreSQL, no PHP warnings.

## Checked on 2.4
- **New in 2.4** (`portal24_e2e.py`): **48 / 48** on a real PostgreSQL: preview on a profile page (light preset from the profile, Latvian description, scale line, picture download) → “Request this sign” → login → request with the picture attached and the line filled in; preview dialog in the request form attaching a second picture; extra e-mails (bad address refused, cleaned list); dashboard figures; reply template inserted in Latvian with name and number; price list → quote line in the client language; owner, reminder and notes, with “Mine” and “Remind today”; **internal notes never appear on the client page**; installation proposed → e-mail to the client and both extra addresses → “Needs your attention” → .ics → client confirms → calendar; board drag & drop and the keyboard path, kept after reload; CSV export; no JavaScript errors, no PHP warnings.
- **Motion** (`motion24.py`): **8 / 8**: full motion switches the sign on once when it is seen, flickers while striking and then holds still; reduced motion never auto-starts and lights at once; the pause button gives a soft fade with no flicker; the status line animates only with full motion. Older motion checks: **15 / 15** (`motion23.py`).
- **Accessibility (WCAG 2.2 AA, axe-core 4.10)**: **76** new page checks (`axe24.py`): six profile pages with the preview off and on, portal dashboard, request with the installation card, account, activity, new request with the preview dialog open; both themes, phone and desktop, full and reduced motion: **0 violations**. The 2.3 set (`axe23.py --quick`, 116 checks): **0 violations**. Two small things the newer checker found were fixed on the way (the dashboard filter links now use `aria-current`; the installation date tile keeps its contrast in the dark theme).
- **Six-language crawl** (`qa.py`): 210 pages, 351 page loads, 347 internal links: **0 issues** (red at most once per page: the preview’s “Request this sign” is a light button on purpose).
- **No cropping** (`nocrop23.py`): 181 page views, 3,216 photo placements: **0 cropped**. A facade photo in the preview is also drawn whole.
- **Earlier suites, unchanged:** portal 2.3 **64 / 64**, portal 2.2 **59 / 59**, admin **82 / 82**, header **148 / 148**.
- **Weight:** the preview script is 5.4 KB compressed and loads only on profile pages and the new-request form; site CSS +0.9 KB, portal CSS +0.4 KB compressed.

<details><summary>2.3 results (for reference)</summary>


- **Six-language crawl** (`qa.py`): 210 pages at 1440 px plus phones, 341 internal links. No broken images or links, no sideways scrolling, one H1 per page, red at most once per page. **0 issues.**
- **Accessibility (WCAG 2.2 AA, axe-core)** (`axe23.py`): 220 page checks in **both themes** (dark and light), phone and desktop, full and reduced motion, EN/RU/DE/LV, portal sign-in pages, 404, plus the language menu, theme menu and search open. **0 violations.**
- **Header** (`hdr23.py`): 148 / 148. Layout at 7 widths (1440 → 360 px) × 2 themes × 3 page types with no overlap or overflow; language menu (keyboard, Esc, keeps the page, remembered); language hint for a Latvian browser; theme menu, reload, system theme; search (Ctrl K, "/", results, Enter, no-results, theme action); phone drawer.
- **New client features** (`portal23_e2e.py`): **64 / 64** on a real PostgreSQL: helper, drafts, saved sites, order again, ZIP, proofs (upload → changes → v2 → approve, emails), activity + badge, live messages, feedback, filters with and without JavaScript, access control between clients (proof previews, ZIP, live refresh, sites, activity), compare, contact clock, process line, dark portal, no PHP warnings.
- **2.2 portal flow** (`portal_e2e.py`): **59 / 59** still pass after the schema upgrade.
- **Admin** (`e2e.py`): **82 / 82**, 0 JavaScript errors.
- **Motion**: 19 / 19 (2.1) + 19 / 19 (2.2) + **15 / 15 new** (`motion23.py`): steps light one by one, wire, Lotties load, card tilt, back-to-top ring, theme reveal, pause and reduced motion make everything still and instant.
- **Weight on a phone** (`perf23.py`, compressed): home first view **284 KB** (2.2: 272 KB), profile 4 **131 KB** (120 KB), Signage Research **235 KB** (225 KB). About +11 KB for the theme, search, compare and portal code; every new animation loads only when it scrolls into view. Home scrolled to the end: 1.3 MB (2.2: 1.25 MB), now with the process section and its 7 animations.

</details>

<details><summary>2.2 results (for reference)</summary>


- **Content against the live site:** 0 sentences and 0 images missing on all 35 pages.
- **Client portal:**
  - **59 / 59** end-to-end checks (`portal_e2e.py`) on a real PostgreSQL;
  - covers the full flow, from registration and email confirmation to a quote accepted online;
  - security: access control between clients, CSRF, files, password reset, the contact form moved to the portal, and deleting a client.
- **Admin:** **82 / 82** browser checks (`e2e.py`), including the new grouped interface texts, with 0 PHP warnings.
- **Motion:** 19 / 19 (2.1 checks) + 19 / 19 (new 2.2 checks), in both normal and reduced motion.
- **Six-language crawl:** 210 pages at 1440 px plus phones, with 341 internal links. Result: no broken images or links, no sideways scrolling, one H1 per page, red at most once per screen.
- **Accessibility (WCAG 2.2 AA):** 156 page checks covering EN, RU, DE and LV, the portal, the profile anatomy, phones, desktop, and full and reduced motion. Result: **0 violations**.

</details>
