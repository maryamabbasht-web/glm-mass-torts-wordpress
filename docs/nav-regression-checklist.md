# Navigation regression checklist

Manual tests required before switching the site header from HFE's Navigation Menu widget to `glm_nav()`.

Everything here is something **static analysis cannot confirm** — key presses, focus movement, rendered pixels, runtime errors. The automated checks already passed and are listed in [nav-menu-replacement.md §7](nav-menu-replacement.md); this covers the rest.

---

## Setup

**Test page:** `http://localhost:8882/glm-nav-test/`

That page renders **both** implementations, which is the point:

- **In the page body** → the new `glm_nav()`
- **In the site header at the top** → the existing HFE widget

So every visual test is a direct A/B on one screen. If the site is not running: `studio start --skip-browser`.

**Open dev tools before you start.** Keep the Console tab visible throughout — item 1.4 depends on it, and a runtime error during any other test is a blocker regardless of which test surfaced it.

**Browsers:** Chrome or Edge is enough for a first pass. Repeat §2 in Firefox and Safari before going live, since focus behaviour differs most between engines.

---

## How to record results

Mark each row **PASS** / **FAIL** / **N/A**. For any FAIL, note what you saw — "submenu stayed open" is more useful than "broken".

**Blocking vs non-blocking:**

| Severity | Meaning | Rows |
|---|---|---|
| 🔴 **Blocker** | Do not switch | all of §2, plus 1.1–1.4 |
| 🟡 **Should fix** | Switch is fine, fix soon after | §3 visual differences |
| ⚪ **Nice to have** | Log and move on | §5 |

---

## 1. Functional 🔴

| # | Test | Steps | Expected |
|---|---|---|---|
| 1.1 | Desktop layout | Window ≥ 1024px wide | Menu is horizontal. Hamburger **not** visible. |
| 1.2 | Submenu opens on hover | Hover **Mass Torts** | Dropdown appears below, 6 category links, does not overlap the logo or clip at the header edge. |
| 1.3 | Submenu closes | Move the pointer away | Dropdown closes. |
| 1.4 | **No console errors** | Watch Console through 1.1–1.3 | **Zero** errors. Warnings from other plugins are fine; anything mentioning `nav.js` is not. |
| 1.5 | Links navigate | Click **About**, then **Mass Torts → Pharmaceutical Drugs** | Correct pages load. |
| 1.6 | Current page marked | Land on `/about/` | The **About** item is visually distinct. |
| 1.7 | Mobile panel opens | Narrow to 375px, click hamburger | Panel opens, items stack vertically, icon becomes an X. |
| 1.8 | Mobile submenu | Tap the caret next to **Mass Torts** | Submenu expands inline (accordion, not a flyout). Tapping the **label** still navigates. |
| 1.9 | Outside click closes | With the panel open, click the page body | Panel closes. |
| 1.10 | Resize resets | Open the mobile panel, widen past 900px | Panel closes cleanly; no stuck open state, no menu stranded off-screen. |

### 1.11 — Breakpoint band 🔴 **test this specifically**

This is where HFE is **currently broken** — it hardcodes 767/1024 in JavaScript while the design breaks at 900.

| Width | Expected in `glm_nav()` | What HFE does today |
|---|---|---|
| 375px | Hamburger | Hamburger |
| 768px | Hamburger | Hamburger |
| 899px | Hamburger | Hamburger |
| **901px** | **Horizontal menu** | ⚠️ may still show hamburger |
| **1023px** | **Horizontal menu** | ⚠️ may still show hamburger |
| 1440px | Horizontal menu | Horizontal |

Drag the window slowly through **880 → 920px**. The switch must happen exactly once, at 900px, with no flicker and no state where both are visible.

---

## 2. Accessibility 🔴 — all blocking

Do this section **entirely with the keyboard**. Put the mouse down.

### 2.1 Desktop keyboard

| # | Key | Expected |
|---|---|---|
| 2.1.1 | `Tab` into the nav | Focus lands on the first link with a **visible** focus ring. |
| 2.1.2 | `Tab` repeatedly | Focus moves through every link **and** the submenu toggle button, in visual order. |
| 2.1.3 | `Shift+Tab` | Moves backwards through the same sequence. Never skips, never traps. |
| 2.1.4 | `Enter` on a link | Navigates. |
| 2.1.5 | `Enter` on the submenu toggle | Submenu opens. |
| 2.1.6 | `Space` on the submenu toggle | Submenu opens. (Both keys must work — buttons respond to both.) |
| 2.1.7 | `Escape` with submenu open | Submenu closes **and focus returns to the toggle button**. |
| 2.1.8 | `↓` / `↑` | Focus moves between items. Wraps at both ends. |

### 2.2 Mobile keyboard — at 375px

| # | Key | Expected |
|---|---|---|
| 2.2.1 | `Enter` / `Space` on hamburger | Panel opens, focus stays on the toggle. |
| 2.2.2 | `Tab` through the open panel | Focus moves through the menu items. |
| 2.2.3 | **Focus trap** | Keep pressing `Tab` past the last item — focus **wraps to the first item**, it does **not** escape to page content behind the panel. |
| 2.2.4 | `Shift+Tab` on the first item | Wraps to the last item. |
| 2.2.5 | `Escape` | Panel closes **and focus returns to the hamburger**. |

> **2.2.3 is the single most important row in this document.** It is the one thing `glm_nav()` adds that HFE never had. If focus escapes the open panel, do not switch.

### 2.3 ARIA — live in the inspector

Inspect the elements while operating them. The attributes must **change**, not just exist.

| # | Element | Check |
|---|---|---|
| 2.3.1 | `.glm-nav__toggle` | `aria-expanded` flips `false` ⇄ `true` as the panel opens and closes. |
| 2.3.2 | `.glm-nav__toggle-sub` | `aria-expanded` flips as the submenu opens and closes. |
| 2.3.3 | `.glm-nav__toggle-sub` | `aria-haspopup="true"` present. |
| 2.3.4 | `.glm-nav__toggle` | `aria-controls="glm-nav-menu"` and an element with that id exists. |
| 2.3.5 | Active page link | `aria-current="page"` on exactly one link. |
| 2.3.6 | Both toggles | Are `<button type="button">` — **not** `<div>`. |

### 2.4 Screen reader — if one is available

NVDA (Windows) or VoiceOver (`Cmd+F5` on macOS). Skip only if genuinely unavailable, and note that it was skipped.

| # | Check |
|---|---|
| 2.4.1 | The hamburger announces as a **button**, named "Menu", with its collapsed/expanded state. |
| 2.4.2 | The submenu toggle announces as **"Show submenu for Mass Torts, button, collapsed"**. |
| 2.4.3 | Opening the submenu announces the state change. |
| 2.4.4 | The nav announces as a **navigation landmark** named "Primary". |
| 2.4.5 | The current page link announces as **current**. |

---

## 3. Visual regression 🟡

Compare `glm_nav()` in the page body against HFE in the header, on the same screen.

At **375px**, **900px** and **1440px**, check:

| # | Property | Expected |
|---|---|---|
| 3.1 | Font family | Outfit — matches |
| 3.2 | Font size | ~14px desktop links |
| 3.3 | Link colour | Pale blue `--glm-accent-light` |
| 3.4 | Hover colour | White |
| 3.5 | Active item | Visually distinct |
| 3.6 | Item spacing | Comparable to HFE — no cramping or drift |
| 3.7 | Dropdown position | Directly below its parent, left-aligned, not clipped |
| 3.8 | Dropdown background | White, blue top border, drop shadow |
| 3.9 | Hamburger | Three white bars, animates to an X |
| 3.10 | Mobile panel | Full width, dark background, blue top border |

> Exact-pixel parity is **not** required. `glm_nav()` uses the design tokens, so where it differs from HFE it is likely to be *more* correct. Flag anything that looks unintentional or broken — not everything that differs.

---

## 4. No-JavaScript fallback ⚪

Disable JS (dev tools → Command palette → *Disable JavaScript*) and reload.

| # | Expected |
|---|---|
| 4.1 | Menu items are **visible**, not hidden behind a dead hamburger. |
| 4.2 | Hamburger is hidden. |
| 4.3 | All links still navigate. |

---

## 5. Cross-browser ⚪

Repeat **§2.1** and **§2.2** in each. Focus behaviour is where engines differ most.

| Browser | §2.1 | §2.2 |
|---|---|---|
| Chrome / Edge | ☐ | ☐ |
| Firefox | ☐ | ☐ |
| Safari | ☐ | ☐ |

---

## Sign-off

Switching the header requires:

- **All of §1** pass
- **All of §2** pass — no exceptions, no "mostly works"
- **§3** has no unintentional breakage
- **§4** passes

Any 🔴 failure means the implementation needs fixing first. That is the correct outcome, not a setback — it is exactly why the replacement was built in parallel instead of swapped in directly.

```
Tested by:  ______________________    Date: ____________
Browser(s): ______________________
Screen reader used: ______________    (or: not available)

§1 Functional      PASS / FAIL
§2 Accessibility   PASS / FAIL
§3 Visual          PASS / FAIL
§4 No-JS           PASS / FAIL

Notes / failures:


Approved to switch:  YES / NO
```

---

## What happens after you confirm

1. Header template switched from the HFE widget to `[glm_nav]`
2. HFE Navigation Menu widget removed from the header
3. `wp glm build-header-footer --force` to regenerate
4. `wp glm audit` re-run
5. Full template regression re-run
6. Final report: architecture and styling conflicts, and confirmed asset savings

**HFE the plugin stays installed** — it provides the header/footer template system. Only its nav widget stops being used. See [nav-menu-replacement.md §7](nav-menu-replacement.md#7-implementation-status--2026-07-29).
