# Branch Usage Checker

[![Packagist Version][pkg-shield]][packagist]
[![License][license-shield]][packagist]
[![CI][ci-shield]][ci]

A CLI tool that cross-references GitHub branches with Packagist download
statistics to identify branches safe to delete. Configure a lookback window
(default 9 months), review a statistics table of per-branch downloads, and
get a suggestion list of zero-download branches you can clean up.

## Requirements

- PHP 8.4+

## Installation

### Composer (global)

```bash
composer global require ivuorinen/branch-usage-checker
```

### PHAR

Download the latest `branch-usage-checker` PHAR from
[GitHub Releases][releases], then:

```bash
chmod +x branch-usage-checker
./branch-usage-checker check <vendor/package>
```

## Usage

```text
branch-usage-checker check <vendor/package> [months]
```

| Argument         | Description            | Default |
|------------------|------------------------|---------|
| `vendor/package` | Packagist package name |         |
| `months`         | Months to look back    | `9`     |

### Example

```text
$ branch-usage-checker check ivuorinen/branch-usage-checker 6

 Branch usage statistics for ivuorinen/branch-usage-checker
 ┌────────────────────┬────────┬────────┬────────┬────────┬────────┬────────┐
 │ Branch             │ 2025-7 │ 2025-8 │ 2025-9 │ 2025-… │ 2025-… │ 2025-… │
 ├────────────────────┼────────┼────────┼────────┼────────┼────────┼────────┤
 │ dev-master         │     42 │     38 │     51 │     47 │     55 │     60 │
 │ dev-feat/dto       │      0 │      0 │      0 │      0 │      0 │      0 │
 │ dev-fix/timeouts   │      3 │      1 │      0 │      0 │      0 │      0 │
 └────────────────────┴────────┴────────┴────────┴────────┴────────┴────────┘

 Suggestions — branches with zero downloads (safe to delete)
 ┌────────────────────┐
 │ Branch             │
 ├────────────────────┤
 │ dev-feat/dto       │
 └────────────────────┘
```

## Development

```bash
git clone https://github.com/ivuorinen/branch-usage-checker.git
cd branch-usage-checker
composer install
```

| Command           | Description                      |
|-------------------|----------------------------------|
| `composer test`   | Run tests (Pest)                 |
| `composer lint`   | Check code style (PHPCS, PSR-12) |
| `composer format` | Auto-fix code style (PHPCBF)     |
| `composer build`  | Build PHAR to `builds/`          |

Pre-commit hooks ([CaptainHook][captainhook]) are installed automatically and
will run PHPCBF + PHPCS on staged PHP files.

## License

MIT — see [LICENSE](LICENSE) for details.

[packagist]: https://packagist.org/packages/ivuorinen/branch-usage-checker
[pkg-shield]: https://img.shields.io/packagist/v/ivuorinen/branch-usage-checker
[license-shield]: https://img.shields.io/packagist/l/ivuorinen/branch-usage-checker.svg
[ci-shield]: https://img.shields.io/github/actions/workflow/status/ivuorinen/branch-usage-checker/test-and-build.yml?branch=master&label=CI
[ci]: https://github.com/ivuorinen/branch-usage-checker/actions/workflows/test-and-build.yml
[releases]: https://github.com/ivuorinen/branch-usage-checker/releases
[captainhook]: https://github.com/captainhookphp/captainhook
