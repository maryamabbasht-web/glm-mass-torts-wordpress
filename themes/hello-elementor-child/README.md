# GLM Mass Torts — child theme

Child of **Hello Elementor**. Holds design tokens, custom post types, and the tort grid renderer.

> **Status: written but never executed.** This was built ahead of the WordPress environment. Expect a debugging pass on first activation — that is planned, not a surprise.

---

## Install

1. Install and activate **Hello Elementor** (the parent) in WordPress
2. Junction this directory into the site's `wp-content/themes/`
3. Activate **GLM Mass Torts**
4. Install **Advanced Custom Fields** (free) — an admin notice appears if it is missing
5. Visit **Settings → Permalinks** and click Save if any tort URL 404s

On first load the theme seeds 6 category terms and 5 status terms, then flushes rewrite rules once.

---

## Structure

```
style.css              Theme header + ALL design tokens as CSS vars
functions.php          Enqueues, ACF JSON sync, includes, safe helpers
inc/
  post-types.php       tort · location · result
  taxonomies.php       tort_category · tort_status + seeded terms
  tort-grid.php        [glm_tort_grid] and two helper shortcodes
  importer.php         Tools → Import Torts (admin only)
  parts/
    tort-card.php      The "loop item" — the card, designed once
acf-json/              Field groups as version-controlled files
data/
  torts.json           40 torts extracted from the source HTML
assets/
  css/components.css   Component styles, tokens only
  js/tort-tabs.js      Tab behaviour, progressive enhancement
single-tort.php        ONE file → 40 tort pages
archive-tort.php       /mass-torts/ and /mass-torts/type/{slug}/
```

---

## One styling system

Elementor ships its own design system and **it cannot be removed** — verified three ways in `project_history.md`. It always emits `.elementor-widget-X .elementor-Y` at specificity **(0,2,0)** into per-section CSS printed in the `<body>`, after this stylesheet loads in the `<head>`. Equal-specificity rules of ours therefore lost on document order alone, silently.

So it is **neutralised** by one reset block at the top of `components.css`:

```css
html .elementor-widget-heading .elementor-heading-title,
html .elementor-widget-text-editor,
html .elementor-widget-button .elementor-button {
  font: inherit;
  letter-spacing: inherit;
  text-transform: inherit;
  color: inherit;
}
```

Elementor's system still exists but has **no effect**. GLM tokens are the single source of truth.

### What this means when you write CSS

**Style the GLM wrapper. Let Elementor's element inherit.**

```css
.glm-hero__title { font-size: var(--glm-h1); }      /* ✓ */
.glm-hero__title .elementor-heading-title { … }     /* ✗ unnecessary */
```

| Property type | Where it goes |
|---|---|
| `font-*`, `color`, `letter-spacing`, `text-transform`, `line-height` | GLM wrapper class — plain selector, inherits |
| `background`, `border`, `padding`, `display` | Elementor's element — **keep the `html` prefix** |

> **Do not remove the `html` prefixes.** They add one element to the specificity, reaching (0,2,1), which beats Elementor's (0,2,0) regardless of load order. Not a typo.
>
> **Do not reach for `!important`.** It escalates — the only thing that overrides one is another one, including your own later rules and anything set in Elementor's panel. Specificity composes; `!important` doesn't.

`wp glm audit` fails if the reset block goes missing, if a selector reaches into Elementor markup unprefixed, or if `!important` climbs above four uses.

---

## Changing the styling of a section

**Styling lives in CSS, not in Elementor's style panel.** Three layers — pick by how far the change should reach.

### Decide which layer

| If the change should… | Edit | Example |
|---|---|---|
| Apply **everywhere** | `style.css` → `:root` | Brand colour, type scale, spacing, breakpoints |
| Apply to **one component** | `assets/css/components.css` | Hero padding, card border, footer column widths |
| Apply to **one element, once** | Elementor's style panel | Almost never — see below |

### The workflow

1. **Find the class.** Inspect the element in devtools. Everything of ours is prefixed `glm-` — `.glm-hero__title`, `.glm-tort-card__settlement`, `.glm-footer__legal`.
2. **Edit the file** in this repo. The junction means WordPress sees it immediately.
3. **Save and hard-refresh** (`Ctrl + F5`).

No build step. No `wp glm` command. No Elementor cache clear — that is only for *structural* changes, not CSS.

> The stylesheet version is `filemtime()`, so saving the file is the cache bust. With a static version string the browser keeps the old copy and you end up debugging CSS that was never loaded.

### Worked example — make the hero less tall

```css
/* assets/css/components.css */
.glm-hero {
  padding: 4rem var(--glm-section-x) 2.5rem;   /* was 6.25rem / 3.125rem */
}
```

Save, refresh. Done.

### Worked example — change the accent colour site-wide

```css
/* style.css */
:root {
  --glm-accent: #3B5BDB;   /* was #506CFB */
}
```

That updates every rule, tag, border, arrow and active state at once, because nothing hardcodes the hex (**R1**).

```bash
# Elementor's own Global Colors are generated from PHP, so update them too:
studio wp glm apply-kit
```

> **Two places, on purpose.** `style.css` serves the theme's own components; the kit serves anything built in Elementor's UI. `inc/elementor-kit.php` holds the same values — change both, or run `apply-kit` after editing the file.

### Adding a new element in Elementor

Give it a class rather than styling it in the panel:

**Advanced → CSS Classes → `glm-your-thing`**

Then style `.glm-your-thing` in `components.css`. Your CSS stays in git; panel styling would not.

### Why not just use the style panel?

You can, and nothing breaks. But:

- It lands in **`postmeta`**, so git never sees it (**R12**) — invisible in diffs and code review
- It is per-element, so the same tweak gets repeated and drifts (**R1**)
- Elementor 4.x does not expose the classic style controls on these widgets anyway

**Reasonable exceptions:** a genuine one-off on a single page, or trying something quickly before committing it to CSS. If you find yourself applying the same panel tweak twice, that is the signal to move it into `components.css`.

### Changing structure, not just styling

Different question, and it has a fork:

| You want | Do this |
|---|---|
| A quick change to one section | Edit it in Elementor. `build-sections` will **refuse to overwrite it** afterwards |
| A change that stays reproducible from git | Edit `inc/elementor-sections.php`, then `wp glm build-sections --force` |

> **The tension is real:** once you edit a generated section in Elementor, the PHP generator no longer describes what is on the site. The guard protects your work but does not reconcile the two. For anything you would want back after a rebuild, change the generator.

Check your work at any point:

```bash
studio wp glm audit
```

---

## Elementor design tokens are generated, not clicked

```bash
studio wp glm apply-kit
```

Elementor's Global Colors and Fonts live in `postmeta` on the kit post, so git captures none of them (**R12**). Hand-clicking them into the UI means the design system exists only in a database.

So `inc/elementor-kit.php` is the **source of truth**, and Elementor's kit is a **generated artifact**. Tokens are defined once, in git, and pushed into Elementor by command.

> **This solves R12 for the kit outright** — there is nothing to export, because the file regenerates it. Saved Templates in Phase 4 still need exporting.

Applied: 9 colours, 7 typography presets, breakpoints, site logo.

> **Gotcha:** re-running overwrites anything edited in Elementor's UI. That is the intended direction — the file wins, not the database.
>
> **Gotcha (found the hard way):** saving `viewport_tablet` stores the value but Elementor's compiled CSS keeps the **old** breakpoint. After the first run the kit reported `tablet=900` while the CSS still emitted `@media(max-width:1024px)`. Fixed by refreshing the breakpoints manager and clearing the file cache before regenerating. Verified: the CSS now emits 900 and 767.

### Why the breakpoint matters

The source breaks at **900px**; Elementor defaults to **1024px**. Left alone, everything in the 900–1024 band renders with tablet styles the design never intended (**R8**).

### Duplicate colour, on purpose

`Surface Deep` is the same hex as `Text` (`#0B1929`). One value, two roles — body copy and dark section backgrounds. De-duplicating would leave an editor picking a background from a swatch labelled "Text". A clear role name beats a clever de-duplication (**R14**).

---

## Importing the 40 torts

**Tools → Import Torts.** Seeds the CPT from `data/torts.json`, which was extracted from `source/glmasstorts.html` rather than transcribed — 40 torts × 8 fields is 320 values, and hand-entry is precisely the grind that introduces drift (**R14**).

The page defaults to a **dry run**. Untick it to write.

**Safe to re-run.** Torts are matched on a `_glm_import_key` meta value derived from the title, so a second run updates rather than duplicates.

> **Gotcha:** re-running **overwrites** manual edits to title, content, excerpt, and all ACF fields. Once real content has been written on top of the seed, edit `data/torts.json` instead of the admin — or stop re-running the importer.

### What the extraction found

| | |
|---|---|
| Unique torts | 40 — matching the source's own category counts exactly |
| Duplicate panels skipped | 3 (`tab-product`, `tab-abuse`, `tab-tech`) |
| Featured | 5 |
| Statuses | 28 active · 6 emerging · 5 settling · 1 appellate |
| Field completeness | 40/40 on title, description, status, MDL, settlement |

The source claimed "35+". There are 40.

---

## Shortcodes

| Shortcode | Renders |
|---|---|
| `[glm_tort_grid]` | Full tabbed browser, all categories |
| `[glm_tort_grid tabs="no" featured="yes" limit="6"]` | Homepage preview — featured torts only |
| `[glm_tort_grid category="pharma" tabs="no"]` | One category, flat grid |
| `[glm_tort_count]` | Live published tort count |
| `[glm_tort_options]` | `<option>` tags for the contact form's case-type field |
| `[glm_socials]` | Social links using Font Awesome — replaces 5 hotlinked staging SVGs |
| `[glm_section slug="hero"]` | Insert an Elementor Saved Template by slug (**R4**) |

### Section templates

```bash
studio wp glm build-sections          # create if missing
studio wp glm build-sections --force  # overwrite (discards UI edits)
```

Generated by `inc/elementor-sections.php`. The generator establishes the intended structure once, correctly; remaining sections get built in the Elementor UI by copying that pattern.

**Structure and copy live in Elementor. Styling lives in `components.css`.** Elementor 4.x does not expose the classic style controls on these widgets, and anything set through its style panel lands in `postmeta` where git cannot see it (**R12**). So every element carries only a CSS class, and the theme owns the appearance using design tokens.

The trade-off: an editor cannot restyle these from the panel. Given the founding brief, that is a feature.

> ### ⚠️ `[elementor-template]` does not exist in Elementor Free 4.x
>
> R4 originally specified `[elementor-template id="123"]` — that is Elementor 3.x. Verified on 4.2.0: **not registered**. The literal text renders, and `wptexturize` curls the quotes into `id=&#8221;50&#8243;` first, because it only skips registered shortcodes.
>
> `[glm_section slug="..."]` replaces it and is better: slug-addressed so rebuilding a template does not break every page, registered so quotes survive, in git, and it shows a visible error to logged-in editors instead of failing silently.

> **Gotcha — the asymmetry that fails silently:** widgets take `_css_classes`, containers take `css_classes` (**no leading underscore**). Wrong key and the element still renders, just without your class. Nothing errors; the styling simply never applies.
>
> **Gotcha:** Elementor caches rendered output. After changing a template you must clear the file cache or your change looks like it did not work. `glm build-sections` handles this.

### Social icons and the X problem

Elementor bundles **Font Awesome 5 Free**, which covers `fa-facebook-f`, `fa-linkedin-in`, `fa-instagram` and `fa-youtube` at no extra cost.

It does **not** contain `fa-x-twitter` — that arrived in Font Awesome 6. Since the profile links to `x.com`, the shortcode renders X as a ~300-byte inline SVG rather than falling back to the outdated bird. To use the bird instead, set `'icon' => 'fa-twitter'` on that entry.

URLs live in `glm_social_profiles()` and are filterable:

```php
add_filter( 'glm_social_profiles', function ( $p ) {
    $p['facebook']['url'] = 'https://facebook.com/newhandle';
    return $p;
} );
```

> **Gotcha handled:** Elementor only enqueues Font Awesome when one of *its* icon widgets renders. A shortcode using `fab` classes on a page without one would show empty squares. Worse, enqueueing from inside a shortcode runs during `the_content` — after `wp_head` has printed — so the stylesheet lands in the footer and icons pop in after paint. The enqueue therefore also runs on `wp_enqueue_scripts`. Verified loading in `<head>`.
>
> **Open for Phase 5:** once the header carries an Elementor phone icon, check Font Awesome is not being loaded twice.

### Attributes for `glm_tort_grid`

| Attribute | Default | Purpose |
|---|---|---|
| `tabs` | `yes` | Show the category tab bar |
| `category` | — | Restrict to one `tort_category` slug |
| `featured` | `no` | Only torts flagged `is_featured` |
| `limit` | `-1` | Maximum results |
| `heading` | `yes` | Show per-category header bars |

---

## Why this is a shortcode and not an Elementor widget

Elementor Free has **no ACF dynamic tags**, and six of the tort card's eight fields are ACF fields. No free grid widget can render this card — it would lose the MDL reference, settlement estimate, status badge, category pill, and the featured variant.

Owning it here also means the site's most complex component lives in **git rather than `postmeta`** (**R12**). It is the one piece that most needs to be reproducible, and now it is.

This is the free-tier equivalent of an Elementor Pro Loop Grid:

| Elementor Pro | Here |
|---|---|
| Loop Item template | `inc/parts/tort-card.php` |
| Loop Grid widget | `[glm_tort_grid]` |
| Dynamic tag → ACF | `glm_field()` in the partial |

Design the card once; the loop repeats it. Same principle, different renderer.

---

## Locked decisions

| Decision | Value | Consequence of changing |
|---|---|---|
| CPT slug | `mass-torts` | 46 redirects |
| Category base | `mass-torts/type` | 6 redirects |
| Category slugs | `pharma` `device` `toxic` `product` `abuse` `tech` | Redirects + CSS class renames |
| Status slugs | `active` `settling` `emerging` `appellate` `inactive` | CSS class renames |

Status slugs map directly to CSS classes (`.glm-status--active`). Renaming a term label is safe; renaming its **slug** is not.

---

## Design token naming

Tokens are named by **role**, not appearance — `--glm-accent`, not `--gold`.

The source did the opposite, and it aged badly: `--gold` held `#506CFB`, an indigo blue, because a rebrand changed the values and left the names. Real gold `#c8a84b` was still hardcoded in the footer, bypassing the variables entirely.

Appearance-based names force a choice between a misleading name and a rename touching every file. Nobody does the rename, so the lie persists.

---

## Gotchas

- **PHP errors white-screen the site.** Edit through the repo and test locally. Never use the WordPress file editor.
- **`status_label` vs `tort_status`.** The taxonomy drives the *colour*; the ACF text field carries the *wording*. The source had 27 distinct status strings across 5 colours — this split is why we do not maintain 27 terms.
- **Excerpts.** The card uses `get_the_excerpt()`. With no manual excerpt WordPress auto-trims the content, which can cut mid-sentence. Write real excerpts.
- **Term seeding runs once**, guarded by the `glm_terms_seeded` option against `GLM_VERSION`. Bump the version in `functions.php` to re-seed.
- **Rewrite flush runs once** on version change. Never call `flush_rewrite_rules()` on every load.
- **ACF is required.** Without it `glm_field()` returns fallbacks and cards render title and excerpt only — degraded, not fatal.

---

## Not built yet

- Header and footer — Phase 5, via Header Footer Elementor
- Location and result renderers — Phase 4
- Form integration — Phase 5. `single-tort.php` exposes the `glm_case_form_shortcode` filter so swapping form plugins never means editing 40 pages.
