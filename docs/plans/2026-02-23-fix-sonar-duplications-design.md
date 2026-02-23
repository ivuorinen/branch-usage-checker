# Fix SonarCloud Duplication Quality Gate

## Problem

PR #43 fails the Sonar Way quality gate: `new_duplicated_lines_density` is
7.5% (threshold: <=3%). SonarCloud detects 2 duplicated blocks / 28 lines,
all within `tests/Feature/Commands/CheckCommandTest.php`.

The duplication is between 4 structurally identical input-validation tests
(lines 71-93) that each follow the same 3-line pattern:

```php
$this->artisan('<args>')
    ->expectsOutputToContain('<message>')
    ->assertExitCode(1);
```

## Solution

Replace the 4 separate tests with one Pest parameterized test using a named
`with()` dataset:

```php
test('rejects invalid input', function (
    string $args,
    string $expected,
) {
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

## Impact

- **File:** `tests/Feature/Commands/CheckCommandTest.php`
- **Lines removed:** ~24 (4 test blocks)
- **Lines added:** ~10 (1 parameterized test)
- **Test count:** Stays at 14 (Pest expands each dataset row)
- **Expected duplication:** 0% on new code
