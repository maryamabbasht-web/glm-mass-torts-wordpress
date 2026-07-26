# project_history.md

A working log of what changed in this project and why.

**Convention:** newest entry first. Every entry records the date, what changed, and the reasoning. Decisions and principles are *not* duplicated here — they live in [learning.md](learning.md). This file is the narrative; that file is the reference.

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
