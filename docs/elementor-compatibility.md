# Elementor widget compatibility matrix

Audit of every Elementor widget type in use, against the architecture:

> **GLM owns presentation. Elementor owns layout.**

Generated from the live site — widget inventory read from `_elementor_data` across all templates and pages, Elementor's selectors read from the compiled files in `uploads/elementor/css/`.

**Date:** 2026-07-29 · **Elementor:** 4.2.0 · **HFE:** installed

---

## 1. Widget inventory

Only **five** widget types are in use.

| Widget | Instances | GLM classes attached |
|---|--:|---|
| `heading` | 32 | `glm-eyebrow` `glm-section-title` `glm-hero__title` `glm-contact__sub` `glm-stat__lbl` `glm-highlight__icon` `glm-highlight__sub` `glm-div-card__num` `glm-div-card__icon` `glm-div-card__title` |
| `text-editor` | 21 | `glm-hero__lead` `glm-about__copy` `glm-about__results` `glm-about__disclaimer` `glm-stat__num` `glm-highlight__label` `glm-div-card__desc` `glm-contact__lead` `glm-footer__divisions` `glm-footer__offices` `glm-footer__socials` `glm-footer__legal` |
| `button` | 6 | `glm-btn` `glm-btn--cta` `glm-btn--outline` `glm-header__cta` `glm-div-card__link` |
| `image` | 1 | `glm-header__logo` |
| `navigation-menu` | 1 | `glm-header__nav` |

> **Note:** GLM classes are attached **directly to the widget root** (`.elementor-widget-*`), not to a wrapper. This is the single most important fact in this document — it determines whether a reset can use inheritance or must out-specify.

---

## 2. Compatibility matrix

| Widget | Elementor targets | Specificity | Properties | Reset covers it? | Action | Verdict |
|---|---|---|---|---|---|---|
| `heading` | **inner** `.elementor-heading-title` | (0,2,0) | font-family, size, weight, line-height, color — *all inheritable* | ✅ yes | none | ✅ **Compatible** |
| `button` | **inner** `.elementor-button` | (0,2,0) | background-color, font-*, text-transform, letter-spacing | ⚠️ partial | keep `html` prefix for `background`, `padding`, `border` | ✅ **Compatible** |
| `text-editor` | **ROOT** `.elementor-widget-text-editor` | (0,1,0) | font-family, size, weight, line-height, color | ❌ **wrong** | remove from reset; prefix component rules | ⚠️ **Needs fix** |
| `image` | inner `.widget-image-caption` | (0,2,0) | color, font-* | ➖ n/a | none — captions unused | ✅ **Compatible** |
| `navigation-menu` | inner `.hfe-menu-item`, `.hfe-sub-menu-item`, dropdowns | **(0,2,1) → (0,6,1)** | font-*, color, background-color, border-color | ❌ no | **see §4** | ❌ **Incompatible** |

---

## 3. `text-editor` — the root-collision

The only widget where Elementor styles the **same element** our GLM class sits on.

```
.elementor-widget-text-editor          (0,1,0)  ← Elementor, on the widget root
.glm-hero__lead                        (0,1,0)  ← ours, on the SAME element
```

A tie, decided by load order — and Elementor's file is printed in the `<body>`, after ours in the `<head>`. **Elementor wins.**

Worse, the current reset also targets the root:

```css
html .elementor-widget-text-editor { font: inherit; color: inherit; }   /* (0,2,1) */
```

At (0,2,1) that beats our own (0,1,0) component rules, so **the reset itself suppresses GLM styling** on all 21 text-editor widgets.

### Fix

1. **Remove** `.elementor-widget-text-editor` from the neutralisation layer — a root-level reset can never work here.
2. **Prefix** text-editor component rules with `html` so they reach (0,1,1) and beat Elementor's (0,1,0).

Inner `<p>` and `<li>` then inherit from the widget root as normal.

> Drop-cap rules exist at (0,3,0) but only apply with `.elementor-drop-cap-view-*` classes, which we never set. Not a concern.

---

## 4. `navigation-menu` — genuinely incompatible

HFE's nav widget emits **21 distinct selectors**, reaching **(0,6,1)**:

```
(0,2,1)  .elementor-widget-navigation-menu a.hfe-menu-item
(0,3,1)  .elementor-widget-navigation-menu .menu-item a.hfe-menu-item
(0,4,1)  .elementor-widget-navigation-menu .menu-item.current-menu-item a.hfe-menu-item
(0,5,1)  .elementor-widget-navigation-menu .hfe-nav-menu-layout:not(…) …
(0,6,1)  .elementor-widget-navigation-menu .hfe-nav-menu-layout:not(…):not(…) …
```

covering font, colour, background and borders on links, sub-menus, hover, focus, current-item and dropdowns.

**Our current nav CSS does not apply.** `.glm-header__nav .hfe-nav-menu a` is (0,2,1), losing to HFE's (0,3,1).

This is not fixable by prefixing. Matching (0,6,1) would mean writing deliberately grotesque selectors for every state, and they would break the next time HFE changes its markup.

### Options

| Option | Trade-off |
|---|---|
| **Let HFE own nav styling** | Configure via HFE's panel. Works, but nav appearance then lives in `postmeta` — outside git, outside the token system. Two sources of truth return, in one place. |
| **Match HFE's specificity** | Grotesque selectors, brittle across HFE updates, and each new state needs its own escalation. |
| **Render the menu ourselves** *(recommended)* | Replace the HFE widget with `wp_nav_menu()` in the header template. Same pattern already used for the tort grid, results and locations. Full control, plain `.glm-nav` classes, no HFE CSS at all. |

The third option also **removes 21 selectors** of HFE CSS from every page.

---

## 5. Architecture verdict

The architecture is sound for **4 of 5** widget types, and the failures are specific rather than systemic:

- **`heading`, `button`, `image`** — fully compatible. Reset via inheritance works because Elementor only styles inner elements.
- **`text-editor`** — compatible after the two-line fix in §3.
- **`navigation-menu`** — not compatible. HFE is a second design system in its own right, and the honest resolution is to not use its widget.

### The invariant, restated precisely

> **GLM classes sit on the widget root.**
> **The reset only ever targets elements *inside* a widget.**
> **Where Elementor styles the widget root itself, the GLM rule carries the `html` prefix.**
> **Where a third-party widget out-specifies us beyond reach, we render it ourselves.**

### Coverage check for future widgets

Before using a new Elementor widget, run `wp glm audit` and check:

1. Does Elementor style the widget **root** or an **inner** element?
2. If inner → add it to the reset; inheritance handles the rest.
3. If root → prefix the GLM component rules instead; never reset the root.
4. If it emits above (0,3,0) → treat as incompatible and render it in the theme.
