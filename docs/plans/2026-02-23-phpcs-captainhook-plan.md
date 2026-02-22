# PHPCS + CaptainHook Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use
> superpowers:executing-plans to implement this plan
> task-by-task.

**Goal:** Add PHPCS/PHPCBF as a dev dependency with
composer scripts and CaptainHook-managed pre-commit
hook for automatic formatting.

**Architecture:** Install three dev packages
(`squizlabs/php_codesniffer`, `captainhook/captainhook`,
`captainhook/hook-installer`). Add `composer lint` and
`composer format` scripts. Configure CaptainHook to run
PHPCBF on staged PHP files before each commit. The
existing `phpcs.xml` is unchanged.

**Tech Stack:** PHP 8.4, Composer, PHPCS/PHPCBF,
CaptainHook

---

## Task 1: Install squizlabs/php\_codesniffer

**Files:**

- Modify: `composer.json` (auto-updated by composer)
- Modify: `composer.lock` (auto-updated by composer)

### Step 1: Install the package

Run:

```bash
composer require --dev squizlabs/php_codesniffer
```

Expected: Package installs successfully.
`composer.json` now lists `squizlabs/php_codesniffer`
in `require-dev`.

### Step 2: Verify phpcs works with existing config

Run:

```bash
vendor/bin/phpcs --standard=phpcs.xml app/ tests/
```

Expected: Either clean output or a list of violations.
The command should not error out — it should find and
use `phpcs.xml`.

### Step 3: Verify phpcbf works

Run:

```bash
vendor/bin/phpcbf --standard=phpcs.xml app/ tests/
```

Note: `phpcbf` exits non-zero when it finds fixable
issues. The `|| true` prevents that from stopping
execution. What matters is it runs without crashing.

### Step 4: Commit

```bash
git add composer.json composer.lock
git commit -m "build(deps): add squizlabs/php_codesniffer"
```

---

## Task 2: Add composer lint and format scripts

**Files:**

- Modify: `composer.json` — add two entries to
  `"scripts"`

### Step 1: Add the scripts

In `composer.json`, add these two entries inside the
`"scripts"` object:

```json
"lint": "vendor/bin/phpcs",
"format": "vendor/bin/phpcbf || true"
```

Note: `phpcbf` returns exit code 1 when it fixes files
(which is normal behavior, not an error). The `|| true`
prevents composer from treating successful fixes as
failures. If there are unfixable errors, `phpcbf`
returns exit code 2, but `|| true` masks that too —
this is acceptable since `composer lint` is the proper
check command.

### Step 2: Verify `composer lint` works

Run:

```bash
composer lint
```

Expected: Runs `phpcs` against the project. Either
reports violations or shows no output (clean).

### Step 3: Verify `composer format` works

Run:

```bash
composer format
```

Expected: Runs `phpcbf`. Auto-fixes any fixable
violations.

### Step 4: Run tests to confirm nothing broke

Run:

```bash
composer test
```

Expected: All 14 tests pass.

### Step 5: Commit

```bash
git add composer.json
git commit -m "build: add composer lint and format scripts"
```

---

## Task 3: Install CaptainHook

**Files:**

- Modify: `composer.json` (auto-updated by composer)
- Modify: `composer.lock` (auto-updated by composer)

**Step 1: Install captainhook and the hook-installer
plugin**

Run:

```bash
composer require --dev captainhook/captainhook captainhook/hook-installer
```

Note: The installer may prompt about allowing the
plugin. Answer yes. If `composer.json`'s
`config.allow-plugins` needs updating, composer will
do it automatically when you approve.

### Step 2: Verify CaptainHook is available

Run:

```bash
vendor/bin/captainhook --version
```

Expected: Prints a version string
(e.g. `CaptainHook x.x.x`).

### Step 3: Commit

```bash
git add composer.json composer.lock
git commit -m "build(deps): add captainhook and hook-installer"
```

---

## Task 4: Configure CaptainHook pre-commit hook

**Files:**

- Create: `captainhook.json`

### Step 1: Create the CaptainHook config

Create `captainhook.json` in the project root with
this content:

```json
{
  "pre-commit": {
    "enabled": true,
    "actions": [
      {
        "action": "vendor/bin/phpcbf --standard=phpcs.xml {$STAGED_FILES|of-type:php}",
        "config": {
          "label": "Fix code style with PHPCBF"
        }
      },
      {
        "action": "vendor/bin/phpcs --standard=phpcs.xml {$STAGED_FILES|of-type:php}",
        "config": {
          "label": "Check code style with PHPCS"
        }
      }
    ]
  },
  "pre-push": {
    "enabled": false,
    "actions": []
  },
  "commit-msg": {
    "enabled": false,
    "actions": []
  }
}
```

The two pre-commit actions run in order:

1. `phpcbf` auto-fixes what it can
2. `phpcs` checks for remaining violations — if any
   exist, the commit is blocked

### Step 2: Install the hooks into `.git/hooks`

Run:

```bash
vendor/bin/captainhook install --force
```

Expected: CaptainHook installs hook scripts into
`.git/hooks/`. Output mentions installing hooks.

### Step 3: Verify the hook is installed

Run:

```bash
head -5 .git/hooks/pre-commit
```

Expected: Shows a CaptainHook-generated script
(not a sample hook).

### Step 4: Commit

```bash
git add captainhook.json
git commit -m "build: configure CaptainHook pre-commit hook for PHPCS"
```

Note: This commit itself will trigger the pre-commit
hook for the first time. If it blocks due to formatting
issues in existing files, run `composer format` first,
then re-stage and commit.

---

## Task 5: Fix existing violations and final verify

### Step 1: Run the formatter on the whole project

Run:

```bash
composer format
```

Expected: Fixes any existing violations across `app/`
and `tests/`.

### Step 2: Run the linter to confirm clean

Run:

```bash
composer lint
```

Expected: No violations reported (clean exit).

### Step 3: Run tests to confirm nothing broke

Run:

```bash
composer test
```

Expected: All 14 tests pass.

### Step 4: Commit any formatting changes

```bash
git add -A
git status
git commit -m "style: auto-fix code style with phpcbf"
```

Note: Only commit if there are actual changes. If
`composer format` made no changes, skip this step.

---

## Task 6: Update CLAUDE.md

**Files:**

- Modify: `CLAUDE.md` — add `composer lint` and
  `composer format` to Commands section, note
  CaptainHook in Code Standards

### Step 1: Update CLAUDE.md

In the `## Commands` section, add:

```markdown
- `composer lint` — Check code style (PHPCS)
- `composer format` — Auto-fix code style (PHPCBF)
```

In the `## Code Standards` section, add a note:

```markdown
- CaptainHook pre-commit hook runs PHPCBF then
  PHPCS on staged PHP files automatically
```

### Step 2: Commit

```bash
git add CLAUDE.md
git commit -m "docs: add lint/format commands and hook info to CLAUDE.md"
```
