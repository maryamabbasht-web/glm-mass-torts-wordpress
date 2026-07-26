# Editing the GL Mass Torts website

A plain-language guide for the people who keep the site up to date. You do not need to know how the site is built to use it.

**The short version:** almost everything you'll want to change is a form. If you find yourself dragging boxes around or fighting a layout, stop — that's a sign you're in the wrong place. Ask a developer.

---

## Contents

1. [Add a new mass tort case](#1-add-a-new-mass-tort-case)
2. [Change an existing tort](#2-change-an-existing-tort)
3. [Update a settlement or verdict figure](#3-update-a-settlement-or-verdict-figure)
4. [Add, change or close an office](#4-add-change-or-close-an-office)
5. [Change wording on a page](#5-change-wording-on-a-page)
6. [Things to leave alone](#6-things-to-leave-alone)
7. [If something looks broken](#7-if-something-looks-broken)

---

## 1. Add a new mass tort case

This is the most common job, and it is a form.

**Mass Torts → Add New**

| Field | What to put |
|---|---|
| **Title** | The case name, e.g. *Depo-Provera Brain Tumor Litigation* |
| **Content** | A short description — two or three sentences |
| **Excerpt** | The same description, shortened. **This is what shows on the cards.** |
| **Category** *(sidebar)* | Pick one: Pharmaceutical Drugs, Medical Devices, Toxic Exposure, Consumer Products, Sexual Assault & Abuse, Technology & Emerging |
| **Status** *(sidebar)* | Pick one: Active, Settling, Emerging, Appellate, Not Active — this sets the badge **colour** |
| **Status Label** | The badge **wording**, e.g. *Active · Filing Now* |
| **MDL Reference** | e.g. *MDL #3140 · 2,100+ cases* |
| **Settlement Estimate** | e.g. *Est. $75K–$1M+ projected* |
| **Featured Tort** | Leave off for most. On makes it a large dark card — about one per category |

Press **Publish**. That's it.

### What happens automatically

You do not need to do any of this — it just happens:

- The tort gets **its own page** at `/mass-torts/your-case-name/`
- It appears in the **category tab** on the Mass Torts page
- It appears in the **menu** under Mass Torts
- The **counter updates** — the homepage says "40+ Active Mass Torts" because there are 40. Add one and it says 41
- It appears in the **contact form's case-type dropdown**
- It gets added to the **sitemap** for Google

> **Why this matters:** the old site listed torts by hand in six different places. They fell out of sync — the menu, the counter and the form dropdown all disagreed with each other. Now there is one list and everything reads from it.

### Two things worth getting right

**Write a real excerpt.** If you leave it blank, WordPress chops the description at a word limit and it can cut mid-sentence on the cards.

**The Status dropdown sets the colour, the Status Label sets the words.** So "Active" (green) with the label "Active · Bellwether 2026". They are separate on purpose — there are five colours but dozens of possible wordings.

---

## 2. Change an existing tort

**Mass Torts → All Mass Torts → click the one you want.**

Change whatever you need, press **Update**. Every place that tort appears updates at once.

**To reorder the cards:** each tort has an **Order** number under Page Attributes. Lower numbers come first.

---

## 3. Update a settlement or verdict figure

The big figures on the About section — *$11B+*, *$1.1B*, *198K+*.

**Case Results → All Results → click one.**

| Field | What to put |
|---|---|
| **Title** | The case name |
| **Amount** | The figure, kept short — it displays large |
| **Description** | One line explaining it |

> **Include a date in the description** where the number will age — *"as of Q1 2026"*. These are advertising claims on a law firm site, so a figure that quietly goes stale is a real problem, not a cosmetic one.

---

## 4. Add, change or close an office

**Locations → Add New** (or click an existing one).

| Field | What to put |
|---|---|
| **Title** | The city, e.g. *Boca Raton* |
| **State** | Pick from the dropdown |
| **Street Address** | Street on one line, city and ZIP on the next |
| **Phone** | e.g. *(561) 995-1966* |
| **Secondary Phone** | Only if the office has two numbers |

Offices appear in the **footer** and on the **Locations page**, grouped by state. Add one and it shows up in both.

**Closing an office:** move it to Trash. It disappears everywhere.

---

## 5. Change wording on a page

Text that appears on **one page only** — Pages → click the page → edit.

Text that appears in a **section shared across pages** — the hero, stats bar, about block, divisions, contact block — lives in **Templates → Saved Templates**. Edit it once there and every page using it updates.

> **This is deliberate.** The contact block appears on four pages. If it were copied onto each one, changing the phone number would mean four edits and three chances to miss one.

### How to tell which is which

Open the page. If its content is just a short list of lines in square brackets like `[glm_section slug="hero"]`, those are the shared sections — go and edit the template instead.

---

## 6. Things to leave alone

Not because you'll break the site, but because these have a correct route that is faster than the alternative:

| Don't | Do this instead |
|---|---|
| Type a tort into a page by hand | Mass Torts → Add New |
| Type a number like "40+" into text | It's already automatic |
| Copy a section from one page to another | Insert the shared section instead |
| Change colours or fonts on individual elements | Ask a developer — the palette is set in one place |
| Build a separate mobile version of a section | Ask a developer — sections already adapt |
| Paste several cards into one text box | Ask a developer to add a proper block |

> **The last one is the important one.** The old site had sections where an icon, a title and a description were all crammed into a single text box, separated by hand-typed code. That happened because adding a fourth card properly was harder than typing it in badly. If you ever find yourself in that position, **that's a bug in how the site is built — please say so** rather than working around it.

---

## 7. If something looks broken

**A change didn't appear.** Hard-refresh the page: `Ctrl + F5` on Windows.

**A tort isn't showing on the Mass Torts page.** Check it's Published, not Draft, and that it has a Category set.

**A page shows `[glm_section slug="…"]` as literal text.** Something's wrong with the site setup — tell a developer, don't try to fix the page.

**Anything else** — say what you were doing and what you expected. Screenshots help.

---

## For developers

The build ruleset is in [learning.md](../learning.md). Before handoff, run:

```bash
studio wp glm audit
```

This checks the site against the ruleset — raw hex values, legacy layout elements, unnamed layers, duplicated responsive variants, missing `<h1>`s, external hotlinks, and placeholder pages.

> **The R14 test:** hand this guide to someone non-technical and ask them to do three real tasks. If they reach for a workaround, the *structure* has failed — fix the structure, not the guide.
