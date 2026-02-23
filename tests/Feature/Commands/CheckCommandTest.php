<?php

use Illuminate\Support\Facades\Http;

const TEST_COMMAND = 'check test-vendor test-package';
const TEST_STATS_URL = 'packagist.org/packages/test-vendor/test-package/stats';

test('check command with slash format', function () {
    $this->artisan('check ivuorinen/branch-usage-checker')
        ->assertExitCode(0);
});

test('check command with two arguments', function () {
    $this->artisan('check ivuorinen branch-usage-checker')
        ->assertExitCode(0);
});

test('check command with missing package shows error', function () {
    $this->artisan('check ivuorinen')
        ->expectsOutputToContain('Missing package name')
        ->assertExitCode(1);
});

test('check command with conflicting arguments shows error', function () {
    $this->artisan('check ivuorinen/branch-usage-checker extra')
        ->expectsOutputToContain('Conflicting arguments')
        ->assertExitCode(1);
});

test('check command with invalid vendor shows error', function () {
    $this->artisan('check INVALID!/package-name')
        ->expectsOutputToContain('Invalid vendor name')
        ->assertExitCode(1);
});

// --- New tests using Http::fake() ---

function validMetadata(): array
{
    return [
        'package' => [
            'name'        => 'test-vendor/test-package',
            'description' => 'Test',
            'time'        => '2024-01-01T00:00:00+00:00',
            'type'        => 'library',
            'repository'  => 'https://github.com/test-vendor/test-package',
            'language'    => 'PHP',
            'versions'    => [
                'dev-main'    => ['version' => 'dev-main'],
                'dev-feature' => ['version' => 'dev-feature'],
                '1.0.0'       => ['version' => '1.0.0'],
            ],
        ],
    ];
}

function statsResponse(array $downloads): array
{
    return [
        'labels' => ['2024-01', '2024-02', '2024-03'],
        'values' => [$downloads],
    ];
}

test('check command with invalid package name shows error', function () {
    $this->artisan('check valid-vendor INVALID!')
        ->expectsOutputToContain('Invalid package name')
        ->assertExitCode(1);
});

test('check command with 404 shows package not found', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/nonexistent-pkg.json' => Http::response([], 404),
    ]);

    $this->artisan('check test-vendor nonexistent-pkg')
        ->expectsOutputToContain('Package not found')
        ->assertExitCode(1);
});

test('check command with 500 shows server error', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response([], 500),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Failed to fetch package metadata (HTTP 500)')
        ->assertExitCode(1);
});

test('check command skips branch when stats fetch fails', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response(validMetadata()),
        TEST_STATS_URL . '/dev-feature.json*' => Http::response([], 500),
        TEST_STATS_URL . '/dev-main.json*' => Http::response(statsResponse([10, 20, 30])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Failed to fetch stats for dev-feature')
        ->assertExitCode(0);
});

test('check command stops when all stats fail', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response(validMetadata()),
        TEST_STATS_URL . '/*' => Http::response([], 500),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('No statistics found... Stopping.')
        ->assertExitCode(0);
});

test('check command handles exception in try block', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response([
            'package' => ['versions' => 'not-an-array'],
        ]),
    ]);

    $this->artisan(TEST_COMMAND)
        ->assertExitCode(0);
});

test('check command shows no suggestions when all branches have downloads', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response(validMetadata()),
        TEST_STATS_URL . '/dev-main.json*' => Http::response(statsResponse([10, 20, 30])),
        TEST_STATS_URL . '/dev-feature.json*' => Http::response(statsResponse([5, 10, 15])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('No suggestions available. Good job!')
        ->assertExitCode(0);
});

test('check command suggests branches with zero downloads', function () {
    Http::fake([
        'packagist.org/packages/test-vendor/test-package.json' => Http::response(validMetadata()),
        TEST_STATS_URL . '/dev-main.json*' => Http::response(statsResponse([10, 20, 30])),
        TEST_STATS_URL . '/dev-feature.json*' => Http::response(statsResponse([0, 0, 0])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Found 1 branches')
        ->assertExitCode(0);
});
