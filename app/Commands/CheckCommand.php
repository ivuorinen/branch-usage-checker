<?php

namespace App\Commands;

use App\Dto\PackagistApiPackagePayload;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Pool;
use LaravelZero\Framework\Commands\Command;

class CheckCommand extends Command
{
    protected $signature = 'check
        {vendor : Package vendor or vendor/package}
        {package? : Package name}
        {months=9 : How many months should we return for review (optional)}
        ';
    protected $description = 'Check package branch usage';

    private string $vendor = '';
    private string $package = '';
    private string $filter = '';
    private int $totalBranches = 0;

    private const NAME_PATTERN = '/^[a-z0-9]([_.\-]?[a-z0-9]+)*$/';

    private HttpFactory $http;

    public function handle(): int
    {
        $this->http = resolve(HttpFactory::class);

        $resolved = $this->resolveInput();
        if ($resolved === null) {
            return 1;
        }

        $payload = $this->fetchPackageMetadata();
        if ($payload === null) {
            return 1;
        }

        $months = (int) $this->argument('months');
        $this->filter = now()->subMonths($months)->day(1)->toDateString();

        try {
            $pkg = new PackagistApiPackagePayload($payload->json());
            $this->info('Found the package. Type: ' . $pkg->type);

            $versions = collect($pkg->versions ?? [])
                ->keys()
                ->filter(fn ($version) => \str_starts_with($version, 'dev-'))
                ->sort()
                ->values();

            $this->totalBranches = $versions->count();

            $this->info(
                sprintf(
                    'Package has %d branches. Starting to download statistics.',
                    $this->totalBranches
                )
            );

            $responses = $this->http->pool(
                fn (Pool $pool) => $versions->map(
                    fn ($branch) => $pool->as($branch)->timeout(10)->get($this->getStatsUrl($branch))
                )->toArray()
            );

            $statistics = [];
            foreach ($versions as $branch) {
                $response = $responses[$branch];

                if ($response->failed()) {
                    $this->warn("Failed to fetch stats for {$branch} (HTTP {$response->status()}), skipping.");
                    continue;
                }

                $data   = collect($response->json());
                $labels = collect($data->get('labels', []))->toArray();
                $values = collect($data->get('values', []))->flatten()->toArray();

                $labels[] = 'Total';
                $values[] = array_sum($values);

                $statistics[$branch] = \array_combine($labels, $values);
            }

            $this->info('Downloaded statistics...');

            if ($this->outputTable($statistics)) {
                $this->outputSuggestions($statistics);
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Parse and validate vendor/package input arguments.
     *
     * @return array{string, string}|null Vendor and package names, or null on failure.
     */
    private function resolveInput(): ?array
    {
        $vendor  = strtolower((string) $this->argument('vendor'));
        $package = $this->argument('package');

        if (str_contains($vendor, '/')) {
            if ($package !== null) {
                $this->error(
                    'Conflicting arguments: vendor/package format'
                    . ' and separate package argument cannot be used together.'
                );
                return null;
            }
            [$vendor, $package] = explode('/', $vendor, 2);
        }

        if ($package === null || $package === '') {
            $this->error('Missing package name. Usage: check vendor/package or check vendor package');
            return null;
        }

        $package = strtolower((string) $package);

        if (!preg_match(self::NAME_PATTERN, $vendor)) {
            $this->error("Invalid vendor name: {$vendor}");
            return null;
        }

        if (!preg_match(self::NAME_PATTERN, $package)) {
            $this->error("Invalid package name: {$package}");
            return null;
        }

        $this->vendor  = $vendor;
        $this->package = $package;

        return [$vendor, $package];
    }

    /**
     * Fetch package metadata from Packagist.
     *
     * @return \Illuminate\Http\Client\Response|null The response, or null on failure.
     */
    private function fetchPackageMetadata(): ?\Illuminate\Http\Client\Response
    {
        $months = (int) $this->argument('months');

        $this->info('Checking: ' . sprintf('%s/%s', $this->vendor, $this->package));
        $this->info('Months: ' . $months);

        $payload = $this->http->timeout(10)->get(
            sprintf(
                'https://packagist.org/packages/%s/%s.json',
                $this->vendor,
                $this->package
            )
        );

        if ($payload->failed()) {
            if ($payload->status() === 404) {
                $this->error("Package not found: {$this->vendor}/{$this->package}");
                return null;
            }
            $this->error("Failed to fetch package metadata (HTTP {$payload->status()})");
            return null;
        }

        return $payload;
    }

    private function getStatsUrl(string $branch): string
    {
        return sprintf(
            'https://packagist.org/packages/%s/%s/stats/%s.json?average=monthly&from=%s',
            $this->vendor,
            $this->package,
            $branch,
            $this->filter
        );
    }

    private function outputTable(array $statistics): bool
    {
        if (empty($statistics)) {
            $this->info('No statistics found... Stopping.');
            return false;
        }

        $tableHeaders  = ['' => 'Branch'];
        $tableBranches = [];

        foreach ($statistics as $branch => $stats) {
            foreach ($stats as $m => $v) {
                $tableHeaders[$m]                = (string) $m;
                $tableBranches[$branch][$branch] = $branch;
                $tableBranches[$branch][$m]      = (string) $v;
            }
        }

        $this->line('');
        $this->table($tableHeaders, $tableBranches);

        return true;
    }

    private function outputSuggestions(array $statistics = []): bool
    {
        $deletable = [];

        foreach ($statistics as $k => $values) {
            if (!empty($values['Total'])) {
                continue;
            }
            $deletable[$k] = $values['Total'];
        }

        if (empty($deletable)) {
            $this->info('No suggestions available. Good job!');
            return false;
        }

        $keys = array_keys($deletable);

        $branches = collect($keys)->mapWithKeys(function ($branch) {
            return [
                $branch => [
                    $branch,
                    sprintf(
                        'https://packagist.org/packages/%s/%s#%s',
                        $this->vendor,
                        $this->package,
                        $branch
                    ),
                ],
            ];
        });

        $this->line('');
        $this->info(
            sprintf(
                'Found %d branches (out of %d total) with no downloads since %s',
                $branches->count(),
                $this->totalBranches,
                $this->filter
            )
        );
        $this->table(['Branch', 'URL'], $branches);

        return true;
    }
}
