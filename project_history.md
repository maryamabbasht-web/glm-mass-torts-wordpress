# project_history.md

A working log of what changed in this project and why.

**Convention:** newest entry first. Every entry records the date, what changed, and the reasoning. Decisions and principles are *not* duplicated here — they live in [learning.md](learning.md). This file is the narrative; that file is the reference.

---

## 2026-07-26 — Social icons de-hotlinked; brand asset home created

**Type:** `feat` · **Branch:** `feat/socials-and-brand`

### What changed

- Added `inc/socials.php` — `[glm_socials]`, rendering social links from Font Awesome
- Added social styles to `components.css`
- Added `brand/` at the repo root with a README, as the home for logo masters

### Why

The source hotlinked **five social SVGs from another project's staging server**. That is a live outage waiting to happen: when that staging site is pruned or rebuilt, the icons vanish from production. Elementor already bundles Font Awesome, so the replacements cost nothing.

### The X problem

Elementor bundles **Font Awesome 5 Free**. `fa-x-twitter` only exists in Font Awesome 6. The profile links to `x.com`, so falling back to the old bird would be visibly outdated branding.

Resolved with a ~300-byte inline SVG for X and Font Awesome for the other four. No extra library, exact mark.

### Two enqueue gotchas, both real

1. Elementor only enqueues Font Awesome when one of **its own** icon widgets renders. A shortcode using `fab` classes on a page without one would show empty squares.
2. Enqueueing from inside a shortcode runs during `the_content`, **after `wp_head` has printed**. The stylesheet then lands in the footer and icons pop in after paint.

Both fixed by also hooking `wp_enqueue_scripts`. Verified on a real front-end render that the stylesheet appears in `<head>`, not the footer — a distinction the WP-CLI test could not have shown, because `wp_enqueue_scripts` never fires there.

> Worth keeping: the first test said "Font Awesome not registered" and looked like a failure. It was a **false negative** — WP-CLI has no front-end enqueue cycle. Testing in the wrong context produces confident, wrong answers in both directions.

### Logo

`brand/` now exists as the documented home for logo masters, because `wp-content/uploads/` is excluded from git. Masters live in the repo; the Media Library holds a working copy imported with `studio wp media import`.

Also found: the nav and footer logos are pulled from **two different staging servers** and are **different files** (1446 vs 1545 bytes). The same logo has already forked. Both are ~1.5 KB PNGs, almost certainly too low-resolution for modern displays.

### Verification

Rendered on a real page: 5 links, 4 Font Awesome icons, 1 inline SVG, 5 screen-reader labels, Font Awesome in `<head>`, **zero staging references**. Test page deleted afterwards.

---

## 2026-07-26 — Site live: theme activated, 40 torts imported and verified

**Type:** `feat` / `fix` · **Branch:** `fix/taxonomy-template` · **Tag:** `phase-3-live`

### What changed

- Created a Windows directory junction from the Studio site's `wp-content/themes/` to the repo theme
- Installed Hello Elementor 3.4.9, Elementor 4.2.0, ACF 6.8.6
- Activated the child theme — **first execution of any of this code**
- Set permalinks to `/%postname%/`
- Added a WP-CLI command `glm import-torts [--dry-run]`, sharing the admin page's code path
- Imported all 40 torts
- **Fixed:** added `taxonomy-tort_category.php`

### Environment

WordPress 7.0.2, PHP 8.4, SQLite, `http://localhost:8882/`.

### The bug worth recording

`/mass-torts/type/pharma/` rendered the parent theme's generic archive — wrong heading, **zero cards**.

The cause was an assumption I had written into `archive-tort.php`'s docblock **as if it were fact**: that a taxonomy archive falls back to `archive-{post_type}.php`. It does not. WordPress's hierarchy runs `taxonomy-{tax}-{term}.php` → `taxonomy-{tax}.php` → `taxonomy.php` → `archive.php` → `index.php`, and never touches the post-type archive.

Fixed with a delegating `taxonomy-tort_category.php` that requires `archive-tort.php`, so there is still one template to edit (**R4**).

> **The lesson is not about the template hierarchy.** It is that a comment asserting how a framework behaves is worth exactly as much as a comment asserting anything else — nothing, until it runs. This shipped lint-clean and returned HTTP 200 while being completely wrong. The corrected docblock now says what breaks if the delegating file is deleted.

### Verification — measured, not assumed

| Check | Result |
|---|---|
| Torts imported | 40 |
| Category split | pharma 10 · device 11 · toxic 6 · product 4 · abuse 5 · tech 4 |
| Status split | active 28 · emerging 6 · settling 5 · appellate 1 |
| Featured | 5 |
| Empty ACF fields | none |
| `/mass-torts/` | 40 cards, 6 tabs, 6 panels, 40 badges/pills/MDL/settlement |
| Archive H1 | **"40 Active Mass Tort Cases"** — computed |
| 6 taxonomy archives | correct counts, no cross-contamination |
| Single page | H1, pill, status, breadcrumb, both facts, 3 related, disclaimer |
| **Full URL sweep** | **40/40 return HTTP 200, zero failures** |
| PHP errors from our code | none |

The archive H1 is the point of the whole exercise: the source hardcoded "35" in two places and was wrong by five. That number is now `wp_count_posts()` and cannot drift.

### Correction to an earlier note

`learning.md` previously warned that Studio's SQLite meant go-live would be a content migration rather than a database copy. **That was wrong.** `studio export <file>.sql --mode db` produces a MySQL-compatible dump that imports into any MySQL or MariaDB host. Corrected in `learning.md`.

---

## 2026-07-26 — Tort content extracted and importer built

**Type:** `feat` · **Branch:** `feat/tort-importer`

### What changed

- Added `themes/hello-elementor-child/data/torts.json` — all 40 torts, extracted programmatically from the source HTML
- Added `inc/importer.php` — a **Tools → Import Torts** admin page, idempotent, defaulting to dry run
- Wired the importer into `functions.php`, admin-only

### Why

40 torts × 8 fields is **320 values**. Hand-entering them is exactly the grind that introduces the drift this project exists to prevent (**R14** — the correct path must be the easy one). Extracting programmatically also makes the seed content version-controlled and reviewable, so a mistake is a diff rather than an archaeology exercise.

### How the extraction went

Written as a throwaway PHP script using `DOMDocument`/`DOMXPath`, run against `source/glmasstorts.html`. It deduplicates the three tab panels that appear twice in the source, and skips the "coming soon" placeholder cards.

Result: **40 unique torts**, matching the source's own per-category counts exactly (10 pharma, 11 device, 6 toxic, 4 product, 5 abuse, 4 tech). Field completeness is 40/40 on title, description, status label, MDL reference, and settlement estimate.

**One bug worth recording.** The first run set every status to `badge`. The source markup is `class="status-badge status-settling"`, and a naive first-match regex on `status-` returns `badge`. Fixed by collecting all matches and dropping known non-values. Caught only because the output was checked against expectations rather than assumed correct — a reminder that a scraper that runs without error is not the same as a scraper that is right.

### Importer design

Idempotent by a `_glm_import_key` post meta derived from the title, so re-running updates rather than duplicates. Uses `update_field()` when ACF is present so the field-key references are written correctly, and falls back to raw post meta when it is not.

> **Known trade-off:** a re-run overwrites manual edits. Acceptable while the content is still seed data; once real copy is written on top, the JSON becomes the place to edit, or the importer stops being used.

### Verification

All 8 PHP files pass `php -l` on 8.3.32. Seed file parses, 40 records, 30.9 KB. **Not yet run against WordPress.**

---

## 2026-07-26 — Phase 2/3 (partial): child theme scaffold

**Type:** `feat` · **Branch:** `feat/child-theme-scaffold` · **Tag:** `phase-2-theme-scaffold`

### What changed

Built the complete child theme ahead of the WordPress environment, since it is all files and none of it needs WordPress running to be written.

- `style.css` — theme header plus every design token as a CSS custom property, renamed by role
- `functions.php` — enqueues, ACF JSON sync, one-time rewrite flush, `glm_field()` safe accessor, missing-ACF admin notice
- `inc/post-types.php` — `tort`, `location`, `result`
- `inc/taxonomies.php` — `tort_category`, `tort_status`, with 11 terms seeded once
- `inc/parts/tort-card.php` — the card, designed once
- `inc/tort-grid.php` — `[glm_tort_grid]`, `[glm_tort_count]`, `[glm_tort_options]`
- `acf-json/` — three field groups as version-controlled files
- `assets/css/components.css` — component styles, tokens only, no raw hex
- `assets/js/tort-tabs.js` — ARIA tabs with arrow-key support and a no-JS fallback
- `single-tort.php` — one file, forty pages
- `archive-tort.php` — `/mass-torts/` and the category archives
- `themes/hello-elementor-child/README.md` — install steps, shortcode reference, gotchas

### Why

Studio was not yet wired up, but the theme is just files. Building it now meant the environment was never the bottleneck. All of it is in git, ready to activate.

### Verification

All 7 PHP files pass `php -l` on PHP 8.3.32. All 3 ACF JSON files parse. **Nothing has been executed against WordPress yet** — a debugging pass on first activation is expected and planned.

### Decisions made

- **Tort grid renderer:** custom `[glm_tort_grid]` shortcode. Elementor Free has no ACF dynamic tags and 6 of the card's 8 fields are ACF fields, so no free widget can render it. Owning it in the theme also puts the site's most complex component into git rather than `postmeta` — a direct win against R12.
- **URL scheme locked:** `/mass-torts/{slug}/`, categories at `/mass-torts/type/{cat}/`. Deliberately `type` and not `category`, which would read as core's post category.
- **Local environment changed** from LocalWP to **WordPress Studio**, which was already installed. Brings a SQLite caveat — go-live becomes a content migration rather than a database copy. Recorded in `learning.md`.
- **Repo ↔ Studio link:** Windows directory junction, because `mklink /J` needs no admin rights.

### Design note worth keeping

`tort_status` is a **taxonomy and an ACF text field together**. The source had 27 distinct status strings ("Active · Filing Now", "Settling · $1B+ Fund") across only 5 colours. The taxonomy carries the colour; the text field carries the wording. Modelling 27 terms would mean maintaining 27 colours; modelling it as free text alone would lose the colour logic. Worth remembering as a general shape: when a value has *many labels but few behaviours*, split it.

### Open

- Studio site path not yet provided, so the junction is not created
- Header, footer, location and result renderers, and form integration remain

---

## 2026-07-26 — Phase 1: component inventory

**Type:** `docs` · **Branch:** `docs/component-inventory` · **Tag:** `phase-1-inventory`

### What changed

- Added `source/glmasstorts.html` (123 KB, 1,822 lines) — the design reference
- Wrote `docs/component-inventory.md` — 14 components, 3 CPTs with full field lists, 5 source defects, and the tort-grid decision
- Wrote `docs/design-tokens.md` — colour palette, type scale, spacing, breakpoints, assets needing re-hosting
- Wrote `docs/page-map.md` — 10 pages, URL scheme, navigation mapping, component usage matrix
- Updated `README.md` and `docs/README.md` to reflect Phase 1 completion

### Why

The source had to be understood as a **component system** before any WordPress work began. Porting it section by section is the trap that produces an unmaintainable site.

### What the analysis found

The site is **one page containing 14 distinct components and 40 pieces of repeating data** — not twenty pages of markup. Splitting it produces **10 hand-built pages plus 46 generated ones**.

**Five defects in the source**, two of which are this project's founding problem caught in the act:

1. **18 duplicated tort cards.** Three tab panels appear twice with duplicate `id` attributes. Since `showTab()` uses `getElementById()`, the second copy of each renders into the DOM but can never display. 60 `<h4>` elements, only 42 unique. Copy-paste drift, invisible, shipping for months.
2. **The tort count is wrong.** The page says "35+" in two hardcoded places; there are 40. Someone added five and did not update the counter.
3. **"About Us" links to the wrong section.** `id="about"` sits on Divisions; the About section has no id at all.
4. **Malformed CSS comments** (`/* … /`, `/ … */`) silently swallow the rules that follow, so parts of the stylesheet are inert. Rebuild from intent, not from a byte-for-byte port.
5. **Every image is hotlinked from staging servers** — two different ones for the same logo. When either staging site is pruned, the production logo disappears.

Defects 1 and 2 are precisely why **R4** (shortcode-synced templates, never copy-paste) and **R5** (repeating data in the database) exist. Under those rules neither failure is possible: a shortcode template cannot drift from itself, and `wp_count_posts()` cannot be stale.

### Also discovered

The file is a **Claude Artifact export**, not a website export. Lines 1–235 are sandbox runtime — `window.claude`, `postMessage` plumbing, a `fetch` override, and `<body id="artifacts-component-root-html">`. Discarded entirely.

The design tokens are already CSS custom properties, which is good practice — but the **names are lies**. `--gold` is `#506CFB`, an indigo blue. A rebrand changed the values and left the names, and `#c8a84b` (real gold) still appears hardcoded in places, bypassing the variables. Renaming to role-based names (`--color-accent`) during migration, because it is free now and expensive later.

### Decision raised, not yet made

**How to build the tort grid.** Elementor Free has no ACF dynamic tags, and six of the tort card's eight fields are ACF fields, so no free grid widget can render it. Recommended a custom `[glm_tort_grid]` shortcode in the child theme — roughly 120 lines of PHP that put the site's most complex component into version control, which is a direct win against **R12**. Alternatives documented in `docs/component-inventory.md` §5.

---

## 2026-07-26 — Phase 0: repository foundation

**Type:** `chore` · **Branches:** `main`, `chore/repo-foundation` · **Tag:** `phase-0-foundations`

### What changed

- Renamed the default branch from `master` to `main`
- Added `.gitignore`, scoped to what this repo actually tracks: the child theme, docs, source reference, and Elementor exports
- Added `.gitmessage` commit template and wired it up with `git config --local commit.template .gitmessage`
- Added `.gitattributes` to normalise line endings
- Created `CLAUDE.md` — standing instructions so working preferences never need restating
- Created `learning.md` — decisions log, working principles, and the 14-rule build ruleset
- Created `project_history.md` — this file
- Rewrote `README.md` as a real project document
- Scaffolded `docs/`, `source/`, `exports/`, `themes/`

### Why

Version control and an enforced commit format had to exist before any WordPress work, so every later change is traceable, revertable, and carries its rationale. The repository had zero commits at the start of this session.

The documentation set was created up front rather than retrofitted because the project's whole purpose is avoiding an unmaintainable mess — and undocumented conventions are how conventions get abandoned.

### Notes

- Repo currently holds **no WordPress code**. LocalWP is not yet installed. Phase 0 is deliberately environment-independent so that architecture and documentation could be settled first.
- `.gitignore` includes `desktop.ini` because this workspace sits inside OneDrive, which generates one in every synced folder.

---

## 2026-07-25 — Planning session: architecture and ruleset agreed

**Type:** `docs` · No code changes

### What changed

Full architecture settled through a structured decision process. Eight decisions locked, recorded in [learning.md](learning.md) Part 1.

Headlines:

- **Elementor Free** as the builder, on a zero budget
- **Hello Elementor + child theme** as the base
- Repo tracks child theme, docs, source and exports only
- **LocalWP** for development
- Commit messages anonymous; author field unchanged
- No role-based editor restriction

### Why

The brief was to convert one long HTML file into a maintainable multi-page WordPress site that non-technical staff can manage, without recreating a specific known failure: sections with icon, title and description crammed into a single text box and separated by hand-edited markup.

The reframe that shaped everything: **the problem is not which builder, it is whether the correct action is easier than the hacky one.** Nobody builds a mess deliberately — they build one when adding a fourth card properly is harder than typing it into an existing box.

### Two reversals worth remembering

**Bricks → Elementor Free.** Bricks was chosen first for its clean output and global-class design system. It was reversed on discovering it is licence-only with no free tier, once the zero-budget constraint was stated. Worth noting: the constraint pushed the design toward platform-native primitives, which is generally the more durable direction.

**R7 removed.** The original ruleset required editors to have content-only access — technically able to change words, technically unable to change structure. Elementor Free has no Role Manager, and role-based discrimination was ruled out. Rather than pretend the rule still held, it was deleted and its intent rehomed into **R14 — make the correct action the easiest action**, with **R5** (repeating data in custom post types) carrying the practical weight.

### Open at end of session

- Source HTML not yet placed in `source/` — **blocks Phase 1**
- Essential Addons Lite vs Happy Addons free — pick exactly one in Phase 2
- Verify on install: Saved Template shortcodes present, Flexbox Container available
