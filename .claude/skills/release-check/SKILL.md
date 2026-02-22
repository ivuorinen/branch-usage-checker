---
name: release-check
description: Run full pre-release verification suite
disable-model-invocation: true
---

# Pre-Release Verification

Run the complete verification suite before a release.

## Steps

1. Run `composer lint:all` — all linters must pass (PHPCS, EditorConfig, markdownlint)
2. Run `composer test` — all tests must pass
3. Run `composer build` — PHAR must build successfully
4. Run `builds/branch-usage-checker --version` — smoke test the PHAR
5. Run `git status` — working tree should be clean (no uncommitted changes)
6. Report go/no-go summary with results of each step
