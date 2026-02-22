# PHPCS + PHPCBF + CaptainHook Integration

Date: 2026-02-23

## Goal

Add local code formatting enforcement via PHPCS/PHPCBF
with automatic pre-commit hooks managed by CaptainHook.

## New Dependencies

- `squizlabs/php_codesniffer` (dev) — linter (`phpcs`)
  and auto-fixer (`phpcbf`)
- `captainhook/captainhook` (dev) — git hook manager
- `captainhook/hook-installer` (dev) — Composer plugin
  that auto-installs hooks on `composer install`

## Composer Scripts

- `composer lint` — runs `phpcs` to report violations
- `composer format` — runs `phpcbf` to auto-fix

## Config Files

### phpcs.xml (existing, unchanged)

PSR-12 with two exclusions:

- `PSR12.Operators.OperatorSpacing`
- `PSR1.Files.SideEffects.FoundWithSymbols`

### captainhook.json (new)

Pre-commit hook that runs `phpcbf` on staged PHP files.
If unfixable issues remain, the commit is blocked.

## Pre-commit Hook Behavior

1. Developer commits
2. CaptainHook triggers pre-commit
3. Runs `phpcbf` on staged PHP files
4. If all issues auto-fixed, commit proceeds
5. If unfixable issues remain, commit blocked

## What Does NOT Change

- `phpcs.xml` rules stay the same
- CI workflow unchanged (Codacy handles remote checks)
- No functional code changes
