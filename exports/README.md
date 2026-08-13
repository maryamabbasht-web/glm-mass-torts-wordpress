# exports/

Elementor JSON snapshots. **This directory is not optional.**

## Why it exists

> **Elementor stores page layouts in the WordPress database (`postmeta`), not in files.**

Git tracks the child theme, the custom post type code, and the documentation. It does **not** track a single page layout. If the database is lost, the theme code alone rebuilds nothing.

This directory is the mitigation. See **R12** in [../learning.md](../learning.md).

## What gets exported

| Export | Source in WP | Filename pattern |
|---|---|---|
| Saved Templates | Templates → Saved Templates → Export | `template-<name>.json` |
| Site Settings kit | Tools → Import/Export Kit → Export | `kit-<YYYY-MM-DD>.json` |

Include the Global Colors and Global Fonts in the kit export — those are the design tokens from **R1**, and they live in the database too.

## When to export

At **every milestone**, committed alongside the work it captures. Not "at the end" — an export you meant to take is worth nothing.

## Why tags matter here

An export is a point-in-time snapshot with no inherent link to the code that accompanied it. Tagging each completed phase (`phase-0-foundations`, `phase-1-inventory`, …) is what pairs a repo state with the matching export.

Without tags, you have a folder of JSON files and no reliable way to know which commit each belongs to.

## Restore test

At least once before handoff, import these files into a clean LocalWP site and confirm the result matches. An untested backup is a guess.
