# Fix SonarCloud Duplication Quality Gate — Plan

> **For Claude:** REQUIRED SUB-SKILL: Use
> superpowers:executing-plans to implement this
> plan task-by-task.

**Goal:** Eliminate test code duplication that
fails the Sonar Way quality gate on PR #43.

**Architecture:** Replace 4 structurally identical
input-validation tests with one Pest parameterized
test using a named `with()` dataset. No production
code changes.

**Tech Stack:** Pest v4, PHP 8.4

---

## Task 1: Replace validation tests with dataset

Files to modify:
`tests/Feature/Commands/CheckCommandTest.php:71-93`

### Step 1: Replace the 4 test blocks

Replace these 4 tests (lines 71-93):

```php
test('check command with missing package shows error',
    function () {
    $this->artisan('check ivuorinen')
        ->expectsOutputToContain('Missing package name')
        ->assertExitCode(1);
});

test('check command with conflicting arguments shows error',
    function () {
    $this->artisan(
        'check ivuorinen/branch-usage-checker extra'
    )
        ->expectsOutputToContain(
            'Conflicting arguments'
        )
        ->assertExitCode(1);
});

test('check command with invalid vendor shows error',
    function () {
    $this->artisan('check INVALID!/package-name')
        ->expectsOutputToContain('Invalid vendor name')
        ->assertExitCode(1);
});

test('check command with invalid package name shows error',
    function () {
    $this->artisan('check valid-vendor INVALID!')
        ->expectsOutputToContain(
            'Invalid package name'
        )
        ->assertExitCode(1);
});
```

With one parameterized test:

```php
test('check command rejects invalid input',
    function (string $args, string $expected) {
    $this->artisan($args)
        ->expectsOutputToContain($expected)
        ->assertExitCode(1);
})->with([
    'missing package' => [
        'check ivuorinen',
        'Missing package name',
    ],
    'conflicting arguments' => [
        'check ivuorinen/branch-usage-checker extra',
        'Conflicting arguments',
    ],
    'invalid vendor' => [
        'check INVALID!/package-name',
        'Invalid vendor name',
    ],
    'invalid package' => [
        'check valid-vendor INVALID!',
        'Invalid package name',
    ],
]);
```

### Step 2: Run tests to verify all pass

Run: `composer test`

Expected: 14 passed (Pest expands dataset rows
into individual test runs, so `with missing
package`, `with conflicting arguments`, etc. each
appear separately).

### Step 3: Run linter

Run: `composer lint`

Expected: Clean (no PHPCS errors).

### Step 4: Commit

```bash
git add tests/Feature/Commands/CheckCommandTest.php
git commit -m "refactor(tests): parameterize \
input-validation tests to fix duplication gate"
```

## Task 2: Push and verify quality gate

### Step 1: Push

```bash
git push
```

### Step 2: Verify on SonarCloud

Check the quality gate status via API:

```bash
curl -s 'https://sonarcloud.io/api/qualitygates/\
project_status?projectKey=\
ivuorinen_branch-usage-checker&pullRequest=43' \
| python3 -m json.tool
```

Expected: `new_duplicated_lines_density` condition
status changes from `ERROR` to `OK`.
