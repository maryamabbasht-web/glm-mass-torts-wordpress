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
