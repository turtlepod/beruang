# AGENTS.md — Beruang Budget Plugin

## Version bump

Run: `npm version X.Y.Z`

This updates **all** locations automatically:
- `package.json` + `package-lock.json` (via `npm version`)
- `beruang-budget.php` header + constant (via `postversion` sed)
- `tests/bootstrap.php` constant (via `postversion` sed)
- `readme.txt` Stable tag (via `postversion` sed)

`npm version` also creates a git commit + tag by default. Use `--no-git-tag-version` to skip.

**Never bump unless explicitly asked.**
