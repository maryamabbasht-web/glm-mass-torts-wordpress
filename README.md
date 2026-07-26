# VS-WP — GLM Mass Torts WordPress Build

Converting a single long HTML reference file into a maintainable, multi-page WordPress site that non-technical staff can manage without breaking it.

---

## Status

**Current phase:** Child theme written — [themes/hello-elementor-child/](themes/hello-elementor-child/)
**Blocked on:** the WordPress Studio site path, to create the junction and activate

| Phase | Description | Status |
|---|---|---|
| 0 | Repository foundation, docs, git conventions | ✅ Complete |
| 1 | Component inventory from source HTML | ✅ Complete |
| 2 | Child theme + design tokens | ✅ Written, ⏸ not yet activated |
| 3 | Custom post types + ACF fields | ✅ Written, ⏸ not yet activated |
| 4 | Component library (Saved Templates) | ⬜ Not started |
| 5 | Header, footer, page assembly | ⬜ Not started |
| 6 | Editor guide, exports, handoff | ⬜ Not started |
| — | *Deferred:* migration to live, redirects, SEO | ⬜ Not started |

> **The theme is written but has never been executed.** It was built ahead of the environment so setup was never the bottleneck. All 7 PHP files pass `php -l` on PHP 8.3 and all 3 ACF JSON files parse — but nothing has run against WordPress. Expect a debugging pass on first activation. That is planned, not a surprise.

> **No WordPress code exists in this repo yet.** Phases 0 and 1 were deliberately environment-independent so the architecture, rules, and component model could be settled before any building began.

### What Phase 1 found

The source is **one page**, not twenty — containing **14 distinct components** and **40 pieces of repeating data**.

| Metric | Value |
|---|---|
| Distinct components to build | **14** |
| Repeating data items | **52** (40 torts, 8 locations, 4 results) |
| Pages the split produces | **10 hand-built + 46 generated** |
| Lines of the source to discard | **~235** (it is a Claude Artifact export) |
| Defects found in the source | **5** |

Five defects worth knowing about, all detailed in [component-inventory.md §2](docs/component-inventory.md#2-defects-found-in-the-source):

1. **18 duplicated tort cards** in dead markup with invalid duplicate IDs — invisible, and already drifted
2. **The tort count is wrong** — the page says 35, there are 40
3. **"About Us" scrolls to the wrong section** — `id="about"` is on Divisions
4. **Malformed CSS comments** silently disable rules, so parts of the stylesheet are inert
5. **Every image is hotlinked from someone else's staging server**

Numbers 1 and 2 are the project's founding problem caught in the act: copy-paste drift, and a hardcoded count nobody updated. **R4** and **R5** exist precisely to make both impossible.

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
| Local environment | **WordPress Studio** | Installed. Runs SQLite, not MySQL — see [learning.md](learning.md) |
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
| [docs/component-inventory.md](docs/component-inventory.md) | The 14 components, the 3 CPTs, source defects, and the tort-grid decision | Before building anything |
| [docs/page-map.md](docs/page-map.md) | The 10 pages, URL scheme, navigation mapping, component usage matrix | Phase 5 |
| [docs/design-tokens.md](docs/design-tokens.md) | Colours, type scale, spacing, breakpoints, assets to re-host | Phase 2 |
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

1. **Provide the WordPress Studio site path** — the folder containing `wp-content`. Needed to create the junction.
2. **Activate and debug** — install Hello Elementor and ACF free, activate the child theme, work through first-run issues.
3. **Import the 40 torts** from `source/glmasstorts.html` into the CPT.

### Connecting the repo to Studio

```
cmd /c mklink /J "<studio-site>\wp-content\themes\hello-elementor-child" "<repo>\themes\hello-elementor-child"
```

A junction rather than a symlink, because `mklink /J` needs no administrator rights. Edit in the repo, WordPress sees the change instantly, nothing to remember to copy.

> **Gotcha:** this command is Windows-only. On macOS or Linux the equivalent is `ln -s`.

### The highest-value outcome of this migration

Right now all 40 torts live on one page and every card links to `#contact`. There is no page for *"Ozempic lawsuit"*, or Camp Lejeune, or hernia mesh — 40 high-intent search terms sharing a single URL.

Because torts become a custom post type, **each one gets its own page automatically**: a URL, a sitemap entry, and a slot in every grid and menu. That is 40 landing pages generated rather than built, and for a search-driven mass tort practice it is worth more than the redesign.
