# CLAUDE.md — standing instructions

These are permanent. They do not need to be restated each session.

---

## 1. Workspace boundary — hard rule

**Never read, list, search, or modify anything outside the `VS-WP` workspace without asking first.**

This includes the parent Desktop folder, sibling project folders, and any absolute path outside this repo. If a file is needed and it is not here, **ask for it** — do not go looking.

Exception: only when the user gives an explicit path and explicit permission, for that one file, that one time.

---

## 2. How to work

### Explain what and why
Never just produce output. State what is being done and the reasoning behind it. The user is building understanding, not just a website.

### Offer options for decisions
When there is a real choice to make, do not pick silently. Present the realistic options with their trade-offs and let the user choose. Mark a recommendation and say why it is recommended.

Routine judgment calls (naming a variable, ordering two independent steps) do not need a question — just state the call and move on.

### Always surface gotchas and pitfalls
For every option offered and every piece of work done, name the traps: what breaks later, what is hard to reverse, what looks fine now and rots in six months, what the licensing or cost catch is. **A recommendation without its downside is incomplete.**

### Meta-learning is a first-class deliverable
This matters as much as the code:

- If the user proposes something and there is a **better way to do it**, say so — before doing it.
- If there is a **better way to frame the problem**, say that too. Reframing beats optimising the wrong solution.
- When a constraint changes the answer, explain *why* the answer changed, not just what it changed to.
- Point out the general principle behind a specific fix, so it transfers to the next problem.

Do this unprompted. Do not wait to be asked.

### Be honest about problems
If a chosen approach has a serious flaw, say it plainly, once. If the user reaffirms the choice, accept it as their decision, record it as a decision rather than an oversight, and build the best possible version of it. Do not re-litigate.

---

## 3. Git rules

**Full specification lives in [learning.md](learning.md).** Summary:

- **Branches:** `<type>/<kebab-description>` — `feat/`, `fix/`, `docs/`, `refactor/`, `chore/`, `style/`
- **Never** put a person's name in a branch name
- **Commits** follow `.gitmessage`: `type(scope): imperative summary`, bullet body, required `Why:` line
- **Merge** phase branches with `--no-ff`; tag completed phases

### Commit anonymity — non-negotiable
Commit messages must never contain:
- personal names or email addresses
- `Co-Authored-By:` trailers
- `Signed-off-by:` trailers
- any AI or tool attribution of any kind

The git **author field** keeps the user's normal identity. Anonymity applies to message content only.

> The default behaviour of appending a `Co-Authored-By` trailer is **overridden**. It must be actively suppressed on every commit.

### Save before editing
Commit existing work before starting a new change cycle. Never begin edits on top of uncommitted work.

---

## 4. Cycle discipline

After every change cycle, in the **same commit** as the change:

1. Update `README.md` so it reflects current reality
2. Append a dated entry to `project_history.md` — what changed and **why**
3. If a decision or principle was established, add it to `learning.md`

Documentation that ships separately from its change will drift. Ship them together.

---

## 5. Project rules

The 14-rule build ruleset for this WordPress project lives in **[learning.md](learning.md)**. Read it before doing any build work. Cite the relevant rule in commit `Refs:` lines.

The two that are violated most easily:
- **R3** — a section is never a rich-text blob
- **R4** — repeated sections are shortcode-synced templates, never copy-pasted
