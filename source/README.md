# source/

The design reference for this build.

## What goes here

The long HTML file representing `glmasstorts.com`, plus any CSS or small assets it directly references.

Suggested naming: `glmasstorts.html`, with assets in `source/assets/`.

## What this is — and is not

> **This is a design reference, not a content source.**

The instinct is to port it page by page into WordPress. That is the trap that produces an unmaintainable site.

The job in Phase 1 is to read this file and extract the **12–18 distinct section types** it actually contains — hero, card grid, stat band, FAQ, CTA, and so on — then build each one **once**. A twenty-page site is nearly always a small set of components repeated.

Building pages instead of components is how sites become unmaintainable.

## Why it lives in the repo

So the reference we built from is preserved, diffable, and permanently available. Six months from now, "why is the hero laid out like that?" has an answer that is still in version control.

## Note on large assets

Video, `.psd`, `.ai`, and `.zip` files are excluded by `.gitignore` to keep the repo light. Images used on the finished site belong in the WordPress Media Library, not here — only keep what is needed to understand the design.
