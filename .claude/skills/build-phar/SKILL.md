---
name: build-phar
description: Build PHAR executable and run smoke test
disable-model-invocation: true
---

# Build PHAR

Build the PHAR executable and verify it works.

## Steps

1. Run `composer test` — abort if any tests fail
2. Run `composer build` — build PHAR to `builds/branch-usage-checker`
3. Run `builds/branch-usage-checker --version` — verify it launches successfully
4. Report the file size of `builds/branch-usage-checker` and confirm success
