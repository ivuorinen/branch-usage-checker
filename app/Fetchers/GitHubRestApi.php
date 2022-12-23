<?php

namespace App\Fetchers;

use Illuminate\Support\Facades\Http;

class GitHubRestApi
{
    public static function getBranches(string $vendor, string $package): array
    {
        $pages = self::downloader($vendor, $package);
        $pages = \collect($pages)
            ->flatten(1)
            ->toArray();

        return $pages;
    }

    public static function downloader($vendor, $package): array
    {
        $responses = [];

        $continue = true;
        $page     = 1;
        $gh_api   = sprintf(
            'https://api.github.com/repos/%s/%s/branches?per_page=100',
            $vendor,
            $package
        );

        while ($continue) {
            $response = Http::get($gh_api . '&page=' . $page);

            if (empty($response)) {
                $continue = false;
            }

            $responses[$page] = $response;
            $page++;
        }

        return $responses;
    }
}
