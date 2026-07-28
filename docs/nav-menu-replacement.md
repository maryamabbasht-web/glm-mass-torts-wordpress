# HFE Navigation Menu — replacement feasibility audit

Before replacing HFE's nav widget, an inventory of what it actually does — so the swap trades presentation control for nothing we needed.

**Sources:** `navigation-menu.php` (2,062 lines), `menu-walker.php` (113 lines), `inc/js/frontend.js`, and the rendered markup on the live site.

---

## 1. What HFE's widget currently provides

### Markup and structure

| Feature | Detail | In use? |
|---|---|---|
| Semantic `<nav>` | `<nav class="hfe-nav-menu__layout-horizontal hfe-nav-menu__submenu-arrow">` | ✅ yes |
| Custom walker | `Menu_Walker extends \Walker_Nav_Menu` — adds `hfe-menu-item` / `hfe-sub-menu-item` classes, `parent` class on items with children | ✅ yes |
| Sub-menu wrapper | `<div class="hfe-has-submenu-container">` around parent links | ✅ 1 in use (Mass Torts) |
| Sub-menu arrow | `<span class="hfe-menu-toggle sub-arrow">` with an `<i class="fa">` | ✅ yes |
| Hamburger toggle | `<div class="hfe-nav-menu__toggle" role="button" tabindex="0" aria-label="Menu Toggle">` | ✅ yes |
| Screen-reader text | 6 instances on the page | ✅ yes |

### Accessibility — verified on the rendered page

| Attribute | Where | Present |
|---|---|---|
| `role="button"` | toggle + submenu container | ✅ |
| `tabindex="0"` | toggle + submenu container | ✅ |
| `aria-label="Menu Toggle"` | hamburger | ✅ |
| `aria-haspopup="true"` | submenu container | ✅ |
| `aria-expanded` | submenu container, toggled by JS | ✅ `false` initially |
| `aria-hidden` | toggle icon | ✅ |
| `.screen-reader-text` | "Menu" label | ✅ |

### Keyboard behaviour — from `inc/js/frontend.js`

| Behaviour | Line | Notes |
|---|---|---|
| `Escape` closes the menu | 264 | …and returns focus to the toggle (266) |
| Arrow-key navigation | 271, 277 | next/previous menu item |
| `Enter` / `Space` activates toggle | 729 | explicitly filtered |
| `keyup` on submenu container | 303 | opens/closes submenu |
| `keyup` on menu items | 370 | item-level handling |
| ARIA state kept in sync | 318–347 | `aria-expanded` updated on open/close |

**This is a genuinely accessible implementation.** It is the strongest argument for keeping the widget, and the part most likely to be lost by a careless replacement.

### Layout options (mostly unused)

Horizontal · vertical · expandible · flyout (with overlay, push, slide) · dropdown breakpoint · pointer styles (underline, framed, background) · submenu icons · alignment.

**Our config uses only:** `layout: horizontal`, `dropdown: tablet`. Everything else is dead weight — and it's what produces the 21 CSS selectors reaching (0,6,1).

---

## 2. A bug in the current setup

HFE's JavaScript **hardcodes its breakpoints**:

```js
window.matchMedia( "( max-width: 767px )" )    // line 215
window.matchMedia( "( max-width: 1024px )" )   // lines 218, 245
```

Our design breaks at **900px**, and the Elementor kit is configured `mobile=767 / tablet=900`.

So between **901px and 1024px** the JS thinks it is in tablet mode while our CSS thinks it is desktop. The hamburger and the horizontal menu disagree about which should be visible in that band. This is **not configurable** — the values are literals in the plugin's JS.

Worth stating plainly: the current nav is already subtly broken at those widths, independent of any styling conflict.

---

## 3. What `wp_nav_menu()` gives us for free

| Feature | Native | Notes |
|---|---|---|
| Semantic `<ul>` / `<li>` structure | ✅ | |
| `current-menu-item`, `current-menu-ancestor` classes | ✅ | WordPress core |
| `menu-item-has-children` class | ✅ | core, since 3.7 |
| Sub-menu nesting | ✅ | `<ul class="sub-menu">` |
| Custom walker support | ✅ | same API HFE uses |
| `<nav>` wrapper + `aria-label` | ✅ | via `container` / `container_aria_label` |
| Menu order and structure from admin | ✅ | identical — same WP menu |

---

## 4. What must be reimplemented

Honest list. None of this is exotic, but none of it is free either.

| Feature | Effort | Notes |
|---|---|---|
| Hamburger toggle button | Small | `<button>` — better than HFE's `div[role=button]` |
| Show/hide at breakpoint | Small | CSS only, at **our** 900px — fixes §2 |
| Submenu open/close | Small | `<details>`/`<summary>`, or ~30 lines of JS |
| `aria-expanded` sync | Small | must not be skipped |
| `Escape` closes + returns focus | Small | ~6 lines |
| Arrow-key navigation | **Medium** | the one genuinely fiddly piece |
| Focus trap in mobile menu | **Medium** | HFE does **not** implement this — an improvement, not a regression |
| Click-outside to close | Small | ~5 lines |

**Estimated:** ~120 lines of PHP walker + ~80 lines of JS + CSS.

### What we would lose and not rebuild

- Flyout / vertical / expandible layouts — **unused**
- Pointer effects (underline, framed, background) — **unused**
- HFE's icon picker for the hamburger — replace with one inline SVG
- Per-item Elementor style controls — **deliberately** removed; that is the point

---

## 5. Editor experience — unchanged

This is the key reassurance, and it holds:

**The menu is still edited at Appearance → Menus.** Nothing about how an editor adds, removes, reorders or nests items changes. HFE's widget does not own the menu — it only *renders* the `Primary` menu, which `glm build-pages` generates from the `tort_category` taxonomy.

| Task | Today | After |
|---|---|---|
| Add a menu item | Appearance → Menus | **identical** |
| Reorder / nest | Appearance → Menus | **identical** |
| Add a tort category | Automatic via taxonomy | **identical** |
| Change nav colours | HFE panel *(currently the only thing that works)* | `components.css` + tokens |
| Change nav layout | HFE panel | developer, in CSS |

Only the last two change — and those are the ones we *want* to move into git.

---

## 6. Recommendation

**Replace, but only on the condition that the accessibility behaviour is rebuilt first, not afterwards.**

The trade is favourable:

**Gained** — nav styling in git and on tokens; 21 HFE selectors and their CSS removed from every page; the 900px breakpoint bug fixed; a real `<button>` instead of `div[role=button]`; a focus trap HFE never had.

**Lost** — unused layout modes, and roughly 200 lines we now maintain ourselves.

**Risk** — the arrow-key navigation and ARIA sync are the parts most likely to be quietly dropped. They should be written first and verified with a keyboard before the HFE widget is removed.

> **The one thing that would change this recommendation:** if nobody will ever keyboard-test the replacement, HFE's accessible-but-unstyleable widget is better than a good-looking inaccessible one. On a law firm site with ADA exposure, that is not a hypothetical concern.

### Suggested sequence

1. Build `glm_nav_menu()` walker + JS + CSS **alongside** the HFE widget
2. Keyboard-test: Tab, Shift-Tab, Enter, Space, Escape, arrows
3. Verify ARIA with an accessibility inspector
4. Test the 767 / 900 / 1024 bands specifically
5. Only then swap the header template and remove the HFE widget
6. Re-run `wp glm audit`
