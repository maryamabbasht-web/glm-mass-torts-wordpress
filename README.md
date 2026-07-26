# VS-WP — GLM Mass Torts WordPress Build

Converting a single long HTML reference file into a maintainable, multi-page WordPress site that non-technical staff can manage without breaking it.

---

## Status

**Current phase:** Phase 0 complete — repository foundation
**Next phase:** Phase 1 — component inventory
**Blocked on:** the source HTML file being placed in [source/](source/)

| Phase | Description | Status |
|---|---|---|
| 0 | Repository foundation, docs, git conventions | ✅ Complete |
| 1 | Component inventory from source HTML | ⏸ Blocked — needs source file |
| 2 | WordPress baseline, child theme, design tokens | ⬜ Not started |
| 3 | Custom post types + ACF fields | ⬜ Not started |
| 4 | Component library (Saved Templates) | ⬜ Not started |
| 5 | Header, footer, page assembly | ⬜ Not started |
| 6 | Editor guide, exports, handoff | ⬜ Not started |
| — | *Deferred:* migration to live, redirects, SEO | ⬜ Not started |

> **No WordPress code exists in this repo yet.** Phase 0 was deliberately environment-independent so the architecture and rules could be settled before any building began.

---

## The problem this project solves

The failure being designed against, in the user's own words: *sections where the icon, title, and description are all crammed into one box, separated by manual text editing instead of proper section management.*

That is a symptom. The cause is that **doing it properly was harder than doing it badly**. Somebody needed a fourth card, couldn't work out how to add one correctly, and typed it into an existing text box instead.

So the guiding principle is not "pick a better builder." It is:

> **Make the correct action the easiest action.**

Every rule in [learning.md](learning.md) exists to serve that.

---

## Stack

All free. No paid licences anywhere.

| Component | Choice | Notes |
|---|---|---|
| Local environment | **LocalWP** | Not yet installed |
| Theme | **Hello Elementor** + child theme | Child theme is the git-tracked artifact |
| Builder | **Elementor Free** | Flexbox Container only — never legacy Section/Column |
| Header / footer | **Header Footer Elementor** | Replaces Pro's Theme Builder |
| Custom fields | **ACF free** | Pro not needed — see R5 |
| Custom post types | Registered in **child theme PHP** | Deliberately not CPT UI: code goes in git, database config does not |
| Post grids | Essential Addons Lite **or** Happy Addons free | Pick exactly one — never both |
| Forms | Fluent Forms free or Contact Form 7 | Elementor's Form widget is Pro |
| Backups | UpdraftPlus free | Load-bearing, given R12 |

---

## Repository structure

```
VS-WP/
├── CLAUDE.md              Standing instructions for AI assistance
├── learning.md            Decisions, principles, and the 14-rule build ruleset
├── project_history.md     Dated log of what changed and why
├── README.md              This file
├── .gitmessage            Commit template (wired to commit.template)
├── .gitignore             Scoped to what we actually track
├── .gitattributes         Line-ending normalisation
├── docs/                  Inventory, page map, design tokens, editor guide
├── source/                Reference HTML — the design source of truth
├── exports/               Elementor template + kit JSON (see R12)
└── themes/                Child theme only; parent installed via WP
```

### What is deliberately **not** tracked

WordPress core, plugins, uploads, and `wp-config.php`. Your code is versioned; fifty megabytes of core is not.

> ### ⚠️ The most important thing to understand about this repo
>
> **Elementor stores page layouts in the database (`postmeta`), not in files.** Git will capture the child theme, the custom post type code, and the documentation — but **not a single page layout**.
>
> This is why `exports/` exists and why exporting Elementor JSON at every milestone is mandatory rather than optional. It is the only thing making this build reproducible. See **R12** in [learning.md](learning.md).

---

## Documentation map

| File | What it holds | Read it when |
|---|---|---|
| [learning.md](learning.md) | Decisions log, working principles, the build ruleset | Before doing any build work |
| [project_history.md](project_history.md) | Dated narrative of changes and reasoning | Working out why something is the way it is |
| [CLAUDE.md](CLAUDE.md) | Standing instructions for AI assistance | Automatically loaded each session |
| `docs/component-inventory.md` | Every distinct section type and its fields | Phase 1 onward — *not yet written* |
| `docs/page-map.md` | Page list and which sections each uses | Phase 1 onward — *not yet written* |
| `docs/design-tokens.md` | Colours, type scale, spacing from the source HTML | Phase 1 onward — *not yet written* |
| `docs/editor-guide.md` | Plain-language guide for non-technical staff | Phase 6 — *not yet written* |

---

## Working conventions

Full specification in [learning.md](learning.md) Part 4. Summary:

**Branches** — `<type>/<kebab-description>`, e.g. `feat/cpt-practice-areas`. Never a person's name.

**Commits** —

```
type(scope): imperative summary

- concrete change
- concrete change

Why: one or two sentences of rationale.
Refs: learning.md R4
```

The `Why:` line is required. It is greppable — `git log --grep="^Why:"` gives a rationale-only history.

**Anonymity** — no names, emails, `Co-Authored-By`, `Signed-off-by`, or AI attribution in commit *messages*. The author field keeps the real identity.

**Merging** — phase branches merge with `--no-ff`; completed phases get tagged (`phase-0-foundations`, …). Tags pair a repo state with its matching `exports/` snapshot.

**Cycle discipline** — README and `project_history.md` updates ship in the *same commit* as the change they describe.

---

## Getting started

The commit template is already wired to this repo. If you clone it fresh, re-apply:

```bash
git config --local commit.template .gitmessage
```

To see the rationale behind every change so far:

```bash
git log --grep="^Why:" --format='%h %s%n%b'
```

---

## What is needed next

1. **Place the source HTML in `source/`** — this unblocks Phase 1
2. Install LocalWP and create the site
3. Run Phase 1: produce the component inventory before touching WordPress

Phase 1 is where this project is won or lost. The source file is a **design reference, not a content source** — the job is to extract the 12–18 distinct section types it actually contains and build each one once, not to port twenty pages by hand.
