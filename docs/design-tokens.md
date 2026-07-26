# Design tokens

Extracted from `source/glmasstorts.html` (lines 247–261 and throughout the stylesheet). This is the input to **R1 — tokens before pixels**.

---

## ⚠️ The token names in the source are wrong

The source defines its palette as CSS custom properties, which is good practice. But the names describe a palette that **no longer exists**:

```css
--gold:       #506CFB;   /* this is INDIGO BLUE, not gold */
--gold-light: #DBE1FB;   /* pale blue */
--gold-pale:  #dfe6fe;   /* pale blue */
--navy-2:     #201e6b;   /* indigo, used as the main dark surface */
```

Somebody rebranded from a navy-and-gold palette to a navy-and-indigo one and updated the *values* without updating the *names*. The stylesheet is now full of lines like `.div-link { color: #01c53db5; }` sitting next to `--gold` variables that render blue.

There is also leftover evidence of the old palette hardcoded in place — `#c8a84b` (a real gold) still appears in the footer and phone-block rules, bypassing the variables entirely.

### The principle worth taking from this

> **Name tokens by role, not by appearance.**

`--gold` breaks the moment the brand stops being gold. `--color-accent` never does. Appearance-based names create a choice between a misleading name and a rename that touches every file — which is why nobody does the rename, and the lie persists.

We rename now, during migration, because it is free now and expensive later.

---

## Colour tokens

### Proposed Elementor Global Colors

Elementor's Site Settings → Global Colors is where these live (**R1**). Elementor gives four primary slots plus custom ones.

| New name | Value | Old name | Where it is used |
|---|---|---|---|
| `Primary` | `#201e6b` | `--navy-2` | Main dark surface — nav, hero, divisions, footer |
| `Secondary` | `#506CFB` | `--gold` | Accent — rules, tags, borders, arrows, active states |
| `Text` | `#0b1929` | `--navy` / `--text` | Body text, headings on light backgrounds |
| `Accent` | `#01C53D` | `--call` | **Call-to-action green** — phone buttons, submit buttons |

| Custom token | Value | Old name | Use |
|---|---|---|---|
| `Accent Light` | `#DBE1FB` | `--gold-light` | Nav links, hero `<em>`, light-on-dark text |
| `Accent Pale` | `#dfe6fe` | `--gold-pale` | Tort card background, tab active state, feat-stat panel |
| `Surface` | `#eff7f8` | `--cream` | Page background, mass-torts section |
| `Surface Deep` | `#0b1929` | `--navy` | Contact section, category headers |
| `Text Muted` | `#5a6a7e` | `--muted` | Secondary copy, labels, descriptions |
| `Danger` | `#b91c1c` | `--red` | Declared but **unused** — drop unless needed |
| `Navy 3` | `#1a3560` | `--navy-3` | Declared but **unused** — drop |

> **Gotcha:** `--border: #506CFB` is the same value as `--gold`. It is a duplicate token, not a distinct one. Collapse it into `Secondary` rather than carrying two names for one colour.

### Status badge colours

These drive the tort card status pills. Five states.

| Status | Background | Text | Dot |
|---|---|---|---|
| Active | `#dcfce7` | `#15803d` | `#16a34a` |
| Settling | `#fef3c7` | `#92400e` | `#d97706` |
| Emerging | `#e0f2fe` | `#0369a1` | `#0284c7` |
| Appellate | `#f3e8ff` | `#7e22ce` | `#9333ea` |
| Not active | `#808080a6` | `black` | `gray` |

### Category pill colours

| Category | Background | Text |
|---|---|---|
| Pharmaceutical | `#e8f0fe` | `#1a56c7` |
| Medical Device | `#e8f8ee` | `#166534` |
| Toxic | `#dfe6fe` *(Accent Pale)* | `#201e6b` *(Primary)* |
| Abuse | `#fde8e8` | `#9b1c1c` |
| Tech | `#f0e8ff` | `#6b21a8` |
| Product | `#fef3c7` | `#92400e` |
| Emerging | `#f0fdf4` | `#166534` |

> **Gotcha:** that is **12 status/pill colour pairs — 24 values**. Elementor's Global Colors panel becomes unusable at that size, and these are not brand colours, they are component states. Keep them as CSS custom properties in the child theme stylesheet (**R9**), not as Elementor globals. Only the brand palette goes in Global Colors.

---

## Typography

Two Google Fonts, loaded as one request.

| Role | Family | Weights used |
|---|---|---|
| Display / headings | **Cormorant Garamond** (serif) | 400, 600, 700 + italics |
| Body / UI | **Outfit** (sans-serif) | 300, 400, 500, 600 |

### Proposed Elementor Global Fonts

| Global font | Family | Size | Weight | Line height | Used by |
|---|---|---|---|---|---|
| `Primary` (H1) | Cormorant Garamond | `clamp(42px, 5.5vw, 76px)` | 700 | 1.07 | Hero headline |
| `Secondary` (H2) | Cormorant Garamond | `clamp(30px, 4vw, 50px)` | 700 | 1.15 | Section titles |
| `Text` (body) | Outfit | 16px | 300–400 | 1.65 | Body copy |
| `Accent` (eyebrow) | Outfit | 10–11px | 600 | — | Section tags, uppercase, `letter-spacing: 3px` |

### Full type scale observed

| Element | Family | Size | Weight |
|---|---|---|---|
| Hero `h1` | Cormorant | `clamp(42px, 5.5vw, 76px)` | 700 |
| Section title `h2` | Cormorant | `clamp(30px, 4vw, 50px)` | 700 |
| Contact `h3` | Cormorant | 28px | 700 |
| Division title | Cormorant | 24px | 700 |
| Hero card `h3` | Cormorant | 22px | 700 |
| Category title | Cormorant | 22px | 700 |
| Tort card `h4` | Cormorant | 19px | 700 |
| Torts count | Cormorant | 80px | 700 |
| Feat-stat number | Cormorant | 42px | 700 |
| Stat number | Cormorant | 34px | 700 |
| Phone (big) | Cormorant | 34px | 700 |
| Verdict amount | Cormorant | 32px | 700 |
| Hero lead | Outfit | 17px | 300 |
| About body | Outfit | 16px | 300 |
| Nav link | Outfit | 14px | 500 |
| Button | Outfit | 14px | 600 |
| Tort card body | Outfit | 13px | 300 |
| Form label | Outfit | 11px | 600 |
| Status badge | Outfit | 9px | 700 |

> **Observation:** the scale is *nearly* consistent but has drifted — 9, 10, 11, 12, 13, 14, 16, 17, 19, 22, 24, 28, 32, 34, 42, 50, 76. Several of those exist because someone nudged a value rather than reaching for an existing step. Worth collapsing to a defined scale during Phase 2 rather than porting all seventeen sizes.

---

## Layout and spacing

| Token | Value |
|---|---|
| Container max-width | `1200px` |
| Section padding (desktop) | `90px 48px` |
| Section padding (mobile) | `60px 24px` |
| Hero padding | `100px 48px 50px` |
| Nav padding | `0 30px` (desktop), `0 20px` (mobile) |
| Card padding | `30px 28px` (tort), `48px 36px` (division) |
| Grid gap | `2px`–`3px` (card grids), `80px` (about/hero) |
| Border radius | `2px`–`5px` (small), `10px`–`20px` (stats bar, badges) |
| Tort grid | `repeat(auto-fill, minmax(320px, 1fr))` |

### Breakpoints

| Breakpoint | Purpose |
|---|---|
| `max-width: 900px` | **Primary** — nav collapses to hamburger, all grids go single-column |
| `max-width: 768px` | Minor — dropdown positioning only |
| `900px–1280px` | Tablet — footer spacing only |

> **Gotcha:** Elementor's default breakpoints are 767px (mobile) and 1024px (tablet), which **do not match** the source's 900px. Set these explicitly in Site Settings → Layout → Breakpoints during Phase 2, or the layout will break in the 768–900px band that the original handled deliberately.

---

## Icons

| Source | Usage |
|---|---|
| **Font Awesome 4.7.0** (CDN) | Only `fa fa-phone` — a single icon |
| **Emoji** | 💊 🔬 ☣️ 🏭 🔒 📱 ⚖️ 🏛️ 🔍 📋 🏥 — tabs, highlights, divisions |
| **SVG** (hotlinked) | 5 footer social icons |

> **Gotcha:** the page loads the **entire Font Awesome 4.7 stylesheet for one phone icon**. Drop it — Elementor ships Font Awesome 5 built in, and the phone icon is available natively. This removes a render-blocking external stylesheet for free.
>
> **Gotcha on emoji-as-icons:** they render differently on Windows, macOS, Android and iOS, and they are read aloud by screen readers ("biohazard sign"). They are fine as decorative accents but should carry `aria-hidden="true"`. Consider whether the category icons should become real SVGs in Phase 4.

---

## External assets that must be re-hosted

Every image is hotlinked from a **staging server** — two different ones, for the same logo:

| Asset | Current source |
|---|---|
| Logo (nav + mobile) | `revenuecyclstg.wpengine.com/.../GL_LOGOMonogram-1.png` |
| Logo (footer) | `masstortsstg.wpenginepowered.com/.../GL_LOGOMonogram-1.png` |
| 5 social icons | `revenuecyclstg.wpengine.com/.../footer-icons-{1..5}.svg` |

> **Gotcha — this is a live risk, not housekeeping.** These point at staging environments belonging to *other* projects. When either staging site is cleaned up, rebuilt, or has its uploads pruned, the logo disappears from production. All of these must be uploaded to this site's own Media Library in Phase 2 and referenced locally.

---

## Fonts loading

```html
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
```

`display=swap` is already set, which is correct. Consider self-hosting in Phase 2 — it removes a third-party connection, improves Largest Contentful Paint, and sidesteps the GDPR question around Google Fonts serving from an EU-external origin.
