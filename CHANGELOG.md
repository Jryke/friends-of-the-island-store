# Changelog

A running log of every notable change made while developing the Friends of the Island Store website, including the reasoning and the problems found along the way. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); entries are dated (no version numbers yet). For general project information see [README.md](README.md).

Most of the CMS structure (channels, fields, entries, Structure pages) lives in the **database**, which is not tracked in git, so this changelog is the only record of those changes. Add a line under **Unreleased** with every change.

## [Unreleased]
- README now describes the production site only (local development and Mailpit removed) and has a new **Branding** section (color palette, fonts, logo, name and contact) taken from `style.css`.
- **Contact form** switched from EE's Email module (`{exp:email:contact_form}`) to a custom **`form_submission` add-on** (CP: Add-Ons > Forms): saves submissions to `exp_form_submissions` and emails a notification via MailerSend SMTP. `_partials.group/_contact-form.html` now wraps `{exp:form_submission:form}`; form fields renamed to `first_name`/`email`/`subject`/`message` to match the add-on's email builder.
- Contact recipient moved from the Site Info `site_form_email` field to the add-on's `recipient_email` setting (friendsoftheislandstore@gmail.com); the now-unused **`site_form_email` field was removed** from the site_info channel.
- Spam protection on the contact form: **reCAPTCHA v3** (invisible) + a **honeypot** field + a submission **time-trap** (min fill time, HMAC-signed timestamp).
- Add-on security hardening (pre-deploy audit): removed an unused redirect-countdown extension that carried a reflected-XSS / open-redirect vector (`$_GET['redirect_to']` written into `window.location`), and changed the CP "delete submission" action from a GET link to a CSRF-protected POST.
- Add-on hardening + cleanup pass (**v1.0.1**): POST/CSRF guard on the settings save (a GET could previously wipe settings); `process()` now ignores array-valued POST fields (no more 500) with field count/length caps; port 465 uses SSL while 587/2525 stay STARTTLS; settings reads collapsed to one cached query per request; removed dead code and unused language keys; corrected stale docblocks. `update()` clears the orphaned `Form_submission_ext` extension row (run the Add-On Manager update once per environment).
- Add-on settings (MailerSend SMTP creds, captcha keys) are stored **encrypted per environment** with `session_crypt_key`, so they are re-entered in each environment's CP (not carried by a DB copy); Install must be run per environment to create the tables.
- SMTP/deliverability: MailerSend sending domain `friendsoftheislandstore.org` verified (SPF/DKIM/DMARC pass, inbox delivery confirmed via a direct send test). This dev network blocks SMTP ports 587/465, so local uses port **2525**; EE's 5s `smtp_timeout` plus erratic local TLS means the actual email send is validated on **Cloudways staging**, not locally.
- Organization name made editable instead of hardcoded: a new `site_info` **Organization Name** field (`site_org_name`) drives the footer address and the contact-panel heading; the nav logo (`alt`/`aria-label`) and the 404 `<title>` use EE's built-in `{site_name}` global. The page `<title>` tags were left for the upcoming seo_lite per-page SEO setup. Nav/footer menu labels remain hardcoded (a Structure-based nav refactor was considered and deferred).
- Pre-launch to-do: Zeffy $1 live test donation after deploy, MailerSend SMTP values in the server's `.env.php`, QR code for `/give`, automatic image resizing for CMS uploads, favicon.

---

## 2026-10-25

### Added
- Git repository: `main` (deployed) and `develop` (ongoing work); `README.md`, `CHANGELOG.md`, `.gitignore`. Only production files are tracked; Docker/dev tooling, secrets, uploads, cache, database dumps and working notes are ignored.
- `private_html/.env.php` (untracked) for environment values and secrets (`EE_DB_*`, `EE_ENCRYPTION_KEY`, `EE_SESSION_CRYPT_KEY`, `EE_BASE_URL`, `EE_SMTP_*`, `EE_WEBMASTER_*`), plus the tracked reference `private_html/.env.example.php` (every variable with `****`). Distinct `EE_` names avoid clashing with the database variables Docker already injects.
- CSS override `.donation-embed [data-zeffy-embed]{max-width:none!important;margin:0!important}`. Cause: Zeffy's embed script adds an inline `max-width:600px; margin:0 auto` at runtime (not present in the pasted code), which centered the form in a narrower box. Scoping to the CMS wrapper keeps any newly pasted embed code covered.
- Working docs (kept out of git): deployment checklist, maintenance and backup plan (3-2-1), QA/launch checklist, Zeffy/MailerSend/QR setup guides.

### Changed
- **Folder layout now mirrors Cloudways:** `public/` became `public_html/` (web root) and `system/` moved out of the web root into `private_html/system/`. `index.php` and `admin.php` load EE from `../private_html/system`; the local Docker document root was updated to match. Reason: keeps EE core, templates, cache and `config.php` out of the web root.
- `config.php` no longer contains secrets: database, encryption keys, base URL and SMTP settings are read from `.env.php`. `base_path` is derived from the config file location, and the "Site Images & Files" upload directory uses `{base_path}images/uploads/`. Reason: a database copied between local and production carries no environment-specific paths or URLs, and no secrets are committed.
- Donate page: the "Donation form address (Zeffy)" text field became **"Donation Form Embed Code"** (`donation_form_embed`, textarea). The pasted Zeffy embed code is output as given inside `.donation-embed`. Reason: Zeffy's plain share link only leads donors to Zeffy's own site; the embed code is what puts the form on the page.
- Verified after the folder move: every page, the control panel edit forms, the file manager, the contact form (via Mailpit) and 16 screenshots (desktop and phone) are pixel-identical to before.

---

## 2026-10-15

### Added
- ExpressionEngine **7.5.27** in Docker (created with the `ee-install` script); database `island_store_dev` (renamed before the installer was run).
- Static design integrated into EE: custom stylesheet (`assets/css/style.css`), Bootstrap 5.3.8 via CDN, logo and photos. Photos were registered in the File Manager with alt-text descriptions, in an upload destination "Site Images & Files", and resized to at most 2000px with colour profiles kept (originals were up to 14 MB).
- **First CMS build:** one channel per page type plus Site Info, Newsletters and Text Pages; one template group per page; `_layouts/main` layout; grid fields for repeating content (About photo rows and board members, Why sea-level numbers, Home and Donate boxes); embed partials for blocks with their own loops.
- Contact form using EE's Email module (`{exp:email:contact_form}`) with a thank-you page; Mailpit container for local mail; a "Simple" Rich Text Editor toolset (headings, bold, italic, links, lists).
- **Restructured CMS (channels wiped and rebuilt, IDs fixed):** `home` (1), `pages` (2) with fluid field **Page Content**, `newsletters` (3) and `site_info` (4). Content was carried over from a database export.
- **Page Content blocks:** Rich Text (standard, highlighted band, sections), Photo + Text Rows, Boxes, Number Callouts, People Cards, Text + Side Panel, Newsletters (auto list of issues plus a "coming soon" box), Contact. Shared fields `block_heading`, `block_text`, `box_items`.
- **Structure** add-on for page URLs (top-level `/about`, `/why`, `/newsletters`, `/donate`, `/contact`, `/bylaws`, `/legal`) and the newsletters listing (`/newsletters/<slug>`); trailing slash off.
- `.htaccess` for clean URLs; site 404 template.
- Per-page "donate banner" fields (blank heading hides the banner). The static copy differed on every page, so the banner became per-page content, and Donate's banner was left blank because it was commented out in the static design.
- `/give` redirect (302 to `/donate`) for the donation QR code, with room to add `?utm_...` tracking later.
- Separate contact-form recipient field in Site Info (`site_form_email`) so testing mail can go to a developer address while public pages show the real info@ address.

### Changed
- **Template structure:** removed the `_layouts` group. Every page template is a full document using the snippets `{head}`, `{nav}`, `{footer}` (Design > Template Partials). `<main id="main">` opens at the end of `{nav}` and closes at the start of `{footer}`; page templates do not write it.
- The Home template moved to the `home` template group (made the default group, also holding `404` and `contact-sent`); `pages/index` renders every Page Content block; `newsletters/index` is the issue detail.
- Partials with their own entry loops (store hours, store phone, contact details, contact form, latest newsletter, newsletter list) stay **embeds**. Tried as snippets: the loop collided with the page loop (lost text, the newsletter links' `{url_title}` hijacked, fluid blocks broken). Their template names start with `_` so they are not reachable by URL.
- Contact form is sent from the site address with the visitor as Reply-To (MailerSend only sends from the verified domain). Local mail uses CRLF line endings, which Mailpit's SMTP needed.
- Footer and Contact public email set to `info@friendsoftheislandstore.org`; By-Laws uses plain headings and Legal uses underlined section headings (a template-level style choice).
- Rich text carries no classes; spacing and heading styles are applied from wrapper classes in the stylesheet (`.reason-list`, `.text-page`, `.lead-text`, `.panel-rte`, store-hours spacing).

### Removed
- First-build structure: the per-page channels (`home_page`, `about_page`, `why_page`, `donate_page`, `contact_page`, `text_pages`, `newsletters_page`), their per-page template groups, `_layouts`, and the layout-based partials.
- Add-ons installed by mistake through the CLI (Emoji, Structure); Structure was later installed deliberately.

### Fixed
- `style.css` returned 403 (file permissions), leaving pages unstyled.
- Why-page side photo rendered about 880px too tall: image dimensions were being taken from the uploaded file instead of the design's values.
- Photo colours shifted after resizing (the colour profile was dropped); regenerated from the originals.
- Grid file columns inside fluid blocks did not render; fixed with the full-path pair syntax `{group:gridfield:col}...{/group:gridfield:col}`.
- Contact form only sent after setting CRLF newlines and the correct TLS setting for the local mail server.
- Partial templates were reachable by URL (for example `/_partials/store-hours`); renamed with a leading underscore.
- Pixel comparison against the static design: Donate, Contact, By-Laws and Legal match exactly; Home, About and Why differ only by photo resampling. The Newsletters page differs by design (issues are listed, each opens on its own page).
