<?php

namespace App\Dto;

use Spatie\DataTransferObject\Attributes\MapFrom;

class PackagistApiPackagePayload extends \Spatie\DataTransferObject\DataTransferObject {
    #[MapFrom('package.name')]
    public string $name = '';
    #[MapFrom('package.description')]
    public string $description = '';
    #[MapFrom('package.time')]
    public string $time = '';
    #[MapFrom('package.versions')]
    public array $versions = [];
    #[MapFrom('package.type')]
    public string $type = '';
    #[MapFrom('package.repository')]
    public string $repository = '';
    #[MapFrom('package.language')]
    public string $language = '';
}
