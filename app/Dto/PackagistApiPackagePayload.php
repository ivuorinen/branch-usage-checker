<?php

namespace App\Dto;

final readonly class PackagistApiPackagePayload
{
    public function __construct(
        public string $name = '',
        public string $description = '',
        public string $time = '',
        public array $versions = [],
        public string $type = '',
        public string $repository = '',
        public string $language = '',
    ) {
    }

    /** Create from the raw Packagist API response array. */
    public static function fromResponse(array $data): self
    {
        $pkg = $data['package'] ?? [];

        return new self(
            name: $pkg['name'] ?? '',
            description: $pkg['description'] ?? '',
            time: $pkg['time'] ?? '',
            versions: $pkg['versions'] ?? [],
            type: $pkg['type'] ?? '',
            repository: $pkg['repository'] ?? '',
            language: $pkg['language'] ?? '',
        );
    }
}
