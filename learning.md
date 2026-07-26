# learning.md

Decisions made, and the principles we build by. This is the single source of truth — if code and this file disagree, this file is right and the code is a bug.

**Companion files:** [project_history.md](project_history.md) records *what happened when*. This file records *what we decided and what we always do*.

---

## Part 1 — Decisions log

| # | Date | Decision | Choice | Reasoning |
|---|---|---|---|---|
| 1 | 2026-07-25 | Page builder | **Elementor Free** | Developer is already comfortable with it, and budget is zero. Bricks was chosen first and reversed — it is licence-only with no free tier. |
| 2 | 2026-07-25 | Budget | **Everything free** | No Elementor Pro, no ACF Pro, no Bricks, no paid addons. |
| 3 | 2026-07-25 | Source HTML | Lives in `source/`, inside the repo | Keeps the design reference under version control and diffable, and keeps all work inside the workspace. |
| 4 | 2026-07-25 | Repo scope | Child theme + docs + source + exports | WP core, plugins and uploads are excluded. Your code is versioned; 50MB of core is not. |
| 5 | 2026-07-25 | Git identity | Anonymous **messages**; author field unchanged | No names, emails, or AI attribution in message content. The author field keeps the real identity. |
| 6 | 2026-07-26 | Local environment | **WordPress Studio** | Already installed. Free, by Automattic, lightweight. See the SQLite caveat below. |
| 7 | 2026-07-25 | Site status | Fresh build; replaces live site later | Redirect and SEO migration is deferred, not dropped. |
| 8 | 2026-07-25 | Role-based restriction | **None** | No role discrimination. Everyone who edits has full access. Consequence: the defence against mess is ergonomics, not permissions — see R14. |
| 9 | 2026-07-26 | Tort grid renderer | **Custom `[glm_tort_grid]` shortcode** in the child theme | Elementor Free has no ACF dynamic tags, and 6 of the card's 8 fields are ACF fields. Owning it in the theme also puts the most complex component in git rather than `postmeta` — a direct win against R12. |
| 10 | 2026-07-26 | URL scheme | `/mass-torts/{slug}/`, categories at `/mass-torts/type/{cat}/` | Matches the brand and domain. Locked before content exists because changing it later means 46 redirects. |
| 11 | 2026-07-26 | Repo ↔ Studio link | **Windows directory junction** | `mklink /J` needs no admin rights, unlike a symlink. Edit in the repo, WordPress sees it instantly, nothing to remember to copy. |

### ⚠️ Studio runs SQLite, not MySQL

WordPress Studio uses **SQLite** by default. Production will be MySQL (the source's staging URLs point at WP Engine).

- Building the theme, CPTs, ACF fields and Elementor layouts — unaffected.
- **Go-live is a content migration, not a database copy.** An SQLite database does not import into a MySQL host. Use WordPress's exporter or a migration plugin.
- A minority of plugins use MySQL-specific SQL and misbehave on SQLite. If something acts strangely, suspect this first.

Recorded so it is not a surprise at launch. Worth re-verifying on the installed Studio version.

### Reversed decisions — kept deliberately

Recording reversals matters as much as recording choices. It stops us re-treading the same ground.

| Originally chosen | Reversed to | Why it was reversed |
|---|---|---|
| Bricks Builder | Elementor Free | Bricks has no free version at all. Discovered after the budget constraint was stated. |
| ACF Pro | ACF free | Repeater exists to repeat rows *inside one post*. Under R5, each post **is** a row and the grid widget does the repeating — so Pro is unnecessary. |
| **R7 — content-only editor access** | Removed entirely | Elementor Free has no Role Manager, and the user confirmed no role-based discrimination is wanted. Its intent was rehomed into R14. |
| LocalWP | WordPress Studio | Studio was already installed, so the setup cost was zero. Brings the SQLite caveat noted above. |

### Locked slugs — changing these is expensive

| Thing | Slug | Cost of changing |
|---|---|---|
| Tort CPT | `mass-torts` | 46 redirects |
| Category base | `mass-torts/type` | 6 redirects |
| Category terms | `pharma` `device` `toxic` `product` `abuse` `tech` | Redirects **and** CSS class renames |
| Status terms | `active` `settling` `emerging` `appellate` `inactive` | CSS class renames |

Status and category slugs map directly to CSS classes (`.glm-status--active`, `.glm-pill--pharma`). Renaming a term's **label** is always safe. Renaming its **slug** breaks styling.

---

## Part 2 — Working principles

How we work, independent of any particular task.

**P1 — Explain what and why, always.** Output without reasoning teaches nothing and cannot be reviewed.

**P2 — Decisions get options, not defaults.** Real choices are presented with trade-offs and a marked recommendation. Routine calls are just made and stated.

**P3 — Every option carries its gotchas.** What breaks later, what is hard to reverse, what looks fine now and rots in six months. A recommendation without its downside is incomplete.

**P4 — Better framings beat better solutions.** If the problem is framed wrongly, optimising the solution is wasted effort. Say so before building.

**P5 — Constraints are information.** When a constraint kills the first choice, check whether it is pushing toward the boring native answer — that one is usually more durable. *(Learned on 2026-07-25: losing the paid builder pushed us toward platform-native primitives, which was an improvement, not a compromise.)*

**P6 — Never work outside the workspace.** Nothing outside `VS-WP` is read, listed, or touched without explicit permission. If a file is needed, ask for it.

**P7 — Save before editing.** Commit existing work before starting a new change cycle.

**P8 — Documentation ships with its change.** README and history updates go in the same commit as the work they describe, or they drift.

---

## Part 3 — The Build Ruleset

The reason this project exists. Each rule prevents a specific, observed failure.

> **The founding problem:** sections where an icon, title, and description are crammed into one text box, separated by hand-edited markup. Nobody does this on purpose. It happens when doing it *properly* is harder than doing it *badly*.

### Design system

#### R1 — Tokens before pixels
Use Elementor **Site Settings → Global Colors and Global Fonts** exclusively. Never pick an ad-hoc colour from the picker. Never type a raw hex on a widget. Never set a one-off font size where a Global Font applies. Configure Site Settings → Layout (container width, breakpoints) once, up front.

*Why:* a rebrand becomes eight token edits instead of four hundred widget edits.

> **Gotcha:** Elementor's colour picker makes ad-hoc colours *easier* than global ones. This rule fights the tool's default ergonomics and needs periodic auditing.

#### R2 — Flexbox Container only, never legacy Section/Column
Confirm Flexbox Container is active in **Elementor → Settings → Features** before building anything.

*Why:* legacy Section/Column emits three to four nested `div`s per row; Container emits one. This is the single biggest lever on Elementor's DOM bloat and CSS weight.

> **Gotcha:** converting later is painful and error-prone. Decide now, apply everywhere, never mix.

#### R3 — A section is never a rich-text blob
*(The founding complaint, stated as a ban.)*

Every visual unit is its own widget with its own fields.

**Banned:** multiple cards or list items inside a single Text Editor or HTML widget, separated by hand-typed markup.

Use **Icon Box** / **Image Box** for card-like units, **Icon List** for lists, and the **Accordion** / **Tabs** widgets rather than hand-built equivalents.

#### R4 — Build once, use everywhere — via shortcode, never copy-paste
Any section appearing on two or more pages becomes a **Saved Template**, inserted with `[elementor-template id="123"]`. Editing the template updates every instance.

**Banned:** copy-pasting a section between pages. It creates an independent copy that silently drifts out of sync.

> **Gotcha:** shortcode-inserted templates render as a placeholder in the editor canvas, not live content. That is the price of sync — preview the page to check.
>
> **Gotcha:** Elementor Free has no Template *widget* (that is Pro). Use the **Shortcode** widget.

#### R5 — Repeating data lives in the database, not in the page
Practice areas, case types, attorneys, testimonials, FAQs and results become **custom post types + ACF free fields**, displayed with a free post-grid widget.

*Why:* add a practice area once and it appears in the homepage grid, the archive, and the footer automatically. Typed into a page, it must be typed into six pages.

*This rule carries the weight that the removed R7 used to.* Adding a practice area is a short form — genuinely **less** work than hand-editing a card. The correct path is the easy path.

> **Gotcha:** CPT slugs are painful to change later. Decide them during Phase 1.
>
> **Gotcha (important):** Elementor Free has **no ACF dynamic tags**. Free grid widgets reliably display native post fields — title, excerpt, featured image, permalink — but arbitrary ACF fields may need a small custom shortcode. Design components around native fields wherever possible. This is a constraint on the *design*, settled in Phase 1, not a bug to patch later.

#### R6 — Header and footer are templates, not page content
Built once via the free **Header Footer Elementor** plugin. One place to edit.

### Discipline

#### R7 — Name everything in the Navigator
`Section: Hero`, not `Container`. The Navigator **is** the documentation — treat it as a table of contents someone else has to read.

#### R8 — Responsive at defined breakpoints only
**Banned:** building separate desktop and mobile versions of the same section and toggling visibility. It doubles content maintenance and harms SEO.

> **Gotcha:** Elementor makes hide-on-mobile a single click, so this rule gets violated more than any other. Audit for it explicitly.

#### R9 — Custom CSS goes in the child theme stylesheet
With a comment explaining why it was needed. (Per-element Custom CSS is Pro-only, so circumstance enforces this for us.)

#### R10 — A page is a stack of named, section-level Containers
Its Navigator should read like a table of contents.

#### R11 — Ship checklist
Nothing ships without: exactly one `H1`, correct heading hierarchy, alt text on every image, and a mobile pass.

#### R12 — Elementor layouts live in `postmeta`, not in files
**Critical consequence: git will not capture a single page layout.** The repo holds the child theme, CPT code, docs, and source reference only.

**Mandatory mitigation:** export Elementor Saved Templates and the Site Settings kit as JSON into `exports/`, committed at every milestone, plus scheduled database backups. Tag each phase so a repo state can be paired with its matching export.

Without this, the build is not reproducible.

#### R13 — Anything global is a developer action, and gets logged
New Saved Template, new CPT, new Global Color or Font, new ACF field group → recorded in `project_history.md` with the date and the reasoning.

#### R14 — Make the correct action the easiest action
*(Replaces the removed R7. This is the load-bearing rule now.)*

With no permission barrier, mess is prevented by ergonomics alone. For every piece of content someone will change, there must be an obvious, low-effort, correct path — documented in `docs/editor-guide.md`.

**If the hacky path is easier than the correct one, the structure has failed. Rework the structure; do not write more training material.**

---

## Part 4 — Git conventions

### Branch naming

Format: `<type>/<short-kebab-description>` — lowercase, hyphens only, max ~4 words. Always branch off `main`, always merge back to `main`.

| Type | Used for | Example |
|---|---|---|
| `feat/` | New capability | `feat/cpt-practice-areas` |
| `fix/` | Correcting broken behaviour | `fix/mobile-nav-overflow` |
| `docs/` | Documentation only | `docs/component-inventory` |
| `refactor/` | Restructuring, no behaviour change | `refactor/container-cleanup` |
| `chore/` | Tooling, config, dependencies | `chore/repo-foundation` |
| `style/` | Formatting or CSS only | `style/spacing-scale` |

> **Gotcha:** never prefix a branch with a person's name (`maryam/hero-fix`). It is a common convention and it defeats the anonymity requirement.

### Commit format

```
<type>(<scope>): <imperative summary>

- concrete change
- concrete change

Why: one or two sentences of rationale.
Refs: learning.md R4
```

**Subject:** imperative mood (`add`, not `added`), lowercase after the colon, no trailing full stop, 50 characters maximum. Body wraps at 72.

**Scopes:** `repo`, `env`, `docs`, `history`, `theme`, `tokens`, `cpt`, `acf`, `template`, `header`, `footer`, `page`, `export`

`Why:` is **required**. It is what makes `git log` useful a year from now, and it is greppable: `git log --grep="^Why:"`.

`Refs:` is optional and cites the rule that drove the change. Over time this reveals which rules are load-bearing and which are dead weight worth deleting.

### Anonymity

Banned anywhere in a commit message: personal names, email addresses, `Co-Authored-By:`, `Signed-off-by:`, and any AI or tool attribution.

The git **author field** keeps the real identity (decision #5). Anonymity applies to message *content* only.

### Merging and tagging

- Merge phase branches with `--no-ff` so each phase remains a visible, revertable unit in `main`.
- Tag completed phases: `phase-0-foundations`, `phase-1-inventory`, …
- **Tags matter because of R12:** Elementor exports are point-in-time snapshots. A tag is the only thing pairing a repo state with the matching `exports/` JSON.
