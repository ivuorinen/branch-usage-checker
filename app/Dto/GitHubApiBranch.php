<?php

namespace App\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class GitHubApiBranch extends DataTransferObject
{
    public string $name;
    public bool $protected;
}
