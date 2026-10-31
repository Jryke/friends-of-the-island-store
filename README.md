# Friends of the Island Store: website

Website for the **Friends of the Island Store**, a non-profit supporting the Island Store on Isle au Haut, Maine. Visitors can read about the Friends, follow the newsletters, contact the board and donate online.

> This repository contains **only the files that run on the production server**. Secrets, client uploads and database dumps are deliberately not tracked. A running history of what changed and when is in [CHANGELOG.md](CHANGELOG.md).

## Stack

| Layer | What |
|---|---|
| CMS | [ExpressionEngine](https://expressionengine.com/) 7.5.27 (core is tracked so the exact version is pinned) |
| EE add-ons | Structure (page tree and newsletter listing), Fluid field (Page Content blocks), Email module (contact form), Rich Text Editor |
| Language / database | PHP 8.2, MySQL 8 |
| Front end | Bootstrap 5.3.8 (CDN) plus one custom stylesheet, `public_html/assets/css/style.css`; no build step |
| Hosting | Cloudways |
| Version control | Git; `main` = what is deployed |

## Services and integrations

| Service | Used for | Notes |
|---|---|---|
| **Zeffy** | Online donations | Zeffy's embed code is pasted into the CMS (Pages > Donate > Text + Side Panel > Donation Form Embed Code); the page outputs it as given. Zeffy has no test mode, so a real $1 donation (refunded) is used to test after deployment. |
| **MailerSend** | SMTP for contact-form email in production | To be set up with the client before launch. Needs a verified sending domain; credentials go in `private_html/.env.php` (`EE_SMTP_*`, `EE_WEBMASTER_*`). Mail is sent from the site address with the visitor as Reply-To. |
| **QR code** | Links printed material to the donation page | Encodes `/give`, which redirects (302) to `/donate`; tracking parameters can be added to that redirect later without reprinting. |

## Branding

The palette was sampled from the organization logo. The values live as CSS variables at the top of `public_html/assets/css/style.css`, which is the source of truth.

| Name | Hex | Variable | Use |
|---|---|---|---|
| Navy | `#153B6E` | `--fis-navy` | Primary: logo ring and wordmark, links, buttons |
| Deep navy | `#0D2647` | `--fis-navy-deep` | Footer, hero overlay, emphasis text |
| Soft navy | `#2E587E` | `--fis-navy-soft` | Logo door blue |
| Cedar | `#8A6234` | `--fis-cedar` | Accent: link hover, secondary buttons (`#6F4E27` on hover) |
| Sand | `#EDE5D6` | `--fis-sand` | Section backgrounds |
| Deep sand | `#DCD0BA` | `--fis-sand-deep` | Darker sand wash |
| Sky | `#A4DBFC` | `--fis-sky` | Logo sky |
| Ink | `#21303F` | `--fis-ink` | Body text |
| Soft ink | `#56626E` | `--fis-ink-soft` | Secondary text |
| Line | `#D9D2C6` | `--fis-line` | Borders and dividers |

**Typography** (Google Fonts, loaded in the `{head}` snippet): **Newsreader** (serif; body text and headings, weights 300-600) and **Source Sans 3** (sans-serif; navigation, buttons, forms, labels and footer, weights 400-700). Base size 17px (`1.0625rem`) with a 1.72 line height.

**Logo:** `public_html/images/uploads/logo.png` (900 x 600), used in the masthead and footer. A favicon has not been supplied yet.

**Name and contact:** "Friends of the Island Store"; public email `info@friendsoftheislandstore.org`.

## Repository layout

```
public_html/            Web root (mirrors Cloudways public_html)
  index.php, admin.php  EE front controller and control panel entry (load EE from ../private_html/system)
  assets/css/           Site stylesheet
  images/               EE image folders; images/uploads/ holds client uploads (not tracked)
  themes/               EE control panel themes
  .htaccess             Clean URLs (no index.php)
private_html/           Outside the web root
  system/ee/            ExpressionEngine core
  system/user/config/   config.php (tracked; no secrets)
  system/user/templates/default_site/   Templates and snippets
  .env.example.php      Reference list of every environment value (real values live in the untracked .env.php)
```

## Content model

Four channels (the channel IDs are referenced by Structure and templates):

| # | Channel | Purpose |
|---|---|---|
| 1 | **Home** | The home page (single entry) |
| 2 | **Pages** | All inner pages: About, Why the Friends, Newsletters, Donate, Contact, By-Laws, Legal. Built from a fluid field, **Page Content**, made of reusable blocks |
| 3 | **Newsletters** | One entry per issue, listed under the Newsletters page |
| 4 | **Site Info** | Footer text, phone numbers, public email, contact-form recipient, store hours |

**Page Content blocks:** Rich Text, Photo + Text Rows, Boxes, Number Callouts, People Cards, Text + Side Panel, Newsletters, Contact.

## Templates

Templates and snippets are files in `private_html/system/user/templates/default_site/`. On this install the files take precedence over the database copies.

Every page template follows this order:

```
{head}                     whole document head: <!doctype>, <html>, <head>, meta/fonts/CSS,
                           <title> + meta description, </head>, <body> (snippet)
{nav}                      skip link, masthead and menu; ends by opening <main id="main">
  ...page content...        page templates never write <main> themselves
{footer}                   begins by closing </main>; then footer, scripts, </body></html>
```

- **Snippets** (`{head}`, `{nav}`, `{footer}`) live in `_partials/` and appear under Design > Template Partials. `{head}` now outputs the entire document head through the opening `<body>` tag, including a single site-wide `<title>`/meta placeholder (to be managed per-page by the seo_lite add-on once installed). `{nav}` and `{footer}` contain their own entry loop, so they stay outside any other channel loop.
- **Embeds** in the `embeds` template group (names start with `_` so they are not reachable by URL) are used for pieces that run their own entry loop: `{embed="embeds/_store-hours"}`, `_store-phone`, `_contact-details`, `_contact-form`, `_latest-newsletter`, `_newsletter-list`.
- **Template groups:** `home` (default group: home page), `pages` (every Pages entry, plus 404 and the contact thank-you), `newsletters` (issue detail), `give` (`/give` redirect).
- **Fluid and grid syntax:** block tags are `{page_content:group}`, block fields `{group:field}`, grid columns `{gridfield:col}`; grid file columns use the full path pair `{group:gridfield:col}...{/group:gridfield:col}`.
- Rich text is output without classes; styling hooks are wrapper classes in `style.css` (`.reason-list`, `.text-page`, `.lead-text`, `.panel-rte`, `.donation-embed`).

## Configuration and environment

`private_html/system/user/config/config.php` is tracked and contains no secrets. Environment-specific values come from **`private_html/.env.php`** (untracked, next to `system/`): database, EE security keys, base URL and mail settings. `private_html/.env.example.php` lists every variable. Comments in that file must start with `#`.

`base_path` is derived from the config file location, and the upload directory uses `{base_path}images/uploads/`, so a database moved between environments carries no environment-specific paths or URLs.

## Deployment

1. Back up the live site (Cloudways backup plus a database dump).
2. Deploy `public_html/` and `private_html/` (git pull). Never overwrite `private_html/.env.php` or `public_html/images/uploads/` (neither is tracked).
3. Make sure template files and database templates agree; clear caches.
4. Smoke test: all pages, the contact form, `/give` and the donation form.

Each deploy is merged into `main` with a dated tag and a CHANGELOG entry.

## Not in this repository

`.env.php` and other secrets, client uploads (`public_html/images/uploads/*`), EE cache, and database dumps and backups. Database and upload backups are handled outside git.
