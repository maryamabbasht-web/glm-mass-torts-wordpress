# Component inventory

Extracted from `source/glmasstorts.html` — 1,822 lines, one page, five sections.

**The headline:** this is **not** a twenty-page site. It is a **single long homepage** containing **14 distinct component types** and **40 pieces of repeating data**. Build the 14 once; let the 40 come out of the database.

---

## Contents

1. [What the source actually is](#1-what-the-source-actually-is)
2. [Defects found in the source](#2-defects-found-in-the-source)
3. [Component inventory](#3-component-inventory)
4. [Repeating data → custom post types](#4-repeating-data--custom-post-types)
5. [The tort grid decision](#5-the-tort-grid-decision)
6. [What must NOT be ported](#6-what-must-not-be-ported)

---

## 1. What the source actually is

> ⚠️ **The file is a Claude Artifact export, not a website export.**

Lines 1–235 are the Artifacts sandbox runtime — `window.claude`, `postMessage` plumbing, a `fetch` override, an `html-to-image` CDN script, and `<body id="artifacts-component-root-html">`. None of it belongs anywhere near WordPress.

The real page starts at **line 237**. Treat lines 1–235 as packaging to be discarded.

### Page structure

| Lines | Element | Notes |
|---|---|---|
| 1–235 | Artifact runtime | **Discard entirely** |
| 237–365 | `<head>` + inline `<style>` | ~120 lines of CSS — the design system source |
| 374–447 | `<nav>` | Desktop header with two mega-dropdowns |
| 451–604 | `.mobile-menu` | **Full duplicate** of the nav's link set |
| 608–683 | `.hero` | Headline, lead, CTAs, form card, stats bar |
| 687–755 | `.about` | Two-column: copy + highlights / verdict list |
| 759–787 | `.divisions` | Three numbered cards |
| 791–1451 | `.mass-torts` | Tabs + 6 categories + 40 tort cards |
| 1453–1556 | `.contact-section` | Case evaluation form |
| 1560–1749 | `<footer>` | Divisions, newsletter, 8 offices, socials, legal |
| 1751–1819 | `<script>` | Tab switching, mobile menu, newsletter popup |

---

## 2. Defects found in the source

These are not nitpicks — each one changes what we build.

### 2.1 Duplicated tort sections — 18 cards of dead markup

Three tab panels appear **twice** with duplicate `id` attributes:

| `id` | First copy | Second copy |
|---|---|---|
| `tab-product` | line 1116 | line 1324 |
| `tab-abuse` | line 1166 | line 1358 |
| `tab-tech` | line 1230 | line 1407 |

Duplicate IDs are invalid HTML, and `showTab()` uses `getElementById()`, which returns **only the first match**. So the second copy of each is markup that renders into the DOM and **can never be displayed**.

Evidence: 60 `<h4>` elements, but only **42 unique** (40 torts + 2 footer headings). Eighteen exact-duplicate tort titles.

> **This is R4's argument made concrete.** Someone copy-pasted three sections, both copies drifted, and the dead ones have been shipping ever since. Nobody noticed because they are invisible.

### 2.2 The tort count is wrong

| Where | Says |
|---|---|
| Hero headline | "35+ Active Mass Tort Cases" |
| `.torts-count` display | `35` |
| Category counts, summed | `10 + 11 + 6 + 4 + 5 + 4` = **40** |
| Actual unique tort cards | **40** |

The number is hardcoded in two places and was last correct when there were 35 torts. Five were added; nobody updated the counter.

> **This is R5's argument made concrete.** As a custom post type the count is `wp_count_posts()` — computed, always right, impossible to drift. A hardcoded marketing number on a legal site is also a small compliance risk.

### 2.3 The "About Us" nav link goes to the wrong section

```html
<section class="section about">              <!-- line 687 — NO id -->
<section class="section divisions" id="about"> <!-- line 759 — has id="about" -->
```

Both the desktop nav and mobile menu link to `#about`. Both scroll to **Divisions**, skipping the About section entirely. Fix during Phase 5.

### 2.4 Malformed CSS comments silently kill rules

The stylesheet contains several broken comment delimiters — `/* Featured tort card /`, `/ Locations */`, `/* ─── FOOTER ─── /` — which swallow the CSS that follows until the next valid `*/`. There are also invalid declarations: `.btn-outline{color;}`, `.btn-primary{color;}`, `.div-num { color; }`.

Consequence: **some styling in the source is not actually applying.** When rebuilding, work from intended design, not from a byte-for-byte port of a stylesheet that is partly inert.

### 2.5 Every image is hotlinked from someone else's staging server

Covered in [design-tokens.md](design-tokens.md#external-assets-that-must-be-re-hosted). Two different staging domains serve the same logo. Must be re-hosted in Phase 2.

---

## 3. Component inventory

**14 distinct components.** Column key — **Build as:** `HFE` = Header Footer Elementor template · `Template` = Elementor Saved Template inserted by shortcode (**R4**) · `Theme` = PHP in the child theme.

### Global chrome

| # | Component | Build as | Contents | Notes |
|---|---|---|---|---|
| G1 | **Site Header** | HFE | Logo, 5 nav items, 2 mega-dropdowns, phone CTA, hamburger | Mega-dropdown holds 24 tort links across 4 columns |
| G2 | **Site Footer** | HFE | Divisions list + descriptions, newsletter form, 8 office locations, logo, 4 legal links, 5 socials, copyright, attorney-advertising disclaimer | Locations come from a CPT (see C2) |

> **R8 applies immediately to G1.** The source ships a desktop nav *and* a separately maintained mobile menu with its own copy of all 24 tort links. That is the duplicate-markup pattern R8 bans, and it is why the two menus already list different torts. In WordPress this is **one** WP menu rendered responsively.

### Homepage sections

| # | Component | Build as | Fields / contents | Repeats |
|---|---|---|---|---|
| S1 | **Hero** | Template | Eyebrow, `h1` (with `<em>` accent), lead paragraph, 2 CTA buttons | 1× |
| S2 | **Hero Case Form** | Template | Heading, subtext, 4 fields (name, phone, email, message), submit, disclaimer | 1× |
| S3 | **Stats Bar** | Template | 5 × (number, label) | 1× |
| S4 | **About** | Template | Eyebrow, title, 3 paragraphs, 4 highlight boxes, verdict list, disclaimer | 1× |
| S5 | **Divisions Grid** | Template | 3 × (number, icon, title, description, link) | 1× |
| S6 | **Mass Torts Browser** | **Theme** | 6 tabs → 6 category panels → 40 tort cards | **See §5** |
| S7 | **Contact Section** | Template | Eyebrow, title, heading, subtext, 6 fields incl. 2 selects, submit, disclaimer | 1× |
| S8 | **Coming Soon Card** | Template | Single button-style placeholder card | 4× inside S6 |

### Sub-components (used inside the above)

| # | Component | Fields | Count | Where |
|---|---|---|---|---|
| P1 | **Highlight Box** | Icon (emoji), label, sub-label | 4 | S4 |
| P2 | **Verdict Item** | Amount, case name, description | 4 | S4 |
| P3 | **Stat Item** | Number, label | 5 | S3 |
| P4 | **Division Card** | Number, icon, title, description, link | 3 | S5 |
| P5 | **Tort Card** | 8 fields — see §4 | 40 | S6 |
| P6 | **Featured Tort Card** | Tort Card + stat number + stat label | 7 | S6 |
| P7 | **Office Location** | City, address, phone | 8 | G2 |

---

## 4. Repeating data → custom post types

**R5** says repeating data lives in the database. But not everything that repeats deserves a CPT — three items that never change do not need an admin screen.

### The threshold applied

Promote to a CPT when **any** of these is true:

1. There are **10 or more** items, **or**
2. Editors will **add or remove** them during normal operations, **or**
3. The same item appears in **more than one place** on the site

| Data | Count | 10+? | Editors change? | Multi-place? | Verdict |
|---|---|---|---|---|---|
| Torts | 40 | ✅ | ✅ | ✅ nav, tabs, form dropdown | **CPT** |
| Office locations | 8 | ❌ | ✅ | ✅ footer + contact | **CPT** |
| Verdicts / results | 4 | ❌ | ✅ marketing numbers | ❌ | **CPT** |
| Divisions | 3 | ❌ | ❌ | ✅ nav, section, footer | Template |
| Highlight boxes | 4 | ❌ | ❌ | ❌ | Template |
| Stat items | 5 | ❌ | rarely | ❌ | Template |

> **Why Divisions stays a Template despite appearing in three places:** it is three fixed corporate entities that change when the firm restructures, i.e. roughly never. A CPT would add an admin screen nobody opens. Cross-referencing it in three Saved Templates is cheaper than the abstraction.
>
> **Why Verdicts becomes a CPT despite only four items:** these are marketing figures ("$11B+", "198K+ as of Q1 2026") that go stale quarterly. They are exactly the thing someone updates in a hurry — and R14 says the correct path must be the easy one.

### C1 — `tort` (40 items) — the core of the site

| Field | Type | Example |
|---|---|---|
| `post_title` | native | `Johnson & Johnson Talcum Powder Litigation` |
| `post_content` | native | Full description paragraph |
| `tort_category` | **taxonomy** | Pharmaceutical Drugs |
| `tort_status` | **taxonomy** | Settling |
| `status_label` | ACF text | `Settling` · `Active · Filing Now` · `Active · Bellwether 2026` |
| `pill_suffix` | ACF text | `· #1 Largest MDL` (optional) |
| `mdl_reference` | ACF text | `MDL #2738 · Largest Active Mass Tort in the U.S.` |
| `settlement_estimate` | ACF text | `Est. $300K–$5M+ per case` |
| `is_featured` | ACF true/false | `true` for 7 of 40 |
| `featured_stat_number` | ACF text | `67,100+` |
| `featured_stat_label` | ACF text | `Pending cases as of Q1 2026` |

**Taxonomy: `tort_category`** — 6 terms, each with an emoji and pill colour

| Term | Slug | Count | Emoji |
|---|---|---|---|
| Pharmaceutical Drugs | `pharma` | 10 | 💊 |
| Medical Devices | `device` | 11 | 🔬 |
| Toxic Exposure | `toxic` | 6 | ☣️ |
| Consumer Products | `product` | 4 | 🏭 |
| Sexual Assault & Abuse | `abuse` | 5 | 🔒 |
| Technology & Emerging | `tech` | 4 | 📱 |

**Taxonomy: `tort_status`** — 5 terms, each driving a badge colour

`active` · `settling` · `emerging` · `appellate` · `not-active`

> **Why status is a taxonomy *and* a text field.** The source contains **27 distinct status strings** ("Active · Filing Now", "Active · Bellwether 2026", "Settling · $1B+ Fund"…) but only **5 colours**. The taxonomy drives the colour; the text field holds the specific wording. Modelling 27 taxonomy terms would give you 27 colours to maintain; modelling it as text alone loses the colour logic entirely.

> **Gotcha — decide slugs now (R5).** `tort_category` term slugs become URLs. Changing `pharma` to `pharmaceutical` later means redirects. Settle these before creating content.

### C2 — `location` (8 items)

| Field | Type | Example |
|---|---|---|
| `post_title` | native | `Boca Raton` |
| `state` | ACF select | Florida / Massachusetts / New Jersey / Michigan |
| `address` | ACF textarea | `7171 North Federal Highway, Boca Raton, FL 33487` |
| `phone` | ACF text | `(561) 995-1966` |
| `phone_secondary` | ACF text | `(239) 514-5048` — Naples has two |

Current: Florida ×4 (Boca Raton, Naples, Estero, Panama City), Massachusetts ×2 (Rehoboth, Boston), New Jersey ×1 (Ridgewood), Michigan ×1 (Southfield).

### C3 — `result` (4 items)

| Field | Type | Example |
|---|---|---|
| `post_title` | native | `Roundup / Glyphosate MDL` |
| `amount` | ACF text | `$11B+` |
| `description` | ACF text | `Total settlements secured industry-wide for cancer victims` |

---

## 5. The tort grid decision

**This is the one architectural choice left in Phase 1, and it needs your call.**

The Mass Torts Browser (S6) is 40 cards × 8 fields, in 6 tabbed categories, with two card variants and five status colours. It is more than half the page.

**The constraint (learning.md R5):** Elementor Free has **no ACF dynamic tags**. Free post-grid widgets render native fields — title, excerpt, featured image, permalink — and nothing else. Six of our eight tort fields are ACF fields. No free widget can display this card.

### Option A — Custom shortcode in the child theme *(recommended)*

A `[glm_tort_grid]` shortcode, ~120 lines of PHP in `themes/hello-elementor-child/inc/`, dropped into the page with Elementor's Shortcode widget.

- ✅ **Solves R12 for the most complex component** — the whole tort browser becomes version-controlled PHP instead of database rows. The one piece of this site you most need to be reproducible actually is.
- ✅ Exact semantic markup with global classes (**R2, R3**)
- ✅ No plugin dependency, no addon-pack bloat
- ✅ Editors add torts through the CPT form and never touch it (**R14**)
- ⚠️ **Gotcha:** it is PHP — a syntax error white-screens the site. Edit through the repo and test locally, never through the WordPress file editor.
- ⚠️ **Gotcha:** not visually editable in Elementor. That is the point, but it must be a conscious trade.

### Option B — Free addon widget + simplified cards

Use Essential Addons Lite / Happy Addons post grid, and cut the card down to what native fields can express.

- ✅ Visual, no PHP
- ❌ **Loses the MDL reference, settlement estimate, status badge, and featured-card variant** — that is the card's entire information value on a legal site where "MDL #2738" is what a visitor is searching for
- ❌ Adds a plugin dependency for the site's most important component

### Option C — Build all 40 cards by hand in Elementor

- ❌ 320 hand-entered data points, drifting from day one
- ❌ Adding a tort means editing the page — the exact failure this project exists to prevent
- ❌ Directly violates **R3**, **R5**, and **R14**

**Recommendation: Option A.** The general principle: *where a page builder adds nothing and costs a lot, own the component in the theme.* A builder earns its keep on layout-driven marketing sections. It has nothing to offer a data table with eight fields and 40 rows.

---

## 6. What must NOT be ported

| Item | Lines | Why |
|---|---|---|
| Artifact runtime shim | 1–235 | Sandbox plumbing — `window.claude`, `postMessage`, `fetch` override |
| `id="artifacts-component-root-html"` | 368 | Artifact container hook |
| `html-to-image` CDN script | 2 | Screenshot tooling for the sandbox |
| Duplicate mobile menu | 451–604 | **R8** — one WP menu, rendered responsively |
| Duplicate tab panels | 1324–1451 | Dead markup, invalid duplicate IDs |
| Font Awesome 4.7 CDN | 245 | An entire icon library for one phone icon |
| `showTab()` / `toggleMenu()` JS | 1796–1810 | Replaced by theme/Elementor behaviour |
| Hotlinked staging images | throughout | Re-host to Media Library |
| Inline `style="..."` attributes | throughout | **R9** — belongs in the child theme stylesheet |
| Malformed CSS comments | throughout | Rebuild from intent, not from partly-inert CSS |

### Forms

Three forms post to `/wp-admin/admin-post.php` with actions `hero_case_form`, `contact_case_form`, `newsletter_signup`. Elementor Free has no Form widget, so these become **Fluent Forms** or **Contact Form 7** (Phase 5).

> ⚠️ **Gotcha — this is a law firm intake form.** It collects names, phone numbers, emails and injury descriptions, which is sensitive personal data. Requirements: HTTPS, a spam guard (honeypot or hCaptcha, not reCAPTCHA v3 alone), a defined retention policy, and a decision on whether submissions are stored in the database at all or forwarded straight to email/CRM. Storing case descriptions in `wp_posts` by default is the path of least resistance and the wrong default. Flag for Phase 5.

---

## Summary

| Metric | Value |
|---|---|
| Distinct components to build | **14** |
| Of which need a CPT + query | **3** |
| Repeating data items | **52** (40 torts, 8 locations, 4 results) |
| Pages in the source | **1** |
| Lines to discard outright | **~235** |
| Defects found in source | **5** |

The site is one page of 14 components, not twenty pages of markup. Build the 14 once.
