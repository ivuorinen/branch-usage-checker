<?php

namespace App\Commands;

use App\Dto\PackagistApiPackagePayload;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class CheckCommand extends Command
{
    protected $signature = 'check
        {vendor : Package vendor (required)}
        {package : Package name (required)}
        {months=9 : How many months should we return for review (optional)}
        ';
    protected $description = 'Check package branch usage';

    private string $vendor = '';
    private string $package = '';
    private string $filter = '';
    private int $totalBranches = 0;

    public function handle(): int
    {
        $this->vendor  = (string)$this->argument('vendor');
        $this->package = (string)$this->argument('package');
        $months        = (int)$this->argument('months');

        $this->info('Checking: ' . sprintf('%s/%s', $this->vendor, $this->package));
        $this->info('Months: ' . $months);

        $payload = Http::get(
            sprintf(
                'https://packagist.org/packages/%s/%s.json',
                $this->vendor,
                $this->package
            )
        );

        $this->filter = now()->subMonths($months)->day(1)->toDateString();

        try {
            $pkg = new PackagistApiPackagePayload($payload->json());
            $this->info('Found the package. Type: ' . $pkg->type);

            $versions = collect($pkg->versions ?? [])
                ->keys()
                // Filter actual versions out.
                ->filter(fn ($version) => \str_starts_with($version, 'dev-'))
                ->sort();

            $this->totalBranches = $versions->count();

            $this->info(
                sprintf(
                    'Package has %d branches. Starting to download statistics.',
                    $this->totalBranches
                )
            );

            $statistics = collect($versions)
                ->mapWithKeys(fn ($branch) => $this->getStatistics($branch))
                ->toArray();

            $this->info('Downloaded statistics...');

            $this->outputTable($statistics);
            $this->outputSuggestions($statistics);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), $e);
        }

        return 0;
    }

    private function getStatistics(string $branch): array
    {
        $payload = Http::get(
            sprintf(
                'https://packagist.org/packages/%s/%s/stats/%s.json?average=monthly&from=%s',
                $this->vendor,
                $this->package,
                $branch,
                $this->filter
            )
        );

        $data   = collect($payload->json());
        $labels = collect($data->get('labels', []))->toArray();
        $values = collect($data->get('values', []))->flatten()->toArray();

        $labels[] = 'Total';
        $values[] = array_sum($values);

        return [$branch => \array_combine($labels, $values)];
    }

    private function outputTable(array $statistics): void
    {
        if (empty($statistics)) {
            $this->info('No statistics found... Stopping.');
            exit(0);
        }

        $tableHeaders  = ['' => 'Branch'];
        $tableBranches = [];

        foreach ($statistics as $branch => $stats) {
            foreach ($stats as $m => $v) {
                $tableHeaders[$m]                = (string)$m;
                $tableBranches[$branch][$branch] = $branch;
                $tableBranches[$branch][$m]      = (string)$v;
            }
        }

        $this->line('');
        $this->table($tableHeaders, $tableBranches);
    }

    private function outputSuggestions(array $statistics = []): void
    {
        $deletable = [];
        if (empty($statistics)) {
            $this->info('No statistics to give suggestions for. Quitting...');
            exit(0);
        }

        foreach ($statistics as $k => $values) {
            if (!empty($values['Total'])) {
                continue;
            }
            $deletable[$k] = $values['Total'];
        }

        if (empty($deletable)) {
            $this->info('No suggestions available. Good job!');
            exit(0);
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
    }
}
