# brand/

**Put the logo files here.** This is the answer to "where do I add the logo file?"

```
brand/
├── logo.svg              Primary logo — preferred format
├── logo.png              Raster fallback, 2x the largest display size
├── logo-mark.svg         Monogram / icon-only version
└── favicon.png           512×512, square
```

Drop the files in, tell me, and I import them into the WordPress Media Library with:

```bash
studio wp media import brand/logo.svg --title="GL Mass Torts Logo"
```

---

## Why here and not straight into WordPress

You *can* upload through **Media → Add New** in the admin, and that works. But the Media Library lives in `wp-content/uploads/`, which is **excluded from git** (repo-scope decision #4 — we do not version binary uploads).

So if the logo only ever exists in the Media Library, it exists in exactly one place: a local SQLite site on one laptop.

Keeping masters here means:

- The source-of-truth files are version-controlled and travel with the repo
- A rebuild on a fresh site re-imports them in one command
- You can see when the logo changed, and what it changed from

The Media Library then holds a *working copy*, not the only copy.

---

## Format guidance

**SVG is strongly preferred** for the logo. It is resolution-independent, so it stays sharp on retina displays and at any size, and it is usually smaller than a PNG.

> **Gotcha:** WordPress **blocks SVG uploads by default**, and for good reason — an SVG is XML and can carry scripts. Do not install a plugin that simply allows SVGs for everyone. If we go the SVG route, I will sanitise on upload and restrict it to administrators.

If SVG is not available, supply a PNG at **twice** the largest size it will display at. A logo shown at 180px wide needs a 360px-wide file.

### About the current logo

Both hotlinked staging copies are **~1.5 KB PNGs**, which is very small for a logo — almost certainly too low-resolution to look sharp on a modern display.

Worth noting: the nav and footer pull from **two different staging servers** and the files are **different sizes** (1446 vs 1545 bytes). The "same" logo has already diverged into two versions. Consolidating to one master here fixes that permanently.

---

## What the logo is used for

| Where | Built in | Notes |
|---|---|---|
| Site header | Header Footer Elementor — Phase 5 | Links to `/` |
| Site footer | Header Footer Elementor — Phase 5 | Often the mark-only version |
| Site icon / favicon | Appearance → Customize | Square, 512×512 minimum |
| Open Graph image | SEO plugin — later | 1200×630, logo on a branded background |

---

## Social icons are handled separately

The five social icons the source hotlinked from staging are **no longer needed** — they come from Font Awesome via the `[glm_socials]` shortcode. Nothing to add here for those.
