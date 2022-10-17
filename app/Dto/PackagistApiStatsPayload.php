<?php

namespace App\Dto;

use Spatie\DataTransferObject\Attributes\MapFrom;

class PackagistApiStatsPayload extends \Spatie\DataTransferObject\DataTransferObject {
    public array $labels;
    #[MapFrom('values.[0]')]
    public string $version;
    #[MapFrom('values.[0][]')]
    public array $values;
    public string $average = 'monthly';
}
