# Branch Usage Checker

This file provides guidance to Claude Code
(claude.ai/code) when working with code in this
repository.

## Project Overview

Branch Usage Checker is a Laravel Zero CLI tool
that cross-references GitHub branches with
Packagist download statistics to identify branches
safe to delete. Built as a PHAR-distributable PHP
application.

## Commands

- `composer install` — Install dependencies
- `composer test` — Run tests (Pest v4)
- `composer build` — Build PHAR executable
  to `builds/branch-usage-checker`
- `composer lint` — Check code style (PHPCS)
- `composer lint:md` — Lint markdown files
- `composer lint:ec` — Check EditorConfig compliance
- `composer lint:all` — Run all linters
- `composer format` — Auto-fix code style (PHPCBF)
- `composer format:md` — Format Markdown tables
- `composer x` — Run the built PHAR
- `vendor/bin/pest --filter "test name"` — Run a
  single test

## Code Standards

- PSR-12 via PHP CodeSniffer (`phpcs.xml`),
  with `PSR12.Operators.OperatorSpacing` excluded
- PHP 8.4 required
- Composer normalize runs automatically on
  autoload dump
- CaptainHook pre-commit hook runs PHPCBF
  then PHPCS on staged PHP files automatically

## Architecture

This is a Laravel Zero console application.
Entry point is `./application`, which bootstraps
via `bootstrap/app.php`.

### Core Flow (CheckCommand)

`check {vendor} {package?} {months=9}` — the main
(and only functional) command. The `vendor` argument
accepts a combined `vendor/package` form, making
`package` optional in that case:

1. Fetches package metadata from
   `packagist.org/packages/{vendor}/{package}.json`
2. Extracts branches (versions prefixed with
   `dev-`)
3. For each branch, fetches monthly download
   stats from Packagist over the configured
   lookback window
4. Displays a statistics table and a suggestions
   table (branches with zero downloads)

### Key Directories

- `app/Commands/` — CLI commands
  (CheckCommand is the primary one)
- `app/Dto/` — Spatie DataTransferObject classes
  for Packagist API responses
- `tests/Feature/Commands/` — Feature tests
  for commands
- `builds/` — PHAR output directory

### Dependencies of Note

- HTTP requests use `Illuminate\Http\Client\Factory`
  (Guzzle-backed), injected via the container
- DTOs use `spatie/data-transfer-object` with
  `MapFrom` attributes for JSON field mapping
- PHAR building configured in `box.json`
