<?php

use Illuminate\Support\Facades\Http;

const TEST_VENDOR  = 'test-vendor';
const TEST_PACKAGE = 'test-package';
const TEST_COMMAND = 'check ' . TEST_VENDOR . ' ' . TEST_PACKAGE;
const TEST_METADATA_URL = 'packagist.org/packages/' . TEST_VENDOR . '/' . TEST_PACKAGE . '.json';
const TEST_STATS_URL = 'packagist.org/packages/' . TEST_VENDOR . '/' . TEST_PACKAGE . '/stats';

beforeEach(function () {
    Http::preventStrayRequests();
});

function validMetadata(): array
{
    return [
        'package' => [
            'name'        => TEST_VENDOR . '/' . TEST_PACKAGE,
            'description' => 'Test',
            'time'        => '2024-01-01T00:00:00+00:00',
            'type'        => 'library',
            'repository'  => 'https://github.com/' . TEST_VENDOR . '/' . TEST_PACKAGE,
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

function fakePackageResponses(array $statsPerBranch = []): void
{
    $fakes = [TEST_METADATA_URL => Http::response(validMetadata())];
    foreach ($statsPerBranch as $branch => $response) {
        $fakes[TEST_STATS_URL . '/' . $branch . '.json*'] = $response;
    }
    Http::fake($fakes);
}

test('check command with slash format', function () {
    fakePackageResponses([
        'dev-feature' => Http::response(statsResponse([1, 2, 3])),
        'dev-main'    => Http::response(statsResponse([1, 2, 3])),
    ]);

    $this->artisan('check ' . TEST_VENDOR . '/' . TEST_PACKAGE)
        ->assertExitCode(0);
});

test('check command with two arguments', function () {
    fakePackageResponses([
        'dev-feature' => Http::response(statsResponse([1, 2, 3])),
        'dev-main'    => Http::response(statsResponse([1, 2, 3])),
    ]);

    $this->artisan('check ' . TEST_VENDOR . ' ' . TEST_PACKAGE)
        ->assertExitCode(0);
});

test('check command rejects invalid input', function (string $args, string $expected) {
    $this->artisan($args)
        ->expectsOutputToContain($expected)
        ->assertExitCode(1);
})->with([
    'missing package'        => ['check ivuorinen', 'Missing package name'],
    'conflicting arguments'  => ['check ivuorinen/branch-usage-checker extra', 'Conflicting arguments'],
    'invalid vendor'         => ['check INVALID!/package-name', 'Invalid vendor name'],
    'invalid package'        => ['check valid-vendor INVALID!', 'Invalid package name'],
]);

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
        TEST_METADATA_URL => Http::response([], 500),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Failed to fetch package metadata (HTTP 500)')
        ->assertExitCode(1);
});

test('check command skips branch when stats fetch fails', function () {
    fakePackageResponses([
        'dev-feature' => Http::response([], 500),
        'dev-main'    => Http::response(statsResponse([10, 20, 30])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Failed to fetch stats for dev-feature')
        ->assertExitCode(0);
});

test('check command stops when all stats fail', function () {
    fakePackageResponses([
        'dev-feature' => Http::response([], 500),
        'dev-main'    => Http::response([], 500),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('No statistics found... Stopping.')
        ->assertExitCode(0);
});

test('check command lets TypeError propagate from malformed payload', function () {
    Http::fake([
        TEST_METADATA_URL => Http::response([
            'package' => ['versions' => 'not-an-array'],
        ]),
    ]);

    $this->artisan(TEST_COMMAND);
})->throws(\TypeError::class);

test('check command shows no suggestions when all branches have downloads', function () {
    fakePackageResponses([
        'dev-main'    => Http::response(statsResponse([10, 20, 30])),
        'dev-feature' => Http::response(statsResponse([5, 10, 15])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('No suggestions available. Good job!')
        ->assertExitCode(0);
});

test('check command suggests branches with zero downloads', function () {
    fakePackageResponses([
        'dev-main'    => Http::response(statsResponse([10, 20, 30])),
        'dev-feature' => Http::response(statsResponse([0, 0, 0])),
    ]);

    $this->artisan(TEST_COMMAND)
        ->expectsOutputToContain('Found 1 branches')
        ->assertExitCode(0);
});
