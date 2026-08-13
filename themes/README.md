# themes/

Only the **child theme** is tracked here. Nothing else.

```
themes/
├── README.md                      (this file)
└── hello-elementor-child/         Phase 2 — not yet created
    ├── style.css
    ├── functions.php
    ├── acf-json/                  ACF field groups as files (Phase 3)
    └── inc/
        └── post-types.php         CPT registrations (Phase 3)
```

## Why the parent theme is not here

**Hello Elementor** is installed through WordPress and updated through WordPress. Committing it would mean maintaining somebody else's code and reviewing noisy diffs on every release. `.gitignore` excludes `themes/*` and re-includes only the child.

## Two deliberate choices worth knowing

**Custom post types are registered in PHP here, not with CPT UI.**
CPT UI stores its configuration in the database. This code lives in git. Same result on screen, completely different story when you need to rebuild or review a change.

**ACF field groups sync to `acf-json/` in this theme.**
Same reasoning. Enabling ACF's JSON sync turns field definitions into version-controlled files instead of invisible database rows.

## The limit of all this

None of it captures Elementor page layouts — those live in `postmeta`. That is what [`../exports/`](../exports/) is for. See **R12** in [../learning.md](../learning.md).
