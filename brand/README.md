# brand/ — masters have moved

> **Put brand files here now:**
>
> ```
> themes/hello-elementor-child/assets/brand/
> ```
>
> Then run `studio wp glm import-brand`.

This folder is kept only as a signpost. The masters that used to live here are now inside the theme.

---

## Why they moved

Two reasons, the second decisive:

1. **Nothing under the theme could reach them.** `GLM_DIR` comes from `get_stylesheet_directory()`, which resolves through the junction to the WordPress install — so walking up from the theme lands in `wp-content/`, never in this repo.

2. **The theme is the only thing that deploys.** Assets sitting outside it would simply not exist on staging or production, no matter how correct the local paths were.

One location inside the theme works identically whether it is junctioned locally, copied, or deployed as a plain folder.

---

## Adding or replacing an asset

1. Drop the file in `themes/hello-elementor-child/assets/brand/`
2. Run:

```bash
studio wp glm import-brand
```

Idempotent — matched on a `_glm_brand_key` meta value, so re-running replaces rather than piling up duplicates.

### Expected files

| Basename | Used for | Option set |
|---|---|---|
| `GL Logo` | Header logo | `glm_logo_id` |
| `fav-icon` | Browser / site icon | `site_icon` |
| `states-hero` | States page banner | `glm_states_hero_id` |

Any of `.webp`, `.jpg`, `.jpeg`, `.png` is accepted — the command finds files by basename, so switching format needs no code change.

---

## Why masters are version-controlled at all

The Media Library lives in `wp-content/uploads/`, which is excluded from git (repo-scope decision #4 — we do not version binary uploads).

If an image exists only in the Media Library, it exists in exactly one place: one local site on one laptop. Keeping masters in the theme means a fresh clone re-imports them in one command, and you can see when a logo changed and what it changed from. The Media Library holds a *working copy*, not the only copy.

---

## Format guidance

**SVG is preferred for logos** — resolution-independent and usually smaller.

> **Gotcha:** WordPress blocks SVG uploads by default, and rightly so — an SVG is XML and can carry scripts. Do not install a plugin that allows SVGs for everyone. If we go that route it needs sanitising on upload and restricting to administrators.

Otherwise supply a PNG at **twice** its largest display size. A logo shown at 180px needs a 360px-wide file.

**Photographs** (such as the states banner) should be JPG or WebP, roughly 1920px wide, and compressed — an uncompressed hero is often the heaviest thing on a page.

### Known issue with the current logo

`GL Logo.png` is **319×49**, which is only crisp to about 160px wide on a 2× display. Most law-firm header logos sit at 180–220px, so it will look slightly soft. An SVG or a ~700px-wide PNG fixes it permanently.

---

## Social icons are handled separately

The five icons the source hotlinked from staging are no longer needed — they come from Font Awesome and inline SVG via `[glm_socials]`. Nothing to add here for those.
