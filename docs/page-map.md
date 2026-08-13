# Page map

Which pages exist, and which components build each one.

---

## The gap between what you have and what you asked for

The source is **one page**. Everything — hero, about, divisions, all 40 torts, contact — lives at `/` and is navigated with anchor links (`#home`, `#about`, `#torts`, `#contact`).

The brief was to create **separate pages**. That is the right instinct, and the reason is bigger than tidiness.

### Why splitting matters here specifically

Right now all 40 torts sit on one page, and every tort card links to `#contact`. So:

- **There is no page for "Ozempic lawsuit."** Or Camp Lejeune, or hernia mesh, or Roundup. Forty high-intent search terms, one URL between them.
- Google is asked to rank a single page for 40 unrelated queries. It will pick one, at best.
- There is nowhere to put the depth these cases need — eligibility criteria, deadlines, settlement history, FAQs — because it would all have to live on the homepage.

Mass tort marketing is search-driven. Someone typing *"depo provera brain tumor lawsuit"* at 11pm needs a page about **that**. Sending them to a homepage anchor loses them.

> **The reframe:** splitting into pages is not a housekeeping task. **Each tort becomes a landing page**, and that is the single highest-value outcome of this whole migration.

And because torts are a CPT (**R5**), those 40 pages are generated — not built. Add a tort, get a page, a URL, a sitemap entry, and a slot in every grid. Automatically.

---

## Proposed page structure

| # | Page | URL | Source | Built from |
|---|---|---|---|---|
| 1 | **Home** | `/` | Elementor page | S1, S2, S3, S4 (summary), S5, S6 (preview), S7 |
| 2 | **Mass Torts index** | `/mass-torts/` | CPT archive | S6 (full browser) |
| 3 | **Tort detail** ×40 | `/mass-torts/{slug}/` | CPT single | **Generated** |
| 4 | **Category archive** ×6 | `/mass-torts/category/{slug}/` | Taxonomy archive | **Generated** |
| 5 | **About** | `/about/` | Elementor page | S4 (full), S3, S5 |
| 6 | **Contact** | `/contact-us/` | Elementor page | S7, locations grid |
| 7 | **Locations** | `/locations/` | Elementor page | P7 via CPT |
| 8 | **Privacy Policy** | `/privacy-policy/` | Standard page | — |
| 9 | **Terms** | `/terms/` | Standard page | — |
| 10 | **FAQ** | `/faq/` | Standard page | — |

**Total: 10 hand-built + 46 generated = 56 URLs**, from 14 components.

That ratio is the whole argument for component thinking.

### URL scheme

```
/                                    Home
/mass-torts/                         All 40, tabbed
/mass-torts/ozempic-gastroparesis/   One tort  ← the SEO asset
/mass-torts/category/pharma/         10 pharmaceutical torts
/about/
/contact-us/
/locations/
```

> **Gotcha — settle slugs before creating content (R5).** The CPT slug (`mass-torts`) and the taxonomy base (`category`) are baked into every URL. Changing them after publishing means 46 redirects. Decide now.
>
> **Gotcha — `/mass-torts/category/` collides conceptually with core's `category`.** Use a distinct base such as `/mass-torts/type/` to avoid confusion with post categories.

---

## Component usage per page

| Component | Home | Torts index | Tort single | About | Contact | Locations |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| G1 Site Header | ● | ● | ● | ● | ● | ● |
| G2 Site Footer | ● | ● | ● | ● | ● | ● |
| S1 Hero | ● | | | | | |
| S2 Hero Case Form | ● | | ● | | | |
| S3 Stats Bar | ● | ● | | ● | | |
| S4 About | ● partial | | | ● full | | |
| S5 Divisions Grid | ● | | | ● | | |
| S6 Mass Torts Browser | ● preview | ● full | | | | |
| S7 Contact Section | ● | | ● | | ● | |
| P7 Office Location | | | | | ● | ● |

Every ● in a row is a page that breaks if the component is copy-pasted rather than shortcode-inserted. **S7 appears on four pages — R4 is doing real work here**, not enforcing theory.

---

## Tort detail page — the template worth designing well

Forty pages come off this one template, so it deserves more thought than the others.

| Block | Source |
|---|---|
| Breadcrumb | Home → Mass Torts → Category → Tort |
| Title (`h1`) | `post_title` |
| Status badge + category pill | `tort_status`, `tort_category` |
| MDL reference | `mdl_reference` |
| Settlement estimate | `settlement_estimate` |
| Description | `post_content` |
| **Case evaluation form** | S2 — the conversion point |
| Related torts | Query loop, same `tort_category`, exclude current |
| Legal disclaimer | Global |

> **Gotcha:** the source gives each tort roughly **50 words**. That is thin for a page meant to rank. These pages will need real content — eligibility, deadlines, symptoms, settlement history — written over time. The template should make thin pages look intentional rather than broken, and the CPT should carry optional fields the copy can grow into. Flag as a content workstream, not a build task.

---

## Navigation mapping

### Current → proposed

| Nav item | Currently | Becomes |
|---|---|---|
| Home | `https://www.glmasstorts.com/` (absolute) | `/` |
| Start Here ▾ | `#formsection` + 24 anchor links | Mega-menu → `/mass-torts/{slug}/` |
| About Us | `#about` — **lands on Divisions** (defect 2.3) | `/about/` |
| Contact | `https://www.glmasstorts.com/contact-us/` (absolute) | `/contact-us/` |
| Divisions ▾ | 3 external firm sites | Unchanged — genuinely external |

> **Gotcha — absolute URLs in the nav.** The source hardcodes `https://www.glmasstorts.com/...` even for internal links. On a local or staging build every one of those jumps to production. Use relative URLs or WordPress menu items throughout.

### The mega-menu is now generated

The header dropdown lists 24 torts by hand, in four columns, and the mobile menu lists its own separate copy of 33. They already disagree with each other.

Once torts are a CPT, the mega-menu is a query — grouped by `tort_category`, ordered, limited. **One menu, one source, no drift, no duplicate mobile markup (R8).**

### Dead links to resolve

Footer links for **Privacy & Policy**, **Terms**, **About us**, and **FAQ** all point to `href="#"`.

> ⚠️ For a law firm collecting personal injury details through web forms, a missing Privacy Policy is a compliance exposure, not a cosmetic gap. Pages 8–10 are not optional.

---

## Home page composition

The homepage should preview, not duplicate.

| Section | Treatment |
|---|---|
| S1 Hero + S2 Form | Full |
| S3 Stats Bar | Full |
| S4 About | **Trimmed** — first paragraph + 4 highlights, link to `/about/` |
| S5 Divisions | Full |
| S6 Torts | **Preview** — 6 featured torts + "View all 40" → `/mass-torts/` |
| S7 Contact | Full |

> **Why preview rather than the full 40:** rendering 40 cards plus a duplicated set of dropdown links is what makes the current page heavy. More importantly, if the homepage already shows everything, `/mass-torts/` has no reason to exist and no reason to rank. Give each page a distinct job.

---

## Deferred — migration to live

Not now, but recorded so it is not forgotten (see [learning.md](../learning.md) decision #7):

1. Crawl the live site for its real URL inventory
2. Map old → new; 301 everything that moves
3. Preserve title tags and meta descriptions where they already rank
4. Verify the case-type dropdown in S7 still matches the tort list
5. Submit an updated sitemap

> **Gotcha:** the source's contact form dropdown lists **29 case types**, but there are **40 torts**. It is already out of sync. Once torts are a CPT, populate that dropdown from the same query — one source, no drift.
